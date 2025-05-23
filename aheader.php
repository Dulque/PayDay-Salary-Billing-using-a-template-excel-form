<?php



include("database.php"); 


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <div class="overlay"></div>
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('photos/pexels-karolina-grabowska-4475523.jpg');
            background-size: cover;
            background-position: relative;
            overflow-x: hidden;
        }
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            backdrop-filter: blur(5px);
            z-index: -1;
        }

        header {
            background: #333;
            color: white;
            width: 100%;
            padding: 1rem;
            margin: 0;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1rem;
            background: #444;
            margin-right: 1rem;
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

        .menu-toggle {
            display: none;
            font-size: 2rem;
            cursor: pointer;
            color: #ff9800;
        }

        /* Admin Dashboard Styles */
        .content {
            margin-left: 5%;
            width: 80%;
            padding: 20px;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .dashboard-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            animation: fadeIn 1s ease-in-out;
        }

        .dashboard-card h3 {
            margin-bottom: 10px;
            color: #333;
        }

        .dashboard-card p {
            color: #666;
        }

        /* Form Styles */
        .form-container {
            background: white;
            padding: 20px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            animation: slideUp 1s ease-in-out;
        }

        .form-group {
            margin-bottom: 15px;
            margin-right: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            margin-right: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #ff9800;
            outline: none;
        }
        .newmember-form {
            background: white;
            padding: 20px 20px;
            position: center;
            margin: 5%;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            animation: slideUp 1s ease-in-out;
        }

        .form-group button {
            background: linear-gradient(135deg, #ff9800, #e68900);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: fadeIn 1s ease-in-out;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                width: 100%;
            }

            .nav-links {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 60px;
                left: 0;
                width: 100%;
                cursor: pointer;
                background: #444;
                text-align: center;
                padding: 1rem 0;
                animation: slideDown 0.5s ease-in-out;
            }
            .menu-toggle {
                display: block;
            }
        
            .nav-links.active {
                display: flex;
            }

            .menu-toggle {
                display: block;
            }
        }
        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .search-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .search-box input {
            width: 75%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .search-box button {
            background: linear-gradient(135deg, #ff9800, #e68900);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .search-box button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .add-new-members-btn {
            margin-left: auto; /* This will push the button to the right */
        }
        /* Container for toggle buttons */
        .toggle-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        /* Base button styles */
        .toggle-buttons button {
            padding: 12px 24px;
            border: none;
            border-radius: 25px; /* Rounded corners */
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #e0e0e0; /* Default background */
            color: #333; /* Default text color */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Subtle shadow */
        }

        /* Active state for toggle buttons */
        .toggle-buttons button.active {
            background-color: #3498db; /* Blue for active state */
            color: white;
            box-shadow: 0 4px 6px rgba(52, 152, 219, 0.3); /* Shadow with blue tint */
        }

        /* Hover effect for buttons */
        .toggle-buttons button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }

        /* Specific colors for each button */
        #pendingButton.active {
            background-color: #e67e22; /* Orange for Pending Requests */
            box-shadow: 0 4px 6px rgba(230, 126, 34, 0.3);
        }

        #approvedButton.active {
            background-color: #2ecc71; /* Green for Approved Requests */
            box-shadow: 0 4px 6px rgba(46, 204, 113, 0.3);
        }

        /* Disabled state */
        .toggle-buttons button:disabled {
            background-color: #bdc3c7; /* Gray for disabled state */
            color: #7f8c8d;
            cursor: not-allowed;
            box-shadow: none;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">PayDay</div>
            <ul class="nav-links">
                
                <li><a href="employees.php">Employees</a></li>
                <li><a href="salaryandtax.php">Salary and Tax Details</a></li>
                <li><a href="leaverequests.php">Leave Requests</a></li>
                <li><a href="allbillhistory.php">Recent Bills</a></li>
                
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            </ul>
            <div class="menu-toggle" onclick="toggleMenu()">&#9776;</div>
        </nav>
    </header>