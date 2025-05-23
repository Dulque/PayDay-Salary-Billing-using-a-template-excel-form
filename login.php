<?php
session_start();

include("database.php"); // Include your database connection file
$_SESSION["user_id"] = null;
// Initialize variables
$error = "";

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get user input
        $userid = trim($_POST["userid"]);
        $password = trim($_POST["password"]);

        // Validate user input
        if (empty($userid) || empty($password)) {
            $error = "User ID and Password are required.";
        } else {
            // Check if the user exists in the users table
            $sql = "SELECT * FROM users WHERE user_ID = :userid";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':userid' => $userid]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user["password"])) {
                // Set session variables
                $_SESSION["user_id"] = $user["user_ID"];
                $_SESSION["role"] = $user["role"]; // Store the user's role in the session

                // Redirect based on role
                if ($user["role"] === "Admin") {
                    header("Location: employees.php");
                } elseif ($user["role"] === "User") {
                    header("Location: userhome.php");
                } else {
                    $error = "Invalid role assigned to the user.";
                }
                exit();
            } else {
                // If user not found or password is incorrect
                $error = "Invalid User ID or Password.";
            }
        }
    }
} catch (Exception $e) {
    $error = "An error occurred: " . $e->getMessage();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayDay - Login</title>
    <link rel="stylesheet" href="styles.css">
    <div class="overlay"></div>
    <style>
        /* Add the same styles as in home.php */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('photos/pexels-karolina-grabowska-4475523.jpg');
            background-size: cover;
            background-position: relative;
            overflow-x: hidden;
        }
        header {
            background: #333;
            color: white;
            width: 100%;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
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
        .nav-links {
            list-style: none;
            display: flex;
            gap: 1rem;
            margin: 0;
            padding: 0;
        }
        .nav-links li {
            display: inline;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 1rem;
            transition: color 0.3s ease;
        }
        .nav-links a:hover {
            color: #ff9800;
        }
        .login-btn {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        .login-btn:hover {
            background: rgb(112, 112, 112);
        }
        .menu-toggle {
            display: none;
            font-size: 2rem;
            cursor: pointer;
            color: #ff9800;
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
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
           
        }
        .login-box {
            background: white;
            padding: 4rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 300px;
            margin: 5%;
            text-align: center;
        }
        .input-group {
            margin-bottom: 1rem;
        }
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
        }
        .input-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background: #ff9800;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #e68900;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">PayDay</div>
            <ul class="nav-links">
                <li><a href="home.php">Home</a></li>
                <li><a href="home.php#features">Features</a></li>
                <li><a href="home.php#about">About</a></li>
                
                <li><a href="home.php#contact">Contact</a></li>
                <li><a href="login.php" class="login-btn">Login</a></li>
            </ul>
            <div class="menu-toggle" onclick="toggleMenu()">&#9776;</div>
        </nav>
    </header>

    
        <div class="login-box">
            <h1>LOG IN</h1>
            <form id="loginForm" action="login.php" method="post">
            <div class="form-group">
                <div class="input-group">
                    <label for="userid">User ID:</label>
                    <input type="text" name="userid" id="userid" required>
                </div>
                
                <div class="input-group">
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <button type="submit" name="login">Log In</button>
                </div>
            </form>
            <?php if (!empty($error)): ?>
                <p style="color: red;"><?php echo $error; ?></p>
            <?php endif; ?>
        </div>
   

    <script>
        function toggleMenu() {
            const navLinks = document.querySelector('.nav-links');
            navLinks.classList.toggle('active');
        }
    </script>
</body>
</html>