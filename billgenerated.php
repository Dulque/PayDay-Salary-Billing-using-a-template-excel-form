<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

include("header.php");

// Include the Composer autoload file
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Tcpdf;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Initialize variables
$billGenerated = false;
$errorMessage = '';
$formVisible = true; // Control form visibility

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $selectedMonth = $_POST['month'];
    $selectedYear = $_POST['year'];

    // Validate month and year
    if (!is_numeric($selectedYear)) {
        $errorMessage = "Invalid month or year selected.";
    } else {
        // Calculate the date range for leave calculation
        $billMonth = date('m', strtotime("$selectedYear-$selectedMonth-01"));
        $billYear = $selectedYear;

        // Calculate the start date (21st of the previous month)
        $startDate = date('Y-m-21', strtotime("$billYear-$billMonth-01 -1 month"));
        // Calculate the end date (20th of the bill month)
        $endDate = date('Y-m-20', strtotime("$billYear-$billMonth-01"));

        // Calculate total working days in the period (excluding weekends)
        $totalWorkingDays = 0;
        $currentDate = new DateTime($startDate);
        $endDateObj = new DateTime($endDate);
        
        while ($currentDate <= $endDateObj) {
            // Skip weekends (Saturday and Sunday)
            if ($currentDate->format('N') < 6) {
                $totalWorkingDays++;
            }
            $currentDate->modify('+1 day');
        }

        // Fetch leave dates for the user within the date range
        $user_id = $_SESSION['user_id'];
        $leaveDatesQuery = "
            SELECT lr.leave_type, ld.leave_date
            FROM leave_request lr
            JOIN leave_dates ld ON lr.id = ld.leave_request_id
            WHERE lr.user_id = :user_id
            AND ld.leave_date BETWEEN :start_date AND :end_date
            AND lr.status = 'Approved'
            ORDER BY ld.leave_date ASC
        ";
        $leaveDatesStmt = $pdo->prepare($leaveDatesQuery);
        $leaveDatesStmt->execute([
            'user_id' => $user_id,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        $leaveDates = $leaveDatesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if all working days are leave days
        $leaveDaysCount = count($leaveDates);
        if ($leaveDaysCount >= $totalWorkingDays) {
            $errorMessage = "You can't generate bill for $selectedMonth $selectedYear because you were absent for all working days.";
            $formVisible = true;
        } else {
            // Continue with bill generation if not all days are leave days
            $leaveDatesByType = [];
            foreach ($leaveDates as $leave) {
                $leaveType = $leave['leave_type'];
                $leaveDate = $leave['leave_date'];
                if (!isset($leaveDatesByType[$leaveType])) {
                    $leaveDatesByType[$leaveType] = [];
                }
                $leaveDatesByType[$leaveType][] = $leaveDate;
            }

            // Function to format ML dates (continuous as range, non-continuous as comma-separated)
            function formatMLDates($dates) {
                if (empty($dates)) return '';

                $formattedDates = [];
                $startDate = $dates[0];
                $prevDate = $dates[0];

                for ($i = 1; $i < count($dates); $i++) {
                    $currentDate = $dates[$i];
                    $prevDateTimestamp = strtotime($prevDate);
                    $currentDateTimestamp = strtotime($currentDate);

                    // Check if the current date is the next day of the previous date
                    if ($currentDateTimestamp == $prevDateTimestamp + 86400) {
                        $prevDate = $currentDate;
                    } else {
                        if ($startDate == $prevDate) {
                            $formattedDates[] = $startDate;
                        } else {
                            $formattedDates[] = "$startDate - $prevDate";
                        }
                        $startDate = $currentDate;
                        $prevDate = $currentDate;
                    }
                }

                // Add the last range or single date
                if ($startDate == $prevDate) {
                    $formattedDates[] = $startDate;
                } else {
                    $formattedDates[] = "$startDate - $prevDate";
                }

                return implode(', ', $formattedDates);
            }

            // Fetch leaves taken before the start date (before the billing period)
            $leavesBeforeQuery = "
                SELECT lr.leave_type, COUNT(ld.leave_date) AS leave_count
                FROM leave_request lr
                JOIN leave_dates ld ON lr.id = ld.leave_request_id
                WHERE lr.user_id = :user_id
                AND ld.leave_date < :start_date
                AND lr.status = 'Approved'
                GROUP BY lr.leave_type
            ";
            $leavesBeforeStmt = $pdo->prepare($leavesBeforeQuery);
            $leavesBeforeStmt->execute([
                'user_id' => $user_id,
                'start_date' => $startDate
            ]);
            $leavesBefore = $leavesBeforeStmt->fetchAll(PDO::FETCH_ASSOC);

            // Initialize leave counts before the period
            $leaveCountsBefore = [
                'LWA' => 0,
                'CL' => 0,
                'ML' => 0,
                'DL/CO' => 0,
            ];
            foreach ($leavesBefore as $leave) {
                $leaveType = $leave['leave_type'];
                $leaveCountsBefore[$leaveType] = (int)$leave['leave_count'];
            }

            // Path to the Excel template
            $excelTemplatePath = 'uploads/DOC-20231220-WA0010.xlsx';

            // Create a new spreadsheet from the template
            try {
                $spreadsheet = IOFactory::load($excelTemplatePath);
                $sheet = $spreadsheet->getActiveSheet();
            } catch (Exception $e) {
                die("Error loading Excel template: " . $e->getMessage());
            }

            // Fetch user details from the database
            $sql = "SELECT name, designation, department, ac_no, ifsc_code, bank_branch, pan_no, phone_number, mode_of_service FROM user_details WHERE user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $name = $row['name'];
                $designation = $row['designation'];
                $department = $row['department'];
                $acno = $row['ac_no'];
                $ifsc = $row['ifsc_code'];
                $branch = $row['bank_branch'];
                $pan = $row['pan_no'];
                $mobileNo = $row['phone_number'];
                $modeOfService = $row['mode_of_service'];

                // Fetch salary and tax details based on designation
                $salarySql = "SELECT * FROM salary_tax_details WHERE designation = :designation";
                $salaryStmt = $pdo->prepare($salarySql);
                $salaryStmt->bindParam(':designation', $designation, PDO::PARAM_STR);
                $salaryStmt->execute();

                if ($salaryStmt->rowCount() > 0) {
                    $salaryRow = $salaryStmt->fetch(PDO::FETCH_ASSOC);
                    $salary = $salaryRow['salary'];
                    $it = $salaryRow['it_percentage'];
                } else {
                    $errorMessage = "No salary details found for the selected designation.";
                    $salary = 0;
                    $tax = 0;
                }

                // Fill the Excel file with data
                $sheet->setCellValue('E5', $selectedMonth); // Set month
                $sheet->setCellValue('G5', $selectedYear); // Set year
                $sheet->setCellValue('C5', $modeOfService); // Mode of service
                $sheet->setCellValue('C6', $name); // Name
                $sheet->setCellValue('C7', $designation); // Designation
                $sheet->setCellValue('C8', $department); // Department
                $sheet->setCellValue('D33', $acno); // Account Number
                $sheet->setCellValue('D34', $ifsc); // IFSC Code
                $sheet->setCellValue('D35', $branch); // Bank Branch
                $sheet->setCellValue('D36', $pan); // PAN Number
                $sheet->setCellValue('B39', $mobileNo); // Mobile Number
                $sheet->setCellValue('B41', date('d-m-Y')); // Current Date
                $sheet->setCellValue('F17', $salary); // Salary
                $sheet->setCellValue('F23', $salary * ($it / 100)); // Income Tax
                $sheet->setCellValue('G23', $salary * ($it / 100)); // Income Tax

                // Fill leave dates in one cell per leave type
                $leaveTypeCells = [
                    'LWA' => 'D11',
                    'CL' => 'E11',
                    'ML' => 'F11',
                    'DL/CO' => 'G11',
                ];

                // Set leaves taken before the period in D12, E12, F12, G12
                $sheet->setCellValue('D12', $leaveCountsBefore['LWA']);
                $sheet->setCellValue('E12', $leaveCountsBefore['CL']);
                $sheet->setCellValue('F12', $leaveCountsBefore['ML']);
                $sheet->setCellValue('G12', $leaveCountsBefore['DL/CO']);

                // Calculate total leaves (before + during the period) and set in D13, E13, F13, G13
                $sheet->setCellValue('D13', $leaveCountsBefore['LWA'] + count($leaveDatesByType['LWA'] ?? []));
                $sheet->setCellValue('E13', $leaveCountsBefore['CL'] + count($leaveDatesByType['CL'] ?? []));
                $sheet->setCellValue('F13', $leaveCountsBefore['ML'] + count($leaveDatesByType['ML'] ?? []));
                $sheet->setCellValue('G13', $leaveCountsBefore['DL/CO'] + count($leaveDatesByType['DL/CO'] ?? []));
                $sheet->setCellValue('F27', (($salary/30)*((count($leaveDatesByType['LWA'] ?? []))+count($leaveDatesByType['ML'] ?? []))));
                $sheet->setCellValue('G27', (($salary/30)*((count($leaveDatesByType['LWA'] ?? []))+count($leaveDatesByType['ML'] ?? []))));
                
                // Format and set leave dates for CL and ML
                foreach ($leaveDatesByType as $leaveType => $dates) {
                    if (isset($leaveTypeCells[$leaveType])) {
                        $cell = $leaveTypeCells[$leaveType];
                        $datesString = implode("\n", $dates); // Join dates with newlines
                        $sheet->setCellValue($cell, $datesString);

                        // Auto-adjust font size based on the number of dates
                        $fontSize = max(8, 12 - count($dates)); // Adjust font size dynamically
                        $sheet->getStyle($cell)->getFont()->setSize($fontSize);
                        $sheet->getStyle($cell)->getAlignment()->setWrapText(true); // Enable text wrapping
                    }
                }
                if (isset($leaveDatesByType['CL'])) {
                    $clDates = implode(', ', $leaveDatesByType['CL']);
                    $sheet->setCellValue('E11', $clDates);
                }

                if (isset($leaveDatesByType['ML'])) {
                    $mlDates = formatMLDates($leaveDatesByType['ML']);
                    $sheet->setCellValue('F11', $mlDates);
                }

                // Align cell content vertically center
                $sheet->getStyle('C6:C8')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('D33:D36')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('F17:G17')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // Generate a unique filename for the bill
                $billFileName = 'bill_' . $selectedMonth . '_' . $selectedYear . '_' . time() . '.xlsx';
                $filledExcelPath = __DIR__ . '/bills/' . $billFileName;
                $writer = new Xlsx($spreadsheet);
                $writer->save($filledExcelPath);

                // Convert the filled Excel file to PDF
                $pdfFileName = 'bill_' . $selectedMonth . '_' . $selectedYear . '_' . time() . '.pdf';
                $pdfFilePath = __DIR__ . '/bills/' . $pdfFileName;
                $pdfWriter = new Tcpdf($spreadsheet);
                $pdfWriter->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
                $pdfWriter->setFont('helvetica', '', 10);
                $pdfWriter->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
                $pdfWriter->save($pdfFilePath);

                // Insert or update the bill details in the database
                $billDetailsQuery = "
                    INSERT INTO bill_details (user_id, month, year, excel_file_path, pdf_file_path, generated_date)
                    VALUES (:user_id, :month, :year, :excel_file_path, :pdf_file_path, NOW())
                    ON DUPLICATE KEY UPDATE
                    excel_file_path = VALUES(excel_file_path),
                    pdf_file_path = VALUES(pdf_file_path),
                    generated_date = NOW()
                ";
                $filledExcelPath = 'bills/' . $billFileName;
                $pdfFilePath = '/bills/' . $pdfFileName;
                $billDetailsStmt = $pdo->prepare($billDetailsQuery);
                $billDetailsStmt->execute([
                    'user_id' => $user_id,
                    'month' => $selectedMonth,
                    'year' => $selectedYear,
                    'excel_file_path' => $filledExcelPath,
                    'pdf_file_path' => $pdfFilePath
                ]);

                $billGenerated = true;
                $formVisible = false; // Hide the form after successful submission
            } else {
                $errorMessage = "No data found for the selected month and year.";
            }

            $pdo = null; // Close the PDO connection
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill Generation</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* CSS styles */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            backdrop-filter: blur(5px);
            background: rgba(0, 0, 0, 0.5);
            z-index: -1;
        }

        .container {
            max-width: 800px;
            width: 100%;
            margin: 7%;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h2 {
            font-size: 28px;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        p {
            font-size: 18px;
            margin-bottom: 30px;
            color: #34495e;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group select, .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }

        .form-group button {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            background: #3498db;
            color: #fff;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .form-group button:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        .action-buttons button {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .action-buttons button:hover {
            transform: translateY(-2px);
        }

        #downloadBillBtn {
            background: #3498db;
            color: #fff;
        }

        #downloadBillBtn:hover {
            background: #2980b9;
        }

        #viewBillBtn {
            background: #2ecc71;
            color: #fff;
        }

        #viewBillBtn:hover {
            background: #27ae60;
        }

        #printBillBtn {
            background: #e67e22;
            color: #fff;
        }

        #printBillBtn:hover {
            background: #d35400;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                gap: 15px;
            }

            .action-buttons button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($formVisible): ?>
            <h2>Generate Bill</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="month">Select Month:</label>
                    <select name="month" id="month" required>
                        <option value="January">January</option>
                        <option value="February">February</option>
                        <option value="March">March</option>
                        <option value="April">April</option>
                        <option value="May">May</option>
                        <option value="June">June</option>
                        <option value="July">July</option>
                        <option value="August">August</option>
                        <option value="September">September</option>
                        <option value="October">October</option>
                        <option value="November">November</option>
                        <option value="December">December</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="year">Select Year:</label>
                    <select name="year" id="year" required>
                        <?php
                        $currentYear = date('Y');
                        for ($i = $currentYear; $i >= 2000; $i--) {
                            echo "<option value='$i'>$i</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" name="submit">Generate Bill</button>
                </div>
            </form>
        <?php endif; ?>
        
        <?php if ($billGenerated): ?>
            <h2>Bill Generated Successfully!</h2>
            <p>Your bill has been generated. You can download, view, or print it using the options below.</p>
            <div class="action-buttons">
                <button id="downloadBillBtn" onclick="downloadBill()">Download Bill</button>
                <button id="viewBillBtn" onclick="viewBill()">View Bill</button>
                <button id="printBillBtn" onclick="printBill()">Print Bill</button>
            </div>
        <?php elseif ($errorMessage): ?>
            <p style="color: red;"><?php echo $errorMessage; ?></p>
            <?php if (strpos($errorMessage, "can't generate bill") !== false): ?>
                <p>Please contact HR for assistance.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        // Function to handle bill download
        function downloadBill() {
            const billUrl = 'bills/<?php echo isset($pdfFileName) ? $pdfFileName : ''; ?>';
            const link = document.createElement('a');
            link.href = billUrl;
            link.download = '<?php echo isset($pdfFileName) ? $pdfFileName : ''; ?>';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            alert('Bill downloaded successfully!');
        }

        // Function to handle bill viewing
        function viewBill() {
            const billUrl = 'bills/<?php echo isset($pdfFileName) ? $pdfFileName : ''; ?>';
            window.open(billUrl, '_blank');
        }

        // Function to handle bill printing
        function printBill() {
            const billUrl = 'bills/<?php echo isset($pdfFileName) ? $pdfFileName : ''; ?>';
            const printWindow = window.open(billUrl);
            printWindow.onload = () => {
                printWindow.print();
            };
        }
    </script>
</body>
</html>

<?php
include("footer.php");
?>