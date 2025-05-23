<?php
session_start();

// Check if the user is an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

include("aheader.php");

// Fetch user_id from the URL
if (!isset($_GET['id'])) {
    die("User ID not provided.");
}
$selectedUserId = $_GET['id'];

// Fetch bills for the specified user
$billsSql = "SELECT b.id, b.user_id, b.month, b.year, b.generated_date, b.excel_file_path, b.pdf_file_path, u.name 
             FROM bill_details b
             JOIN user_details u ON b.user_id = u.user_id
             WHERE b.user_id = :user_id
             ORDER BY b.generated_date DESC";
$billsStmt = $pdo->prepare($billsSql);
$billsStmt->execute(['user_id' => $selectedUserId]);
$bills = $billsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - User Bill History</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Premium styling */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
        }

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
            background: #fff;
            padding: 30px;
            margin: 5%;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 1200px;
            margin: 5% auto;
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #3498db;
            color: #fff;
            font-weight: 500;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-buttons a {
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .action-buttons a.download-excel {
            background-color: #2ecc71;
            color: #fff;
        }

        .action-buttons a.download-excel:hover {
            background-color: #27ae60;
        }

        .action-buttons a.download-pdf {
            background-color: #e67e22;
            color: #fff;
        }

        .action-buttons a.download-pdf:hover {
            background-color: #d35400;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            h1 {
                font-size: 24px;
            }

            th, td {
                padding: 10px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 8px;
            }

            .action-buttons a {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>
    <div class="container">
        <h1>User Bill History</h1>

        <!-- Bill History Table -->
        <table id="billTable">
            <thead>
                <tr>
                    <th>Bill ID</th>
                    <th>User Name</th>
                    <th>Generated Date</th>
                    <th>Salary Period</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bills)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">
                            No bills found for this user.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bills as $bill): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($bill['id']); ?></td>
                            <td><?php echo htmlspecialchars($bill['name']); ?></td>
                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($bill['generated_date']))); ?></td>
                            <td><?php echo htmlspecialchars($bill['month'] . ' ' . $bill['year']); ?></td>
                            <td class="action-buttons">
                                <a href="<?php echo htmlspecialchars($bill['excel_file_path']); ?>" class="download-excel" download>Download Excel</a>
                                <a href="<?php echo htmlspecialchars($bill['pdf_file_path']); ?>" class="download-pdf" download>Download PDF</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
include("footer.php");
?>