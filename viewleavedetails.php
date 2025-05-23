<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}
include("aheader.php");

// Check if a user ID is provided in the URL
if (!isset($_GET['id'])) {
    die("User ID not provided.");
}
$user_id = $_GET['id'];

// Fetch user details
$user_query = "SELECT Name, Department, Designation FROM user_details WHERE user_id = :user_id";
$user_stmt = $pdo->prepare($user_query);
$user_stmt->execute(['user_id' => $user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Fetch leave policy data
$leave_policy_query = "SELECT leave_type, max_leave FROM leave_policy";
$leave_policy_result = $pdo->query($leave_policy_query);
$leave_policy = [];
while ($row = $leave_policy_result->fetch(PDO::FETCH_ASSOC)) {
    $leave_policy[$row['leave_type']] = $row['max_leave'];
}

// Fetch taken leave days for the selected user
$taken_leave_query = "
    SELECT lr.leave_type, COUNT(ld.leave_date) AS taken 
    FROM leave_request lr
    JOIN leave_dates ld ON lr.id = ld.leave_request_id
    WHERE lr.user_id = :user_id 
    AND YEAR(ld.leave_date) = YEAR(CURDATE()) 
    AND lr.status = 'Approved'
    GROUP BY lr.leave_type
";
$taken_leave_stmt = $pdo->prepare($taken_leave_query);
$taken_leave_stmt->execute(['user_id' => $user_id]);
$taken_leave = [];
while ($row = $taken_leave_stmt->fetch(PDO::FETCH_ASSOC)) {
    $taken_leave[$row['leave_type']] = $row['taken'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Leave Details</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .leave-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .user-details {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .user-details h2 {
            margin-bottom: 15px;
            color: #34495e;
        }

        .user-details p {
            margin: 5px 0;
            font-size: 16px;
        }

        .leave-details {
            margin-bottom: 20px;
        }

        .leave-row {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .leave-row.header {
            font-weight: bold;
            background-color: #f8f9fa;
        }

        .leave-row:last-child {
            border-bottom: none;
        }

        .leave-type, .taken, .max {
            flex: 1;
            text-align: center;
        }

        .leave-type {
            flex: 2;
        }

        .buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            background-color: #3498db;
            color: white;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease;
        }

        .buttons button:hover {
            background-color: #2980b9;
        }

        .buttons button.back {
            background-color: #6c757d;
        }

        .buttons button.back:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="leave-container">
        <h1>View Leave Details</h1>

        <!-- User Details Section -->
        <div class="user-details">
            <h2>Employee Information</h2>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($user['Name']); ?></p>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($user['Department']); ?></p>
            <p><strong>Designation:</strong> <?php echo htmlspecialchars($user['Designation']); ?></p>
        </div>

        <!-- Leave Details Section -->
        <div class="leave-details">
            <div class="leave-row header">
                <div class="leave-type">Leave Type</div>
                <div class="taken">Taken</div>
                <div class="max">Maximum</div>
            </div>

            <?php
            // Define leave types
            $leave_types = [
                'CL' => 'Casual Leave (CL)',
                'ML' => 'Medical Leave (ML)',
                'LWA' => 'Leave Without Allowance (LWA)',
                'CO/DL' => 'Compensatory Off / Duty Leave (CO/DL)'
            ];

            // Display leave details for each type
            foreach ($leave_types as $type => $label) {
                $taken = $taken_leave[$type] ?? 0; // Default to 0 if no leave taken
                $max = $leave_policy[$type] ?? 0; // Default to 0 if no policy found
                echo "
                <div class='leave-row'>
                    <div class='leave-type'>{$label}</div>
                    <div class='taken'>{$taken}</div>
                    <div class='max'>{$max}</div>
                </div>";
            }
            ?>
        </div>

        <!-- Buttons -->
        <div class="buttons">
        <a href="view_employee.php?id=<?php echo $user_id; ?>">
            <button class="back" onclick="window.location.href='view_employee.php';">Back</button>
        </a>
        </div>
    </div>
</body>
</html>
