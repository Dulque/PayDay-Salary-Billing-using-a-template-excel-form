<?php

session_start();
if (!isset($_SESSION['user_id'])) {
   header("Location: home.php");
   exit();
}
include("database.php"); // Include your PDO database connection file

if (isset($_POST['user_ID'])) {
    $id = $_POST['user_ID'];

    try {
        // Begin a transaction
        $pdo->beginTransaction();

        // Delete from user_details table
        $sql1 = "DELETE FROM user_details WHERE user_ID = :user_ID";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute(['user_ID' => $id]);

        // Delete from users table
        $sql2 = "DELETE FROM users WHERE user_ID = :user_ID";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute(['user_ID' => $id]);

        $sql3 = "DELETE FROM leave_request WHERE user_ID = :user_ID";
        $stmt3 = $pdo->prepare($sql2);
        $stmt3->execute(['user_ID' => $id]);

        $sql4 = "DELETE FROM bill_details WHERE user_ID = :user_ID";
        $stmt4 = $pdo->prepare($sql2);
        $stmt4->execute(['user_ID' => $id]);

        // Commit the transaction
        $pdo->commit();

        echo "success"; // Indicate success
    } catch (PDOException $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request. user_ID not provided.";
}
?>