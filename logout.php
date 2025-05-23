<?php
session_start();

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Prevent caching of pages that require authentication
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to the home page
header("Location: home.php");
exit();
?>