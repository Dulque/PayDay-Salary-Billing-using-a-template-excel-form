<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}
include("header.php");


// Fetch leave policy data
$leave_policy_query = "SELECT leave_type, max_leave FROM leave_policy";
$leave_policy_result = $pdo->query($leave_policy_query);
$leave_policy = [];
while ($row = $leave_policy_result->fetch(PDO::FETCH_ASSOC)) {
    $leave_policy[$row['leave_type']] = $row['max_leave'];
}

// Fetch taken leave days for the current user
$user_id = $_SESSION['user_id'];
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
    <title>Leave Management</title>
    <link rel="stylesheet" href="styles.css">
    <div class="overlay"></div>
</head>
<body>
    <div class="leave-container">
        <h1>Leave Management</h1>
        <form id="leaveForm">
            <!-- Leave Details -->
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
                        <div class='taken'><input type='number' value='{$taken}' readonly></div>
                        <div class='max'><input type='number' value='{$max}' readonly></div>
                    </div>";
                }
                ?>
            </div>
        </form>

        <!-- Buttons -->
        <div class="buttons">
            <button onclick="window.location.href='leaverequest.php';">Request Leave</button>
            <!--button type="button" id="requestHistoryBtn"onclick="window.location.href='leaverequesthistory.php';">Request History</button-->
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>

<?php
include("footer.php");
?>