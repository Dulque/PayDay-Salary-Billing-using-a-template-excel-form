<?php
ob_start();
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}
include("aheader.php"); // Include your PDO database connection file

// Fetch distinct designations from the user_details table
$sqlDesignations = "SELECT DISTINCT Designation FROM user_details";
$stmtDesignations = $pdo->query($sqlDesignations);
$designations = $stmtDesignations->fetchAll(PDO::FETCH_COLUMN);

// Check if an employee ID is passed in the URL
$employee_id = $_GET['id'];

// Fetch employee details from the database
try {
    $sql = "SELECT * FROM user_details WHERE user_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        header("Location: employees.php"); // Redirect if no employee is found
        exit();
    }

    // Encode the photo as base64 for display
    $photoBase64 = base64_encode($employee['Photo']);
} catch (PDOException $e) {
    die("Error fetching employee details: " . $e->getMessage());
}

// Handle form submission for updating employee details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve updated data from the form and convert to uppercase where necessary
    $full_name = strtoupper($_POST['full_name']);
    $department = strtoupper($_POST['department']);
    $designation = strtoupper($_POST['designation']);
    $mode_of_service = strtoupper($_POST['mode_of_service']);
    $phone_number = $_POST['phone_number'];
    $email = $_POST['email'];
    $started_year = $_POST['started_year'];
    $organization_name = $_POST['organization_name'];
    $account_number = $_POST['account_number'];
    $ifsc_code = $_POST['ifsc_code'];
    $pan_number = $_POST['pan_number'];
    $branch = $_POST['branch'];

    // Handle photo upload (if a new photo is provided)
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        // Read the photo file as binary data
        $photo_data = file_get_contents($_FILES['photo']['tmp_name']);
    } else {
        // Keep the existing photo if no new one is uploaded
        $photo_data = $employee['Photo'];
    }

    try {
        // If the designation is "OTHER", insert the new designation into the salary_tax_details table
        if ($designation === 'OTHER') {
            $new_designation = strtoupper($_POST['other_designation']);
            if (!empty($new_designation)) {
                // Check if the new designation already exists in the salary_tax_details table
                $sqlCheckDesignation = "SELECT COUNT(*) FROM salary_tax_details WHERE designation = :designation";
                $stmtCheckDesignation = $pdo->prepare($sqlCheckDesignation);
                $stmtCheckDesignation->execute([':designation' => $new_designation]);
                $designationExists = $stmtCheckDesignation->fetchColumn();

                // If the designation does not exist, insert it into the salary_tax_details table
                if (!$designationExists) {
                    $sqlInsertDesignation = "INSERT INTO salary_tax_details (designation, salary, it_percentage, epf, esi, pt, other_deductions, created_at)
                                             VALUES (:designation, 0, 0, 0, 0, 0, 0, NOW())";
                    $stmtInsertDesignation = $pdo->prepare($sqlInsertDesignation);
                    $stmtInsertDesignation->execute([':designation' => $new_designation]);
                }

                // Use the new designation for the user_details table
                $designation = $new_designation;
            } else {
                throw new Exception("New designation is required.");
            }
        }

        // Update the employee details in the user_details table
        $sql = "UPDATE user_details 
                SET Name = :full_name, 
                    Department = :department, 
                    Designation = :designation, 
                    mode_of_service = :mode_of_service, 
                    Phone_Number = :phone_number, 
                    Email = :email, 
                    startedyear = :started_year, 
                    org_name = :organization_name, 
                    Photo = :photo, 
                    ac_no = :account_number, 
                    ifsc_code = :ifsc_code, 
                    pan_no = :pan_number, 
                    bank_branch = :branch 
                WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'full_name' => $full_name,
            'department' => $department,
            'designation' => $designation,
            'mode_of_service' => $mode_of_service,
            'phone_number' => $phone_number,
            'email' => $email,
            'started_year' => $started_year,
            'organization_name' => $organization_name,
            'photo' => $photo_data,
            'account_number' => $account_number,
            'ifsc_code' => $ifsc_code,
            'pan_number' => $pan_number,
            'branch' => $branch,
            'user_id' => $employee_id
        ]);
        echo "<script>alert('Employee Details Updated Successfully!!');</script>";
        // Redirect to the employees page with a success message
        header("Location: employees.php?success=1");
        exit();
    } catch (PDOException $e) {
        die("Error updating employee details: " . $e->getMessage());
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }
        .newmember-form {
            position: relative;
            z-index: 2;
            background: white;
            padding: 20px;
            max-width: 500px;
            margin: 20px auto;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
    <div class="overlay"></div>
    <div class="newmember-form">
        <h2>Edit Employee</h2>
        <form action="editemployee.php?id=<?php echo $employee_id; ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($employee['Name']); ?>" oninput="this.value = this.value.toUpperCase()" required>
            </div>
            <div class="form-group">
                <label for="photo">Photo:</label>
                <input type="file" id="photo" name="photo" accept="image/*">
                <small>Current Photo: <a href="data:image/jpg;base64,<?php echo $photoBase64; ?>" target="_blank">View Photo</a></small>
            </div>
            <div class="form-group">
                <label for="designation">Designation:</label>
                <select id="designation" name="designation" required>
                    <?php foreach ($designations as $designation): ?>
                        <option value="<?php echo $designation; ?>" <?php echo ($designation === $employee['Designation']) ? 'selected' : ''; ?>><?php echo $designation; ?></option>
                    <?php endforeach; ?>
                    <option value="OTHER">Other</option>
                </select>
                <input type="text" id="other_designation" name="other_designation" style="display: none;" placeholder="Enter new designation" oninput="this.value = this.value.toUpperCase()">
            </div>
            <div class="form-group">
                <label for="mode_of_service">Mode of Service:</label>
                <select id="mode_of_service" name="mode_of_service" required>
                    <option value="CONTRACT" <?php echo ($employee['mode_of_service'] === 'CONTRACT') ? 'selected' : ''; ?>>Contract</option>
                    <option value="GUEST" <?php echo ($employee['mode_of_service'] === 'GUEST') ? 'selected' : ''; ?>>Guest</option>
                </select>
            </div>
            <div class="form-group">
                <label for="department">Department:</label>
                <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($employee['Department']); ?>" oninput="this.value = this.value.toUpperCase()" required>
            </div>
            <div class="form-group">
                <label for="phone_number">Phone Number:</label>
                <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($employee['Phone_Number']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($employee['Email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="started_year">Date of Joining</label>
                <input type="date" id="started_year" name="started_year" value="<?php echo htmlspecialchars($employee['startedyear']); ?>" required>
            </div>
            <div class="form-group">
                <label for="organization_name">Organization Name:</label>
                <input type="text" id="organization_name" name="organization_name" value="<?php echo htmlspecialchars($employee['org_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="account_number">A/C No:</label>
                <input type="text" id="account_number" name="account_number" value="<?php echo htmlspecialchars($employee['ac_no']); ?>" required>
            </div>
            <div class="form-group">
                <label for="ifsc_code">IFSC Code:</label>
                <input type="text" id="ifsc_code" name="ifsc_code" value="<?php echo htmlspecialchars($employee['ifsc_code']); ?>" required>
            </div>
            <div class="form-group">
                <label for="pan_number">PAN No:</label>
                <input type="text" id="pan_number" name="pan_number" value="<?php echo htmlspecialchars($employee['pan_no']); ?>" required>
            </div>
            <div class="form-group">
                <label for="branch">Branch:</label>
                <input type="text" id="branch" name="branch" value="<?php echo htmlspecialchars($employee['bank_branch']); ?>" required>
            </div>
            <div class="form-group">
                <button type="submit">Update Employee</button>
            </div>
        </form>
    </div>
    <script>
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

        // Initialize the "Other" field if the current designation is not in the list
        const currentDesignation = "<?php echo $employee['Designation']; ?>";
        if (!designationSelect.querySelector(`option[value="${currentDesignation}"]`)) {
            designationSelect.value = 'OTHER';
            otherDesignationInput.style.display = 'block';
            otherDesignationInput.value = currentDesignation;
        }
    </script>
</body>
</html>