<?php
include("database.php"); // Include your database connection file

// Fetch the user's photo and name from the database
$userId = $_SESSION['user_id'] ?? null; // Ensure the user is logged in
$userPhoto = null;
$userName = 'User'; // Default name

if ($userId) {
    $sql = "SELECT Photo, Name FROM user_details WHERE user_ID = :userid";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':userid' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (!empty($user['Photo'])) {
            $userPhoto = base64_encode($user['Photo']); // Convert binary data to base64 for display
        }
        if (!empty($user['Name'])) {
            $userName = $user['Name']; // Fetch the user's name
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayDay - Salary Billing System</title>
    <style>
        /* Navbar Styles */
        header {
            background: #333;
            color: white;
            width: 100%;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0);
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin: 1%;
            background: #444;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff9800;
            animation: fadeIn 2s ease-in-out;
        }

        .right-section {
            display: flex;
            align-items: center;
            gap: 1rem; /* Space between name tag, profile pic, and menu icon */
        }

        .welcome-tag {
            font-size: 1rem;
            color: white;
        }

        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .menu-toggle {
            font-size: 2rem;
            cursor: pointer;
            color: #ff9800;
        }

        .nav-links {
            display: none;
            flex-direction: column;
            position: absolute;
            top: 60px;
            right: 0;
            width: 20%;
            background: #444;
            text-align: center;
            padding: 1rem 0;
            animation: slideDown 0.5s ease-in-out;
        }

        .nav-links.active {
            display: flex;
        }

        .nav-links li {
            display: inline;
            margin: 2%;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: rgb(255, 255, 255);
        }

        .nav-btn {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s ease;
        }

        .nav-btn:hover {
            background: rgb(131, 130, 130);
        }

        .logout-btn {
            background: #ff4444;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: #cc0000;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 60px;
                left: 0;
                width: 100%;
                background: #444;
                text-align: center;
                padding: 1rem 0;
                animation: slideDown 0.5s ease-in-out;
            }

            .nav-links.active {
                display: flex;
            }

            .menu-toggle {
                display: block;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">PayDay</div>
            <div class="right-section">
                <div class="welcome-tag">Welcome, <?php echo htmlspecialchars($userName); ?></div>
                <?php if ($userPhoto): ?>
                    <img src="data:image/jpeg;base64,<?php echo $userPhoto; ?>" alt="Profile Photo" class="profile-icon">
                <?php else: ?>
                    <img src="default-profile-icon.jpg" alt="Profile Photo" class="profile-icon">
                <?php endif; ?>
                <div class="menu-toggle" onclick="toggleMenu()">&#9776;</div>
            </div>
            <ul class="nav-links">
                <li><a href="userhome.php" class="nav-btn">Dashboard</a></li>
                <li><a href="billhistory.php" class="nav-btn">Bill History</a></li>
                <li><a href="leaverequesthistory.php" class="nav-btn">Leave Requests</a></li>
                <li><a href="changecontact.php" class="nav-btn">Change Contact Information</a></li>
                <li><a href="changepassword.php" class="nav-btn">Change Password</a></li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </nav>
    </header>

    <script>
        function toggleMenu() {
            const navLinks = document.querySelector('.nav-links');
            navLinks.classList.toggle('active');
        }
    </script>
</body>
</html>