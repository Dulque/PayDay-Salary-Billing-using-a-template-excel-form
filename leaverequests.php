<?php

session_start();


// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

include("aheader.php");

// Function to get employee name by user_id from user_Details table
function getEmployeeName($user_id) {
    global $pdo;
    $query = "SELECT name FROM user_Details WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchColumn();
}

// Function to format dates from Y-m-d to d-m-y
function formatDate($dateString) {
    $date = DateTime::createFromFormat('Y-m-d', $dateString);
    return $date ? $date->format('d-m-y') : $dateString;
}

// Function to validate and reject leave requests that exceed max_leave
function validateAndRejectLeaveRequests($user_id, $leave_type, $max_leave) {
    global $pdo;

    // Fetch total approved leave days for the user and leave type
    $approved_query = "
        SELECT SUM(number_of_days) AS total_approved_days
        FROM leave_request
        WHERE user_id = :user_id
        AND leave_type = :leave_type
        AND status = 'Approved'
    ";
    $approved_stmt = $pdo->prepare($approved_query);
    $approved_stmt->execute([
        'user_id' => $user_id,
        'leave_type' => $leave_type
    ]);
    $total_approved_days = $approved_stmt->fetchColumn() ?? 0;

    // Calculate remaining leave days
    $remaining_leave_days = $max_leave - $total_approved_days;

    // Fetch all pending leave requests for the user and leave type
    $pending_query = "
        SELECT id, number_of_days
        FROM leave_request
        WHERE user_id = :user_id
        AND leave_type = :leave_type
        AND status = 'Pending'
    ";
    $pending_stmt = $pdo->prepare($pending_query);
    $pending_stmt->execute([
        'user_id' => $user_id,
        'leave_type' => $leave_type
    ]);
    $pending_requests = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check each pending request and reject if it exceeds remaining leave days
    foreach ($pending_requests as $request) {
        if ($request['number_of_days'] > $remaining_leave_days) {
            // Reject the request
            $reject_query = "UPDATE leave_request SET status = 'Rejected' WHERE id = :id";
            $reject_stmt = $pdo->prepare($reject_query);
            $reject_stmt->execute(['id' => $request['id']]);
        }
    }
}

// Handle leave request approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_request'])) {
    $request_id = $_POST['request_id'];
    $user_id = $_POST['user_id'];
    $leave_type = $_POST['leave_type'];

    // Fetch max_leave for the leave type
    $max_leave_query = "SELECT max_leave FROM leave_policy WHERE leave_type = :leave_type";
    $max_leave_stmt = $pdo->prepare($max_leave_query);
    $max_leave_stmt->execute(['leave_type' => $leave_type]);
    $max_leave = $max_leave_stmt->fetchColumn();

    // Approve the leave request
    $approve_query = "UPDATE leave_request SET status = 'Approved' WHERE id = :id";
    $approve_stmt = $pdo->prepare($approve_query);
    $approve_stmt->execute(['id' => $request_id]);

    // Validate and reject any pending requests that exceed max_leave
    validateAndRejectLeaveRequests($user_id, $leave_type, $max_leave);

    // Redirect or show success message
    header("Location: leave_requests.php");
    exit();
}

// Fetch pending leave requests with dates
$pending_query = "
    SELECT lr.id, lr.user_id, lr.leave_type, lr.status, 
           GROUP_CONCAT(ld.leave_date ORDER BY ld.leave_date ASC SEPARATOR ', ') AS leave_dates
    FROM leave_request lr
    LEFT JOIN leave_dates ld ON lr.id = ld.leave_request_id
    WHERE lr.status = 'Pending'
    GROUP BY lr.id
    ORDER BY lr.created_at DESC
";
$pending_stmt = $pdo->prepare($pending_query);
$pending_stmt->execute();
$pending_requests = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch approved leave requests with dates
$approved_query = "
    SELECT lr.id, lr.user_id, lr.leave_type, lr.status, 
           GROUP_CONCAT(ld.leave_date ORDER BY ld.leave_date ASC SEPARATOR ', ') AS leave_dates
    FROM leave_request lr
    LEFT JOIN leave_dates ld ON lr.id = ld.leave_request_id
    WHERE lr.status = 'Approved'
    GROUP BY lr.id
    ORDER BY lr.created_at DESC
";
$approved_stmt = $pdo->prepare($approved_query);
$approved_stmt->execute();
$approved_requests = $approved_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch rejected leave requests with dates
$rejected_query = "
    SELECT lr.id, lr.user_id, lr.leave_type, lr.status, 
           GROUP_CONCAT(ld.leave_date ORDER BY ld.leave_date ASC SEPARATOR ', ') AS leave_dates
    FROM leave_request lr
    LEFT JOIN leave_dates ld ON lr.id = ld.leave_request_id
    WHERE lr.status = 'Rejected'
    GROUP BY lr.id
    ORDER BY lr.created_at DESC
