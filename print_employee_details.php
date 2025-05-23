<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

include 'aheader.php';

// Get the filtered employee IDs from the query parameter
$employee_ids = isset($_GET['employee_ids']) ? explode(',', $_GET['employee_ids']) : [];

// Fetch employee details from the database using PDO
$sql = "SELECT *
        FROM user_details ud 
        WHERE ud.user_ID IN (" . implode(',', array_fill(0, count($employee_ids), '?')) . ")";
$stmt = $pdo->prepare($sql);
$stmt->execute($employee_ids);
$employees = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Employee Details</title>
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            color: #333;
        }

        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .table-container {
            width: 100%;
            overflow-x: auto; /* Enable horizontal scrolling */
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px; /* Ensure the table has a minimum width */
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Print-specific Styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            h2 {
                font-size: 24px;
                margin-bottom: 15px;
            }

            .table-container {
                overflow-x: visible; /* Disable scrolling for print */
                width: 100%;
            }

            table {
                width: 100%;
                font-size: 12px;
                min-width: 100%; /* Ensure the table fits the page width */
            }

            th, td {
                padding: 8px;
            }

            /* Ensure table fits within the page */
            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }
        }
    </style>
</head>
<body>
    <div class="content">
        <h2>Employee Details</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Designation</th>
                        <th>Mode of Service</th>
                        <th>Department</th>
                        <th>Phone Number</th>
                        <th>Email</th>
                        <th>Started Year</th>
                        <th>Organization Name</th>
                        <th>Account Number</th>
                        <th>Bank Branch</th>
                        <th>IFSC Code</th>
                        <th>PAN Number</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><?php echo $employee['Name']; ?></td>
                            <td><?php echo $employee['Designation']; ?></td>
                            <td><?php echo $employee['mode_of_service']; ?></td>
                            <td><?php echo $employee['Department']; ?></td>
                            <td><?php echo $employee['Phone_Number']; ?></td>
                            <td><?php echo $employee['Email']; ?></td>
                            <td><?php echo $employee['startedyear']; ?></td>
                            <td><?php echo $employee['org_name']; ?></td>
                            <td><?php echo $employee['ac_no']; ?></td>
                            <td><?php echo $employee['bank_branch']; ?></td>
                            <td><?php echo $employee['ifsc_code']; ?></td>
                            <td><?php echo $employee['pan_no']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>