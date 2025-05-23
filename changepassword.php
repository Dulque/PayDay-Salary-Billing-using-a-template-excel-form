<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}
include("header.php");



// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "Please fill in all fields.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "New passwords do not match.";
    } else {
        // Verify current password
        $userId = $_SESSION['user_id'];
        $query = "SELECT password FROM users WHERE user_ID = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($currentPassword, $user['password'])) {
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE users SET password = ? WHERE user_ID = ?";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([$hashedPassword, $userId]);

            if ($updateStmt->rowCount() > 0) {
                $success = "Password updated successfully!";
            } else {
                $error = "Failed to update password.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="styles.css">
    <div class="overlay"></div>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            margin: 5%;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            width: 100%;
            padding: 10px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #2980b9;
        }
        .message {
            margin-top: 15px;
            color: red;
        }
        .success {
            color: green;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Change Password</h2>
        <?php if (isset($error)): ?>
            <div class="message"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST" action="changepassword.php">
            <div class="form-group">
                <input type="password" name="current_password" placeholder="Current Password" required>
                <input type="password" name="new_password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                <button type="submit">Update Password</button>
            </div>
        </form>
    </div>
</body>
</html>
<?php
include("footer.php");
?>