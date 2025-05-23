<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayDay - Salary Billing System</title>
    <link rel="stylesheet" href="styles.css">
    <script defer src="script.js"></script>
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f4f4;
            overflow-x: hidden;
        }
        header {
            background: #333;
            color: white;
            width: 100%;
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 1000;
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
        html {
            scroll-behavior: smooth;
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
        .hero {
            text-align: center;
            padding: 8rem 1rem;
            width: 100%;
            background-image: url('photos/pexels-karolina-grabowska-4475523.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            height: 50vh;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.1);
            z-index: 1;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            animation: slideUp 1.5s ease-in-out;
        }
        .contact-section {
            padding: 16rem 8rem;
            text-align: center;
            background: white;
            height: 100%;
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .contact-section h2 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: #333;
        }
        .contact-section p {
            font-size: 1.25rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }
        .contact-details {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 2rem;
        }
        .contact-details .detail {
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 200px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .contact-details .detail:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        .contact-details .detail i {
            font-size: 2rem;
            color: #ff9800;
            margin-bottom: 1rem;
        }
        .contact-details .detail h3 {
            font-size: 1.25rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .contact-details .detail p {
            font-size: 1rem;
            color: #666;
        }
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: fadeIn 2s ease-in-out;
        }
        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            animation: fadeIn 2.5s ease-in-out;
        }
        .hero .btn {
            display: inline-block;
            background: #ff9800;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            color: white;
            border-radius: 5px;
            margin-top: 1rem;
            transition: background 0.3s ease, transform 0.3s ease;
            animation: pulse 2s infinite;
        }
        .hero .btn:hover {
            background: #e68900;
            transform: scale(1.05);
        }
        .menu-toggle {
            display: none;
            font-size: 2rem;
            cursor: pointer;
            color: #ff9800;
        }
        .features, .about-section, .contact-section {
            padding: 8rem 1rem;
            width: 100%;
            background: white;
            text-align: center;
        }
        .features h2, .about-section h2, .contact-section h2 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: #333;
            animation: fadeIn 2s ease-in-out;
        }
        .feature-cards, .features, .contact-details {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }
        .feature-card, .feature, .detail {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 300px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeIn 2s ease-in-out;
        }
        .feature-card:hover, .feature:hover, .detail:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
        }
        .feature-card img {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
        }
        .feature-card h3, .feature h3, .detail h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #007bff;
        }
        .feature-card p, .feature p, .detail p {
            font-size: 1rem;
            color: #666;
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
            .feature-cards, .features, .contact-details {
                flex-direction: column;
                align-items: center;
            }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .about-section {
            padding: 4rem 1rem;
            text-align: center;
            background: white;
            max-width: 100%;
            margin: 0;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .about-section h2 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: #333;
        }
        .about-section p {
            font-size: 1.25rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }
        .features {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 2rem;
        }
        .feature {
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 200px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .feature:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        .feature i {
            font-size: 2rem;
            color: #ff9800;
            margin-bottom: 1rem;
        }
        .feature h3 {
            font-size: 1.25rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .feature p {
            font-size: 1rem;
            color: #666;
        }
    </style>
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">PayDay</div>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="login.php" class="login-btn">Login</a></li>
            </ul>
            <div class="menu-toggle" onclick="toggleMenu()">&#9776;</div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>Welcome to PayDay</h1>
            <p>Effortless Salary Management System for Your Business</p>
            <a href="login.php" class="btn">Get Started</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <h2>Key Features</h2>
        <div class="feature-cards">
            <div class="feature-card">
                <img src="photos/3144456.png" alt="Automated Payroll">
                <h3>Automated Payroll</h3>
                <p>Streamline your payroll process with automated calculations and timely payments.</p>
            </div>
            <div class="feature-card">
                <img src="photos/3144475.png" alt="Employee Management">
                <h3>Employee Management</h3>
                <p>Easily manage employee data, roles, and access permissions in one place.</p>
            </div>
            <div class="feature-card">
                <img src="photos/3144484.png" alt="Real-Time Reports">
                <h3>Real-Time Reports</h3>
                <p>Generate real-time reports for payroll, taxes, and employee performance.</p>
            </div>
            <div class="feature-card">
                <img src="photos/3144497.png" alt="Secure & Compliant">
                <h3>Secure & Compliant</h3>
                <p>Ensure data security and compliance with industry standards and regulations.</p>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section" id="about">
        <h2>About Us</h2>
        <p>
            PayDay is a simple and user-friendly salary billing system designed to help small and medium-sized businesses 
            manage their payroll efficiently. Our goal is to make payroll processing easy, accurate, and hassle-free.
        </p>
        <div class="features">
            <div class="feature">
                <i class="fas fa-calculator"></i>
                <h3>Easy Calculations</h3>
                <p>Automate salary calculations with minimal effort.</p>
            </div>
            <div class="feature">
                <i class="fas fa-user-friends"></i>
                <h3>Employee Management</h3>
                <p>Easily manage employee data and records.</p>
            </div>
            <div class="feature">
                <i class="fas fa-file-invoice"></i>
                <h3>Real-Time Reports</h3>
                <p>Generate and download payroll reports instantly.</p>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section" id="contact">
        <h2>Contact Us</h2>
        <p>
            Have any questions or need support? Feel free to reach out to us. Our team is here to help you with any inquiries.
        </p>
        <div class="contact-details">
            <div class="detail">
                <i class="fas fa-phone"></i>
                <h3>Phone Number</h3>
                <p>+91 9656716422</p>
            </div>
            <div class="detail">
                <i class="fas fa-envelope"></i>
                <h3>Email</h3>
                <p>payday@gmail.com</p>
            </div>
        </div>
    </section>

    <script>
        function toggleMenu() {
            const navLinks = document.querySelector('.nav-links');
            navLinks.classList.toggle('active');
        }
    </script>
</body>
</html>