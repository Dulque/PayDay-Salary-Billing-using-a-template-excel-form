<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}
include("header.php");


// Check if the user is logged in


$userId = $_SESSION['user_id'];
$error = "";
$success = "";

// Fetch current user details
try {
    $stmt = $pdo->prepare("SELECT * FROM user_details WHERE `user_ID` = :user_id");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPhone = $_POST['phone'];
    $newEmail = $_POST['email'];

    // Validate inputs (add more validation as needed)
    if (empty($newPhone) || empty($newEmail)) {
        $error = "Please fill in all fields.";
    } else {
        // Update contact information in the database
        try {
            $query = "UPDATE user_details SET `Phone_Number` = :phone, `Email` = :email WHERE `user_ID` = :user_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':phone', $newPhone, PDO::PARAM_STR);
            $stmt->bindParam(':email', $newEmail, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $success = "Contact information updated successfully!";
            } else {
                $error = "Failed to update contact information.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Contact Information</title>
    <link rel="stylesheet" href="styles.css">
    <div class="overlay"></div>
    <style>
        body, html {
            margin: 0;
            padding: 0;
        }
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
        <h2>Change Contact Information</h2>
        <?php if (isset($error)): ?>
            <div class="message"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="changecontact.php">
            <div class="form-group">
                <input type="text" name="phone" placeholder="New Phone Number" required>
                <input type="email" name="email" placeholder="New Email"  required>
                <button type="submit">Update</button>
            </div>
        </form>
    </div>
</body>
</html>

<?php
include("footer.php");
?>