";
$rejected_stmt = $pdo->prepare($rejected_query);
$rejected_stmt->execute();
$rejected_requests = $rejected_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Requests</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .content {
            padding: 20px;
        }
        .form-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .toggle-buttons {
            margin-bottom: 20px;
        }
        .toggle-buttons button {
            padding: 10px 20px;
            margin-right: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background-color: #f8f8f8;
        }
        .toggle-buttons button.active {
            background-color: #007bff;
            color: #fff;
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
        .search-bar {
            margin-bottom: 20px;
        }
        .search-bar input {
            padding: 10px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        a {
            text-decoration: none;
            color: #007bff;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="content">
        <div id="leaveRequests" class="form-container">
            <h2>Leave Requests</h2>
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search by employee name">
            </div>
            <div class="toggle-buttons">
                <button id="pendingButton" class="active" onclick="showPendingRequests()">Pending Requests</button>
                <button id="approvedButton" onclick="showApprovedRequests()">Approved Requests</button>
                <button id="rejectedButton" onclick="showRejectedRequests()">Rejected Requests</button>
            </div>
            <div id="pendingSection">
                <h3>Pending Requests</h3>
                <table id="pendingRequests">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Leave Type</th>
                            <th>Leave Dates</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_requests as $request): ?>
                            <tr id="request-<?php echo $request['id']; ?>" class="pending-row">
                                <td><?php echo htmlspecialchars($request['user_id']); ?></td>
                                <td class="employee-name"><?php echo htmlspecialchars(getEmployeeName($request['user_id'])); ?></td>
                                <td><?php echo htmlspecialchars($request['leave_type']); ?></td>
                                <td>
                                    <?php 
                                    if (!empty($request['leave_dates'])) {
                                        $dates = explode(', ', $request['leave_dates']);
                                        $formatted_dates = array_map('formatDate', $dates);
                                        echo htmlspecialchars(implode(', ', $formatted_dates));
                                    } else {
                                        echo 'No dates available';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                        <input type="hidden" name="leave_type" value="<?php echo $request['leave_type']; ?>">
                                        
                                    </form>
                                    <a href="viewrequest.php?id=<?php echo $request['id']; ?>">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="approvedSection" style="display: none;">
                <h3>Approved Requests</h3>
                <table id="approvedRequests">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Leave Type</th>
                            <th>Leave Dates</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approved_requests as $request): ?>
                            <tr class="approved-row">
                                <td><?php echo htmlspecialchars($request['user_id']); ?></td>
                                <td class="employee-name"><?php echo htmlspecialchars(getEmployeeName($request['user_id'])); ?></td>
                                <td><?php echo htmlspecialchars($request['leave_type']); ?></td>
                                <td>
                                    <?php 
                                    if (!empty($request['leave_dates'])) {
                                        $dates = explode(', ', $request['leave_dates']);
                                        $formatted_dates = array_map('formatDate', $dates);
                                        echo htmlspecialchars(implode(', ', $formatted_dates));
                                    } else {
                                        echo 'No dates available';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($request['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="rejectedSection" style="display: none;">
                <h3>Rejected Requests</h3>
                <table id="rejectedRequests">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Leave Type</th>
                            <th>Leave Dates</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rejected_requests as $request): ?>
                            <tr class="rejected-row">
                                <td><?php echo htmlspecialchars($request['user_id']); ?></td>
                                <td class="employee-name"><?php echo htmlspecialchars(getEmployeeName($request['user_id'])); ?></td>
                                <td><?php echo htmlspecialchars($request['leave_type']); ?></td>
                                <td>
                                    <?php 
                                    if (!empty($request['leave_dates'])) {
                                        $dates = explode(', ', $request['leave_dates']);
                                        $formatted_dates = array_map('formatDate', $dates);
                                        echo htmlspecialchars(implode(', ', $formatted_dates));
                                    } else {
                                        echo 'No dates available';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($request['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Function to show pending leave requests
        function showPendingRequests() {
            document.getElementById("pendingSection").style.display = "block";
            document.getElementById("approvedSection").style.display = "none";
            document.getElementById("rejectedSection").style.display = "none";
            document.getElementById("pendingButton").classList.add("active");
            document.getElementById("approvedButton").classList.remove("active");
            document.getElementById("rejectedButton").classList.remove("active");
        }

        // Function to show approved leave requests
        function showApprovedRequests() {
            document.getElementById("pendingSection").style.display = "none";
            document.getElementById("approvedSection").style.display = "block";
            document.getElementById("rejectedSection").style.display = "none";
            document.getElementById("approvedButton").classList.add("active");
            document.getElementById("pendingButton").classList.remove("active");
            document.getElementById("rejectedButton").classList.remove("active");
        }

        // Function to show rejected leave requests
        function showRejectedRequests() {
            document.getElementById("pendingSection").style.display = "none";
            document.getElementById("approvedSection").style.display = "none";
            document.getElementById("rejectedSection").style.display = "block";
            document.getElementById("rejectedButton").classList.add("active");
            document.getElementById("pendingButton").classList.remove("active");
            document.getElementById("approvedButton").classList.remove("active");
        }

        // Show pending requests by default
        showPendingRequests();

        // Live search functionality
        document.getElementById("searchInput").addEventListener("input", function () {
            const searchTerm = this.value.toLowerCase();
            const pendingRows = document.querySelectorAll("#pendingRequests tbody tr.pending-row");
            const approvedRows = document.querySelectorAll("#approvedRequests tbody tr.approved-row");
            const rejectedRows = document.querySelectorAll("#rejectedRequests tbody tr.rejected-row");

            // Filter pending requests
            pendingRows.forEach(row => {
                const name = row.querySelector(".employee-name").textContent.toLowerCase();
                if (name.includes(searchTerm)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });

            // Filter approved requests
            approvedRows.forEach(row => {
                const name = row.querySelector(".employee-name").textContent.toLowerCase();
                if (name.includes(searchTerm)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });

            // Filter rejected requests
            rejectedRows.forEach(row => {
                const name = row.querySelector(".employee-name").textContent.toLowerCase();
                if (name.includes(searchTerm)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });
    </script>
</body>
</html>