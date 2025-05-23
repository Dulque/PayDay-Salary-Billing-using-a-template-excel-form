<?php
ob_start();
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}
include("aheader.php");

// Function to format dates from Y-m-d to d-m-y
function formatDate($dateString) {
    $date = DateTime::createFromFormat('Y-m-d', $dateString);
    return $date ? $date->format('d-m-y') : $dateString;
}

// Fetch leave request details
if (isset($_GET['id'])) {
    $request_id = $_GET['id'];

    // Fetch leave request and employee details
    $query = "
        SELECT lr.*, ud.* 
        FROM leave_request lr 
        JOIN user_details ud ON lr.user_id = ud.user_id 
        WHERE lr.id = :id
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        die("Leave request not found.");
    }

    // Fetch leave dates for the request
    $dates_query = "SELECT leave_date FROM leave_dates WHERE leave_request_id = :id ORDER BY leave_date ASC";
    $dates_stmt = $pdo->prepare($dates_query);
    $dates_stmt->execute(['id' => $request_id]);
    $leave_dates = $dates_stmt->fetchAll(PDO::FETCH_COLUMN);
} else {
    die("Invalid request.");
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status = $_POST['status'];
    $update_query = "UPDATE leave_request SET status = :status WHERE id = :id";
    $update_stmt = $pdo->prepare($update_query);
    $update_stmt->execute(['status' => ucfirst($status), 'id' => $request_id]);

    // Redirect back to leaverequests.php after updating
    header("Location: leaverequests.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Leave Request</title>
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-group textarea {
            resize: vertical;
        }
        .form-group button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: #fff;
            cursor: pointer;
        }
        .form-group button:hover {
            background-color: #0056b3;
        }
        .leave-dates {
            margin-top: 10px;
        }
        .leave-dates ul {
            list-style-type: none;
            padding: 0;
        }
        .leave-dates ul li {
            padding: 5px 0;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .button-group button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .button-group button.approve {
            background-color: #28a745;
            color: white;
        }
        .button-group button.reject {
            background-color: #dc3545;
            color: white;
        }
        .button-group button i {
            margin-right: 5px;
        }
        .user-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .user-details .form-group {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="form-container">
            <h2>View Leave Request</h2>

            <!-- User Details Section -->
            <div class="user-details">
                <div class="form-group">
                    <label>Employee ID:</label>
                    <input type="text" value="<?php echo htmlspecialchars($request['user_id']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Employee Name:</label>
                    <input type="text" value="<?php echo htmlspecialchars($request['Name']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Department:</label>
                    <input type="text" value="<?php echo htmlspecialchars($request['Department']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Designation:</label>
                    <input type="text" value="<?php echo htmlspecialchars($request['Designation']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Mode of Service:</label>
                    <input type="text" value="<?php echo htmlspecialchars($request['mode_of_service']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Phone Number:</label>
                    <input type="text" value="<?php echo htmlspecialchars($request['Phone_Number']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="text" value="<?php echo htmlspecialchars($request['Email']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Organization Name:</label>
                    <input type="text" value="<?php echo htmlspecialchars($request['org_name']); ?>" readonly>
                </div>
            </div>

            <!-- Leave Request Details Section -->
            <div class="form-group">
                <label>Leave Type:</label>
                <input type="text" value="<?php echo htmlspecialchars($request['leave_type']); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Leave Dates:</label>
                <div class="leave-dates">
                    <ul>
                        <?php foreach ($leave_dates as $date): ?>
                            <li><?php echo htmlspecialchars(formatDate($date)); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="form-group">
                <label>Reason:</label>
                <textarea readonly><?php echo htmlspecialchars($request['reason']); ?></textarea>
            </div>

            <!-- Approve/Reject Buttons -->
            <form method="POST">
                <div class="button-group">
                    <button type="submit" name="status" value="approved" class="approve">
                        <i>✔</i> Approve
                    </button>
                    <button type="submit" name="status" value="rejected" class="reject">
                        <i>✖</i> Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>