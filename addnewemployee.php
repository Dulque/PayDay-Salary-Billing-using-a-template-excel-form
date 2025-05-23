<?php
ob_start();
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

// Fetch distinct designations from the user_details table
$sql = "SELECT DISTINCT Designation FROM user_details";
$stmt = $pdo->query($sql);
$designations = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data and convert to uppercase where necessary
    $name = strtoupper($_POST['name']);
    $designation = strtoupper($_POST['designation']);
    $department = strtoupper($_POST['department']);
    $phone_number = $_POST['phone_number'];
    $email = $_POST['email'];
    $started_year = $_POST['started_year'];
    $organization_name = $_POST['organization_name'];
    $account_number = $_POST['account_number'];
    $ifsc_code = $_POST['ifsc_code'];
    $pan_number = $_POST['pan_number'];
    $branch = $_POST['branch'];
    $mode_of_service = strtoupper($_POST['mode_of_service']); // New field
    $photo_data = null;

    // Handle file upload (photo)
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        // Read the photo file as binary data
        $photo_data = @file_get_contents($_FILES['photo']['tmp_name']) ?? null;
    }

    // Generate a random password
    $password = bin2hex(random_bytes(8)); // Generates a 16-character password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT); // Hash the password

    // Insert data into the user_details table
    try {
        $pdo->beginTransaction(); // Start a transaction

        // If the designation is "OTHER", insert the new designation into the salary_tax_details table
        if ($designation === 'OTHER') {
            $new_designation = strtoupper($_POST['other_designation']);
            if (!empty($new_designation)) {
                // Insert the new designation into the salary_tax_details table
                $sql0 = "INSERT INTO salary_tax_details (designation,salary,it_percentage,epf,esi,pt,other_deductions,created_at)
                         VALUES (:designation, 0, 0, 0, 0, 0, 0, NOW())";
                $stmt0 = $pdo->prepare($sql0);
                $stmt0->execute([':designation' => $new_designation]);

                // Use the new designation for the user_details table
                $designation = $new_designation;
            } else {
                throw new Exception("New designation is required.");
            }
        }

        // Insert into user_details table
        $sql1 = "INSERT INTO user_details (Name, Photo, Designation, Department, Phone_Number, Email, startedyear, org_name, ac_no, ifsc_code, pan_no, bank_branch, mode_of_service)
                 VALUES (:name, :photo, :designation, :department, :phone_number, :email, :started_year, :organization_name, :account_number, :ifsc_code, :pan_number, :branch, :mode_of_service)";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute([
            ':name' => $name,
            ':photo' => $photo_data, // Store binary data directly
            ':designation' => $designation,
            ':department' => $department,
            ':phone_number' => $phone_number,
            ':email' => $email,
            ':started_year' => $started_year,
            ':organization_name' => $organization_name,
            ':account_number' => $account_number,
            ':ifsc_code' => $ifsc_code,
            ':pan_number' => $pan_number,
            ':branch' => $branch,
            ':mode_of_service' => $mode_of_service
        ]);

        // Get the last inserted user_ID
        $user_ID = $pdo->lastInsertId();
        $role = "User";

        // Insert into users table
        $sql2 = "INSERT INTO users (user_ID, password, role)
                 VALUES (:user_ID, :password, :role)";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([
            ':user_ID' => $user_ID,
            ':password' => $hashed_password,
            ':role' => $role
        ]);

        $pdo->commit(); // Commit the transaction

        // Send email with user_ID and password using PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Server settings for Gmail
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
            $mail->SMTPAuth = true; // Enable SMTP authentication
            $mail->Username = 'yourr email'; // Your Gmail address
            $mail->Password = 'yourr app passworrd'; // Your App Password
            $mail->SMTPSecure = 'ssl'; // Use SSL encryption
            $mail->Port = 465; // SMTP port for SSL

            // Recipients
            $mail->setFrom('yourr email', 'PayDay');
            $mail->addAddress($email, $name); // Add the new member's email

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Account Details';
            $mail->Body = "Dear $name,<br><br>
                           Your account has been created successfully.<br>
                           User ID: $user_ID<br>
                           Password: $password<br><br>
                           Please log in and change your password immediately.<br><br>
                           Best regards,<br>PayDay";

            $mail->send();
            echo "<script>alert('Employee added successfully! Login details sent via email.');</script>";
            header("Location:employees.php");
        } catch (Exception $e) {
            echo "<script>alert('Employee added successfully, but failed to send email. Error: " . $mail->ErrorInfo . "');</script>";
        }
    } catch (PDOException $e) {
        $pdo->rollBack(); // Rollback the transaction on error
        die("Database error: " . $e->getMessage());
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
    <title>Add New Member</title>
    <style>
        .content {
            padding: 20px;
        }
        .newmember-form {
            max-width: 500px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        .form-group button {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }
        .form-group button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="newmember-form" id="newmember-form">
            <h2>Add New Member</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" oninput="this.value = this.value.toUpperCase()" required>
                </div>
                <div class="form-group">
                    <label for="photo">Photo:</label>
                    <input type="file" id="photo" name="photo" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="designation">Designation:</label>
                    <select id="designation" name="designation" required>
                        <?php foreach ($designations as $designation): ?>
                            <option value="<?php echo $designation; ?>"><?php echo $designation; ?></option>
                        <?php endforeach; ?>
                        <option value="OTHER">Other</option>
                    </select>
                    <input type="text" id="other_designation" name="other_designation" style="display: none;" placeholder="Enter new designation" oninput="this.value = this.value.toUpperCase()">
                </div>
                <div class="form-group">
                    <label for="mode_of_service">Mode of Service:</label>
                    <select id="mode_of_service" name="mode_of_service" required>
                        <option value="CONTRACT">Contract</option>
                        <option value="GUEST">Guest</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="department">Department:</label>
                    <input type="text" id="department" name="department" oninput="this.value = this.value.toUpperCase()" required>
                </div>
                <div class="form-group">
                    <label for="phone_number">Phone Number:</label>
                    <input type="text" id="phone_number" name="phone_number" required>
                </div>
                <div class="form-group">
                    <label for="email">Email ID:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="started_year">Date of Joining</label>
                    <input type="date" id="started_year" name="started_year" required>
                </div>
                <div class="form-group">
                    <label for="organization_name">Organization Name:</label>
                    <input type="text" id="organization_name" name="organization_name" required>
                </div>
                <div class="form-group">
                    <label for="account_number">A/C No:</label>
                    <input type="text" id="account_number" name="account_number" required>
                </div>
                <div class="form-group">
                    <label for="ifsc_code">IFSC Code:</label>
                    <input type="text" id="ifsc_code" name="ifsc_code" required>
                </div>
                <div class="form-group">
                    <label for="pan_number">PAN No:</label>
                    <input type="text" id="pan_number" name="pan_number" required>
                </div>
                <div class="form-group">
                    <label for="branch">Branch:</label>
                    <input type="text" id="branch" name="branch" required>
                </div>
                <div class="form-group">
                    <button type="submit">Add Member</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function toggleMenu() {
            const navLinks = document.querySelector('.nav-links');
            navLinks.classList.toggle('active');
        }

        // Show/hide the "Other" designation input field
        const designationSelect = document.getElementById('designation');
        const otherDesignationInput = document.getElementById('other_designation');

        designationSelect.addEventListener('change', function () {
            if (this.value === 'OTHER') {
                otherDesignationInput.style.display = 'block';
                otherDesignationInput.setAttribute('required', true);
            } else {
                otherDesignationInput.style.display = 'none';
                otherDesignationInput.removeAttribute('required');
            }
        });
    </script>
</body>
</html>