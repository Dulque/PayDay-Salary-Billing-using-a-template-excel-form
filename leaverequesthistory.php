<?php
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

// Include database connection
include("header.php");

// Fetch leave requests for the logged-in user
$user_id = $_SESSION['user_id'];
$query = "SELECT * 
          FROM leave_request 
          WHERE user_id = :user_id 
          ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute(['user_id' => $user_id]);
$leave_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Request History</title>
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

        .status-pending {
            color: #e67e22; /* Orange for pending */
        }

        .status-approved {
            color: #2ecc71; /* Green for approved */
        }

        .status-rejected {
            color: #e74c3c; /* Red for rejected */
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
        }
    </style>
</head>
<body>
    <div class="overlay"></div>
    <div class="container">
        <h1>Leave Request History</h1>

        <!-- Leave Request History Table -->
        <table id="leaveTable">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Leave Type</th>
                    <th>Number Of Days</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($leave_requests)): ?>
                    <?php foreach ($leave_requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['id']); ?></td>
                            <td><?php echo htmlspecialchars($request['leave_type']); ?></td>
                            <td><?php echo htmlspecialchars($request['number_of_days']); ?></td>
                            <td class="status-<?php echo strtolower($request['status']); ?>">
                                <?php echo htmlspecialchars($request['status']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">No leave requests found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
include("footer.php");
?>