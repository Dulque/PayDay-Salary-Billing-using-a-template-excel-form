<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

include 'aheader.php';

// Fetch distinct designations from the user_details table
try {
    $stmt = $pdo->prepare("SELECT DISTINCT Designation FROM user_details");
    $stmt->execute();
    $designations = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Error fetching designations: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $designation = $_POST['designation'];
    $salary = $_POST['salary'];
    $it_percentage = $_POST['it_percentage'];
    $epf = $_POST['epf'];
    $esi = !empty($_POST['esi']) ? $_POST['esi'] : 0; // Set to 0 if empty
    $pt = !empty($_POST['pt']) ? $_POST['pt'] : 0; // Set to 0 if empty
     // Set to 0 if empty
    $other = !empty($_POST['other']) ? $_POST['other'] : 0; // Set to 0 if empty

    // Validate required fields
    if (empty($designation) || empty($salary) || empty($it_percentage) || empty($epf)) {
        echo "<script>alert('Designation, Salary, IT Percentage, and EPF are required fields.');</script>";
    } else {
        try {
            // Insert or update data in the salary_tax_details table
            $sql = "INSERT INTO salary_tax_details (designation, salary, it_percentage, epf, esi, pt, lwa, other_deductions)
                    VALUES (:designation, :salary, :it_percentage, :epf, :esi, :pt, :other)
                    ON DUPLICATE KEY UPDATE
                    salary = VALUES(salary),
                    it_percentage = VALUES(it_percentage),
                    epf = VALUES(epf),
                    esi = VALUES(esi),
                    pt = VALUES(pt),
                    
                    other_deductions = VALUES(other_deductions)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                
                ':designation' => $designation,
                ':salary' => $salary,
                ':it_percentage' => $it_percentage,
                ':epf' => $epf,
                ':esi' => $esi,
                ':pt' => $pt,
                
                ':other' => $other
            ]);
            echo "<script>
                    alert('Salary and Tax Details Saved Successfully!');
                    window.location.href = 'salaryandtax.php';
                  </script>";
        } catch (PDOException $e) {
            // Log the error and display a user-friendly message
            error_log("Error saving salary and tax details: " . $e->getMessage());
            echo "<script>alert('An error occurred while saving the details. Please try again.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary and Tax Details</title>
    <style>
        .content {
            padding: 20px;
        }
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        .form-group button {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .form-group button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="content">
        <div id="salaryTaxDetails" class="form-container">
            <h2>Salary and Tax Details</h2>
            <form id="salaryTaxForm" method="POST">
                <div class="form-group">
                    <label for="designation">Designation:</label>
                    <select id="designation" name="designation" required>
                        <option value="">Select Designation</option>
                        <?php foreach ($designations as $designation): ?>
                            <option value="<?php echo htmlspecialchars($designation); ?>"><?php echo htmlspecialchars($designation); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="salary">Salary:</label>
                    <input type="number" id="salary" name="salary" placeholder="Enter Salary" required>
                </div>
                <div class="form-group">
                    <label for="it_percentage">IT Percentage:</label>
                    <input type="number" id="it_percentage" name="it_percentage" placeholder="Enter IT Percentage" step="0.01" min="0" max="100" required>
                </div>
                <div class="form-group">
                    <label for="epf">EPF @12% (Max Rs 1800):</label>
                    <input type="number" id="epf" name="epf" placeholder="Enter EPF" required>
                </div>
                <div class="form-group">
                    <label for="esi">ESI (0.75%):</label>
                    <input type="number" id="esi" name="esi" placeholder="Enter ESI (optional)">
                </div>
                <div class="form-group">
                    <label for="pt">PT:</label>
                    <input type="number" id="pt" name="pt" placeholder="Enter PT (optional)">
                </div>
               
                <div class="form-group">
                    <label for="other">Other Deductions:</label>
                    <input type="number" id="other" name="other" placeholder="Enter Other Deductions (optional)">
                </div>
                <div class="form-group">
                    <button type="submit">Save Details</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const navLinks = document.querySelector('.nav-links');
            navLinks.classList.toggle('active');
        }

    
    </script>
</body>
</html>