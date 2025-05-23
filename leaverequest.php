<?php
ob_start();
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

include("header.php");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $leave_type = $_POST['leaveType'];
    $date_range = $_POST['dateRange'];
    $reason = $_POST['reason'];

    // Split the date range into start and end dates
    list($start_date, $end_date) = explode(' to ', $date_range);
    
    // Convert dates from dd-mm-yy to Y-m-d format
    $start_date_obj = DateTime::createFromFormat('d-m-y', trim($start_date));
    $end_date_obj = DateTime::createFromFormat('d-m-y', trim($end_date));
    
    if (!$start_date_obj || !$end_date_obj) {
        die("Error: Invalid date format. Please use dd-mm-yy format.");
    }
    
    $start_date_db = $start_date_obj->format('Y-m-d');
    $end_date_db = $end_date_obj->format('Y-m-d');
    
    // Calculate all dates in the range
    $selected_dates = [];
    $current_date = clone $start_date_obj;
    while ($current_date <= $end_date_obj) {
        $selected_dates[] = $current_date->format('Y-m-d');
        $current_date->modify('+1 day');
    }
    
    // Calculate the number of days
    $number_of_days = count($selected_dates);

    // Fetch max_leave from leave_policy table
    $max_leave_query = "SELECT max_leave FROM leave_policy WHERE leave_type = :leave_type";
    $max_leave_stmt = $pdo->prepare($max_leave_query);
    $max_leave_stmt->execute(['leave_type' => $leave_type]);
    $max_leave = $max_leave_stmt->fetchColumn();

    // Fetch the total approved leave days for the user for the same leave type
    $approved_leave_query = "
        SELECT SUM(number_of_days) AS total_approved_days
        FROM leave_request
        WHERE user_id = :user_id
        AND leave_type = :leave_type
        AND status = 'Approved'
    ";
    $approved_leave_stmt = $pdo->prepare($approved_leave_query);
    $approved_leave_stmt->execute([
        'user_id' => $user_id,
        'leave_type' => $leave_type
    ]);
    $total_approved_days = $approved_leave_stmt->fetchColumn() ?? 0;

    // Calculate remaining leave days
    $remaining_leave_days = $max_leave - $total_approved_days;

    // Validate if the requested leave days exceed the remaining leave days
    if ($number_of_days > $remaining_leave_days) {
        die("Error: You cannot request more than $remaining_leave_days days for $leave_type. You have already used $total_approved_days days out of $max_leave.");
    }

    // Handle file upload
    $document_path = null;
    if (isset($_FILES['fileupload']) && $_FILES['fileupload']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/'; // Directory to store uploaded files
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true); // Create the directory if it doesn't exist
        }
        $document_name = basename($_FILES['fileupload']['name']);
        $document_path = $upload_dir . $document_name;
        move_uploaded_file($_FILES['fileupload']['tmp_name'], $document_path);
    }

    // Insert leave request into the database
    $pdo->beginTransaction(); // Start a transaction

    try {
        // Insert into leave_request table
        $insert_query = "INSERT INTO leave_request (user_id, leave_type, number_of_days, document_path, reason, status) 
                         VALUES (:user_id, :leave_type, :number_of_days, :document_path, :reason, 'Pending')";
        $insert_stmt = $pdo->prepare($insert_query);
        $insert_stmt->execute([
            'user_id' => $user_id,
            'leave_type' => $leave_type,
            'number_of_days' => $number_of_days,
            'document_path' => $document_path,
            'reason' => $reason
        ]);

        // Get the last inserted leave request ID
        $leave_request_id = $pdo->lastInsertId();

        // Insert selected dates into leave_dates table
        $date_insert_query = "INSERT INTO leave_dates (leave_request_id, leave_date) VALUES (:leave_request_id, :leave_date)";
        $date_insert_stmt = $pdo->prepare($date_insert_query);

        foreach ($selected_dates as $date) {
            $date_insert_stmt->execute([
                'leave_request_id' => $leave_request_id,
                'leave_date' => $date
            ]);
        }

        $pdo->commit(); // Commit the transaction
        $success_message = "Leave request submitted successfully!";
        header("Location: userhome.php");
    } catch (Exception $e) {
        $pdo->rollBack(); // Rollback the transaction on error
        die("Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Request</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Include Flatpickr CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        .leave-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group select, 
        .form-group input[type="text"],
        .form-group input[type="file"],
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        button[type="submit"] {
            background-color:rgb(83, 103, 167);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button[type="submit"]:hover {
            background-color: #45a049;
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
    </style>
</head>
<body>
    <!-- Leave Request Form -->
    <div class="leave-container">
        <form method="POST" action="leaverequest.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="leaveType">Leave Type</label>
                <select id="leaveType" name="leaveType" required>
                    <option value="CL">Casual Leave (CL)</option>
                    <option value="ML">Medical Leave (ML)</option>
                    <option value="LWA">Leave Without Allowance (LWA)</option>
                    <option value="CO/DL">Compensatory Off / Duty Leave (CO/DL)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="dateRange">Select Date Range</label>
                <input type="text" id="dateRange" name="dateRange" placeholder="Select start and end date (dd-mm-yy)" required>
            </div>

            <div class="form-group">
                <label for="document">Document:</label>
                <input type="file" id="document" name="fileupload">
            </div>

            <div class="form-group">
                <label for="reason">Reason</label>
                <textarea id="reason" name="reason" rows="4" required></textarea>
            </div>

            <button type="submit">Request Leave</button>
        </form>
    </div>

    <?php if (!empty($success_message)): ?>
        <script>
            alert("<?php echo $success_message; ?>");
        </script>
    <?php endif; ?>

    <script>
        // Initialize Flatpickr with range selection and dd-mm-yy format
        flatpickr("#dateRange", {
            mode: "range",
            dateFormat: "d-m-y",
            allowInput: true,
            altInput: true,
            altFormat: "d-m-y",
            minDate: "today",
            locale: {
                firstDayOfWeek: 1 // Start week on Monday
            },
            onReady: function(selectedDates, dateStr, instance) {
                // Customize the range selection display
                if (selectedDates.length === 2) {
                    instance.input.value = instance.formatDate(selectedDates[0], "d-m-y") + 
                                          " to " + 
                                          instance.formatDate(selectedDates[1], "d-m-y");
                }
            },
            onChange: function(selectedDates, dateStr, instance) {
                // Update the input with the selected range
                if (selectedDates.length === 2) {
                    instance.input.value = instance.formatDate(selectedDates[0], "d-m-y") + 
                                          " to " + 
                                          instance.formatDate(selectedDates[1], "d-m-y");
                }
            }
        });
    </script>
</body>
</html>

<?php
include("footer.php");
?>