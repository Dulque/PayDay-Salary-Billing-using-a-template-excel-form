<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}
include ('aheader.php');


// Fetch all leave requests with user details and leave dates
$query = "
    SELECT lr.*, ud.Name AS user_name, GROUP_CONCAT(ld.leave_date ORDER BY ld.leave_date ASC) AS leave_dates
    FROM leave_request lr
    JOIN user_details ud ON lr.user_id = ud.user_id
    LEFT JOIN leave_dates ld ON lr.id = ld.leave_request_id
    GROUP BY lr.id
    ORDER BY lr.created_at DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute();
$leave_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Leave Request History</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container {
            background: #fff;
            padding: 30px;
            margin: 5%;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 90%; /* Adjusted width for better readability */
            text-align: center;
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
            background-color: #f8f8f8;
            font-weight: bold;
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

        .leave-dates {
            white-space: nowrap; /* Prevent dates from wrapping */
        }
    </style>
</head>
<body>
    <div class="overlay"></div>
    <div class="container">
        <h1>Admin - Leave Request History</h1>
        <table id="leaveTable">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>User Name</th>
                    <th>Leave Type</th>
                    <th>Number Of Days</th>
                    <th>Leave Dates</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($leave_requests)): ?>
                    <?php foreach ($leave_requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['id']); ?></td>
                            <td><?php echo htmlspecialchars($request['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['leave_type']); ?></td>
                            <td><?php echo htmlspecialchars($request['number_of_days']); ?></td>
                            <td class="leave-dates">
                                <?php
                                if (!empty($request['leave_dates'])) {
                                    $dates = explode(',', $request['leave_dates']);
                                    $formatted_dates = array_map(function($date) {
                                        $date_obj = DateTime::createFromFormat('Y-m-d', $date);
                                        return $date_obj ? $date_obj->format('d-m-y') : $date;
                                    }, $dates);
                                    echo implode('<br>', $formatted_dates); // Display dates on separate lines
                                } else {
                                    echo 'No dates available';
                                }
                                ?>
                            </td>
                            <td class="status-<?php echo strtolower($request['status']); ?>">
                                <?php echo htmlspecialchars($request['status']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No leave requests found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>