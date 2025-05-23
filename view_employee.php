<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}
include 'aheader.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$user_id = $_GET['id'];

// Fetch employee details from the database using PDO
$sql = "SELECT * FROM user_details WHERE user_ID = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$employee = $stmt->fetch();

// Handle resend password request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_password'])) {
    // Generate a new random password (8 characters with letters and numbers)
    $new_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password in database
    $update_sql = "UPDATE users SET password = :password WHERE user_ID = :user_id";
    $update_stmt = $pdo->prepare($update_sql);
    $update_result = $update_stmt->execute([
        'password' => $hashed_password,
        'user_id' => $user_id
    ]);

    if ($update_result) {
        $email = $employee['Email'];
        $name = $employee['Name'];

        // Send email with user_ID and new password using PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Server settings for Gmail
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
            $mail->SMTPAuth = true; // Enable SMTP authentication
            $mail->Username = 'Your Gmail address'; // Your Gmail address
            $mail->Password = 'Your App Password'; // Your App Password
            $mail->SMTPSecure = 'ssl'; // Use SSL encryption
            $mail->Port = 465; // SMTP port for SSL

            // Recipients
            $mail->setFrom('Your Gmail address', 'PayDay');
            $mail->addAddress($email, $name); // Add the employee's email

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your New Account Password';
            $mail->Body = "Dear $name,<br><br>
                           Your password has been reset. Here are your new login details:<br>
                           User ID: $user_id<br>
                           New Password: $new_password<br><br>
                           Please log in and change your password immediately for security reasons.<br><br>
                           Best regards,<br>PayDay";

            $mail->send();
            echo "<script>alert('New password generated and sent successfully!');</script>";
        } catch (Exception $e) {
            echo "<script>alert('Password was updated but failed to send email. Error: " . $mail->ErrorInfo . "');</script>";
        }
    } else {
        echo "<script>alert('Failed to update password in database.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Details</title>
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
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
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
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

        .employee-photo {
            text-align: center;
            margin-bottom: 20px;
        }

        .employee-photo img {
            max-width: 150px;
            border-radius: 50%;
            border: 4px solid #3498db;
        }

        .actions {
            text-align: center;
            margin-top: 20px;
        }

        .actions button {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 10px;
            transition: background 0.3s ease;
        }

        .actions button:hover {
            background-color: #2980b9;
        }

        .actions button.print {
            background-color: #28a745;
        }

        .actions button.print:hover {
            background-color: #218838;
        }

        .actions button.leave-details {
            background-color: #ff6b6b;
        }

        .actions button.leave-details:hover {
            background-color: #ff5252;
        }

        .actions button.leave-history {
            background-color: #6b5b95;
        }

        .actions button.leave-history:hover {
            background-color: #5a4a7d;
        }

        .actions button.bill-history {
            background-color: #f39c12;
        }

        .actions button.bill-history:hover {
            background-color: #e67e22;
        }

        /* Print-specific Styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .content {
                box-shadow: none;
                border: none;
            }

            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="content">
        <h2>Employee Details</h2>
        <div class="employee-photo">
            <?php if (!empty($employee['Photo'])): ?>
                <img src="data:image/jpeg;base64,<?php echo base64_encode($employee['Photo']); ?>" alt="Employee Photo">
            <?php else: ?>
                <p>No photo available.</p>
            <?php endif; ?>
        </div>
        <table>
            <tr>
                <th>Name</th>
                <td><?php echo $employee['Name']; ?></td>
            </tr>
            <tr>
                <th>Designation</th>
                <td><?php echo $employee['Designation']; ?></td>
            </tr>
            <tr>
                <th>Mode of Service</th>
                <td><?php echo $employee['mode_of_service']; ?></td>
            </tr>
            <tr>
                <th>Department</th>
                <td><?php echo $employee['Department']; ?></td>
            </tr>
            <tr>
                <th>Phone Number</th>
                <td><?php echo $employee['Phone_Number']; ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?php echo $employee['Email']; ?></td>
            </tr>
            <tr>
                <th>Started Year</th>
                <td><?php echo $employee['startedyear']; ?></td>
            </tr>
            <tr>
                <th>Organization Name</th>
                <td><?php echo $employee['org_name']; ?></td>
            </tr>
            <tr>
                <th>Account Number</th>
                <td><?php echo $employee['ac_no']; ?></td>
            </tr>
            <tr>
                <th>Bank Branch</th>
                <td><?php echo $employee['bank_branch']; ?></td>
            </tr>
            <tr>
                <th>IFSC Code</th>
                <td><?php echo $employee['ifsc_code']; ?></td>
            </tr>
            <tr>
                <th>PAN Number</th>
                <td><?php echo $employee['pan_no']; ?></td>
            </tr>
        </table>
        <div class="actions">
            <button onclick="window.print()" class="print">Print Details</button>
            <form method="POST" style="display: inline;">
                <button type="submit" name="resend_password">Resend Password</button>
            </form>
            <a href="viewleavedetails.php?id=<?php echo $user_id; ?>">
                <button class="leave-details">Leave Details</button>
            </a>
            <a href="leaverequesthistoryadmin.php?id=<?php echo $user_id; ?>">
                <button class="leave-history">Leave Request History</button>
            </a>
            <a href="billhistoryadmin.php?id=<?php echo $user_id; ?>">
                <button class="bill-history">Bill Generated History</button>
            </a>
        </div>
    </div>
</body>
</html>                                                 `