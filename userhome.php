<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}
include("header.php");

// Fetch user details from the database
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM user_details WHERE user_ID = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $photoBase64 = base64_encode($user['Photo']);
    $user['Photo'] = 'data:image/jpg;base64,' . $photoBase64;
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Home Page</title>
    <div class="overlay"></div>
    <style>
        /* Global Styles */
        body {
             margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background-image: url('photos/pexels-karolina-grabowska-4475523.jpg');
            background-size: cover;
            background-position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #0f0e0e;
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
        .content {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 20px;
            text-align: left;
        }

        
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .employee-photo {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }

        .employee-photo img {
            max-width: 150px;
            border-radius: 50%;
            border: 4px solid #3498db;
        }

        .edit-icon {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: #3498db;
            color: white;
            border-radius: 50%;
            padding: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .edit-icon:hover {
            background: #2980b9;
        }

        .actions {
            text-align: center;
            margin-top: 20px;
        }

        .actions button {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 10px;
            transition: background 0.3s ease;
        }

        .actions button:hover {
            background-color: #2980b9;
        }

        .actions button.print {
            background-color: #28a745;
        }

        .actions button.print:hover {
            background-color: #218838;
        }

        .actions button.leave-management {
            background-color: #6b5b95;
        }

        .actions button.leave-management:hover {
            background-color: #5a4a7d;
        }

        .actions button.generate-bill {
            background-color: #f39c12;
        }

        .actions button.generate-bill:hover {
            background-color: #e67e22;
        }

        /* Print-specific Styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .content {
                box-shadow: none;
                border: none;
            }

            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="content">
        <h2>User Details</h2>
        <div class="employee-photo">
            <img src="<?php echo htmlspecialchars($user['Photo']); ?>" alt="User Photo">
            <div class="edit-icon" onclick="openPhotoUpload()">✎</div>
        </div>
        <table>
            <tr>
                <th>Name</th>
                <td><?php echo htmlspecialchars($user['Name']); ?></td>
            </tr>
            <tr>
                <th>Designation</th>
                <td><?php echo htmlspecialchars($user['Designation']); ?></td>
            </tr>
            <tr>
                <th>Department</th>
                <td><?php echo htmlspecialchars($user['Department']); ?></td>
            </tr>
            <tr>
                <th>Phone Number</th>
                <td><?php echo htmlspecialchars($user['Phone_Number']); ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?php echo htmlspecialchars($user['Email']); ?></td>
            </tr>
            <tr>
                <th>Date of Joining</th>
                <td><?php echo htmlspecialchars($user['startedyear']); ?></td>
            </tr>
            <tr>
                <th>Account Number</th>
                <td><?php echo htmlspecialchars($user['ac_no']); ?></td>
            </tr>
            <tr>
                <th>IFSC Code</th>
                <td><?php echo htmlspecialchars($user['ifsc_code']); ?></td>
            </tr>
            <tr>
                <th>PAN Number</th>
                <td><?php echo htmlspecialchars($user['pan_no']); ?></td>
            </tr>
            <tr>
                <th>Bank Branch</th>
                <td><?php echo htmlspecialchars($user['bank_branch']); ?></td>
            </tr>
        </table>
        <div class="actions">
            <button onclick="window.print()" class="print">Print Details</button>
            <button class="leave-management" onclick="window.location.href='leavemanagement.php';">Leave Management</button>
            <button class="generate-bill" onclick="window.location.href='billgenerated.php';">Generate Bill</button>
        </div>
    </div>

    <!-- Photo Upload Modal -->
    <div id="photoUploadModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2); z-index: 1000;">
        <h3>Upload New Photo</h3>
        <form id="photoUploadForm" enctype="multipart/form-data">
            <input type="file" name="photo" accept="image/*" required>
            <button id=new type="submit">Upload</button>
            <button type="button" onclick="closePhotoUpload()">Cancel</button>
        </form>
    </div>

    <script>
        // Open photo upload modal
        function openPhotoUpload() {
            document.getElementById('photoUploadModal').style.display = 'block';
        }

        // Close photo upload modal
        function closePhotoUpload() {
            document.getElementById('photoUploadModal').style.display = 'none';
        }

        // Handle photo upload
        document.getElementById('photoUploadForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);

            const response = await fetch('upload_photo.php', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    alert('Photo updated successfully!');
                    window.location.reload();
                } else {
                    alert('Failed to upload photo.');
                }
            } else {
                alert('Failed to upload photo.');
            }
        });
    </script>
</body>
</html>

<?php
include("footer.php");
?>