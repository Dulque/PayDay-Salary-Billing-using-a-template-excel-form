<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}
include 'aheader.php';

// Include PhpSpreadsheet library
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['spreadsheet'])) {
    $file = $_FILES['spreadsheet']['tmp_name'];

    try {
        // Load the spreadsheet file
        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();

        // Get the highest row and column
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();

        // Loop through each row starting from the second row (skip header)
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $worksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE)[0];

            // Extract data from the row
            $name = strtoupper($rowData[0]);
            $designation = strtoupper($rowData[1]);
            $department = strtoupper($rowData[3]);
            $phone_number = $rowData[4];
            $email = $rowData[5];
            $started_year = $rowData[6];
            $organization_name = $rowData[7];
            $account_number = $rowData[8];
            $ifsc_code = $rowData[10];
            $pan_number = $rowData[11];
            $branch = $rowData[9];
            $mode_of_service = $rowData[2]; // New field

            // Check if the designation already exists in the salary_tax_details table
            $sqlCheckDesignation = "SELECT COUNT(*) FROM salary_tax_details WHERE designation = :designation";
            $stmtCheckDesignation = $pdo->prepare($sqlCheckDesignation);
            $stmtCheckDesignation->execute([':designation' => $designation]);
            $designationExists = $stmtCheckDesignation->fetchColumn();

            // If the designation does not exist, insert it into the salary_tax_details table
            if (!$designationExists) {
                $sqlInsertDesignation = "INSERT INTO salary_tax_details (designation, salary, it_percentage, epf, esi, pt, other_deductions, created_at)
                                         VALUES (:designation, 0, 0, 0, 0, 0, 0, NOW())";
                $stmtInsertDesignation = $pdo->prepare($sqlInsertDesignation);
                $stmtInsertDesignation->execute([':designation' => $designation]);
            }

            // Generate a random password
            $password = bin2hex(random_bytes(8)); // Generates a 16-character password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT); // Hash the password

            // Insert into user_details table
            $sql1 = "INSERT INTO user_details (Name, Designation, Department, Phone_Number, Email, startedyear, org_name, ac_no, ifsc_code, pan_no, bank_branch, mode_of_service)
                     VALUES (:name, :designation, :department, :phone_number, :email, :started_year, :organization_name, :account_number, :ifsc_code, :pan_number, :branch, :mode_of_service)";
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->execute([
                ':name' => $name,
                ':designation' => $designation,
                ':department' => $department,
                ':phone_number' => $phone_number,
                ':email' => $email,
                ':started_year' => $started_year,
                ':organization_name' => $organization_name,
                ':account_number' => $account_number,
                ':ifsc_code' => $ifsc_code,
                ':pan_number' => $pan_number,
                ':branch' => $branch,
                ':mode_of_service' => $mode_of_service
            ]);

            // Get the last inserted user_ID
            $user_ID = $pdo->lastInsertId();
            $role = "User";

            // Insert into users table
            $sql2 = "INSERT INTO users (user_ID, password, role)
                     VALUES (:user_ID, :password, :role)";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([
                ':user_ID' => $user_ID,
                ':password' => $hashed_password,
                ':role' => $role
            ]);

            // Send email with user_ID and password using PHPMailer
            $mail = new PHPMailer(true);

            try {
                // Server settings for Gmail
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
                $mail->SMTPAuth = true; // Enable SMTP authentication
                $mail->Username = 'your email'; // Your Gmail address
                $mail->Password = 'Your App Password'; // Your App Password
                $mail->SMTPSecure = 'ssl'; // Use SSL encryption
                $mail->Port = 465; // SMTP port for SSL

                // Recipients
                $mail->setFrom('Your Gmail address', 'PayDay');
                $mail->addAddress($email, $name); // Add the new member's email

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Your Account Details';
                $mail->Body = "Dear $name,<br><br>
                               Your account has been created successfully.<br>
                               User ID: $user_ID<br>
                               Password: $password<br><br>
                               Please log in and change your password immediately.<br><br>
                               Best regards,<br>PayDay";

                $mail->send();
            } catch (Exception $e) {
                echo "<script>alert('Failed to send email to $email. Error: " . $mail->ErrorInfo . "');</script>";
            }
        }

        echo "<p class='success-message'>Employees added successfully.</p>";
    } catch (Exception $e) {
        echo "<p class='error-message'>Error processing the file: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Spreadsheet</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .content {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        h2 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }

        .upload-instructions {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }

        .upload-instructions h3 {
            font-size: 20px;
            color: #34495e;
            margin-bottom: 15px;
        }

        .upload-instructions ul {
            list-style-type: disc;
            padding-left: 20px;
            margin: 0;
        }

        .upload-instructions ul li {
            margin-bottom: 10px;
            font-size: 14px;
            color: #555;
        }

        .upload-instructions p {
            font-size: 14px;
            color: #777;
            margin-top: 15px;
        }

        .upload-section {
            text-align: center;
        }

        .upload-section input[type="file"] {
            display: none;
        }

        .upload-section label {
            display: inline-block;
            background: #3498db;
            color: #fff;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease;
        }

        .upload-section label:hover {
            background: #2980b9;
        }

        .upload-section button {
            background: #27ae60;
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
            transition: background 0.3s ease;
        }

        .upload-section button:hover {
            background: #219653;
        }

        .upload-section p {
            font-size: 14px;
            color: #777;
            margin-top: 15px;
        }

        /* Success and Error Messages */
        .success-message {
            color: #27ae60;
            font-size: 14px;
            text-align: center;
            margin-top: 20px;
        }

        .error-message {
            color: #e74c3c;
            font-size: 14px;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="content">
        <h2>Upload Employee Spreadsheet</h2>

        <!-- Upload Instructions -->
        <div class="upload-instructions">
            <h3>Excel Sheet Format</h3>
            <p>Please ensure your Excel file follows the format below:</p>
            <ul>
                <li><strong>Column 1:</strong> Name (e.g., John Doe)</li>
                <li><strong>Column 2:</strong> Designation (e.g., Manager)</li>
                <li><strong>Column 3:</strong> Mode of Service (e.g., Full-Time)</li>
                <li><strong>Column 4:</strong> Department (e.g., HR)</li>
                <li><strong>Column 5:</strong> Phone Number (e.g., 1234567890)</li>
                <li><strong>Column 6:</strong> Email (e.g., john.doe@example.com)</li>
                <li><strong>Column 7:</strong> Started Year (e.g., 2015)</li>
                <li><strong>Column 8:</strong> Organization Name (e.g., ABC Corp)</li>
                <li><strong>Column 9:</strong> Account Number (e.g., 123456789)</li>
                <li><strong>Column 10:</strong> Bank Branch (e.g., Main Branch)</li>
                <li><strong>Column 11:</strong> IFSC Code (e.g., ABCD123456)</li>
                <li><strong>Column 12:</strong> PAN Number (e.g., ABCDE1234F)</li>
            </ul>
            <p><strong>Note:</strong> The first row should contain headers. Data should start from the second row.</p>
        </div>

        <!-- Upload Section -->
        <div class="upload-section">
            <form action="" method="post" enctype="multipart/form-data">
                <label for="spreadsheet">Choose Excel File</label>
                <input type="file" name="spreadsheet" id="spreadsheet" accept=".xlsx, .xls, .csv" required>
                <button type="submit">Upload</button>
            </form>
            <p>Supported formats: .xlsx, .xls, .csv</p>
        </div>
    </div>
</body>
</html>