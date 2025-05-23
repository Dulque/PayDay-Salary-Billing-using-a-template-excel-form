<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}
include 'aheader.php';

// Fetch employees from the database using PDO
$sql = "SELECT u.user_ID, ud.Name, ud.Department, ud.Designation, ud.mode_of_service 
        FROM users u 
        JOIN user_details ud ON u.user_ID = ud.user_ID 
        WHERE u.role = 'User'";
$stmt = $pdo->query($sql);
$employees = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .content {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-box input, .search-box select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .search-box input {
            flex: 1;
        }

        .search-box select {
            width: 150px;
        }

        .add-member-btn {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .add-member-btn:hover {
            background-color: #218838;
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
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .actions button {
            padding: 8px 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
            transition: background 0.3s ease;
        }

        .actions button:hover {
            background-color: #2980b9;
        }

        .actions button.delete {
            background-color: #e74c3c;
        }

        .actions button.delete:hover {
            background-color: #c0392b;
        }

        .print-btn {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .print-btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="content">
        <div id="employees">
            <h2>Employees</h2>
            <div class="search-box">
                <input type="text" id="searchEmployee" placeholder="Search..." oninput="searchEmployees()">
                <select id="filterOption">
                    <option value="name">Name</option>
                    <option value="id">ID</option>
                    <option value="department">Department</option>
                    <option value="designation">Designation</option>
                    <option value="mode_of_service">Mode of Service</option>
                </select>
                <button class="add-member-btn" onclick="window.location.href='addnewemployee.php'">Add New Member</button>
                <button class="add-member-btn" onclick="window.location.href='upload_employee_excel.php'">Add New Members (Excel)</button>
            </div>
            <table id="employeeTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Mode of Service</th>
                        <th>Action</th>
                    </tr>                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr id="employee-<?php echo $employee['user_ID']; ?>">
                            <td><?php echo $employee['user_ID']; ?></td>
                            <td><a href="view_employee.php?id=<?php echo $employee['user_ID']; ?>"><?php echo $employee['Name']; ?></a></td>
                            <td><?php echo $employee['Department']; ?></td>
                            <td><?php echo $employee['Designation']; ?></td>
                            <td><?php echo $employee['mode_of_service']; ?></td>
                            <td class="actions">
                                <button onclick="window.location.href='editemployee.php?id=<?php echo $employee['user_ID']; ?>'">Edit</button>
                                <button class="delete" onclick="confirmDelete(<?php echo $employee['user_ID']; ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button class="print-btn" onclick="printEmployeeDetails()">Print Employee Details</button>
        </div>
    </div>

    <!-- Hidden input to store filtered employee IDs -->
    <input type="hidden" id="filteredEmployeeIds" name="filteredEmployeeIds">

    <script>
        function searchEmployees() {
            const searchTerm = document.getElementById("searchEmployee").value.toLowerCase();
            const filterOption = document.getElementById("filterOption").value;
            const rows = document.querySelectorAll("#employeeTable tbody tr");
            let filteredIds = [];

            rows.forEach(row => {
                const id = row.cells[0].textContent.toLowerCase();
                const name = row.cells[1].textContent.toLowerCase();
                const department = row.cells[2].textContent.toLowerCase();
                const designation = row.cells[3].textContent.toLowerCase();
                const modeOfService = row.cells[4].textContent.toLowerCase();

                let searchText;
                switch (filterOption) {
                    case "id":
                        searchText = id;
                        break;
                    case "name":
                        searchText = name;
                        break;
                    case "department":
                        searchText = department;
                        break;
                    case "designation":
                        searchText = designation;
                        break;
                    case "mode_of_service":
                        searchText = modeOfService;
                        break;
                    default:
                        searchText = name;
                }

                if (searchText.includes(searchTerm)) {
                    row.style.display = "";
                    filteredIds.push(row.cells[0].textContent); // Add ID to filteredIds array
                } else {
                    row.style.display = "none";
                }
            });

            // Update the hidden input field with the filtered employee IDs
            document.getElementById("filteredEmployeeIds").value = filteredIds.join(",");
        }

        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this employee?")) {
                deleteEmployee(id);
            }
        }

        function deleteEmployee(id) {
            if (confirm("Are you sure you want to delete this employee?")) {
                fetch('deleteemployee.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_ID=${id}`
                })
                .then(response => response.text())
                .then(data => {
                    if (data === "success") {
                        const row = document.getElementById(`employee-${id}`);
                        if (row) {
                            row.remove();
                        }
                        alert("Employee deleted successfully.");
                    } else {
                        alert("Error deleting employee: " + data);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred while deleting the employee.");
                });
            }
        }

        function printEmployeeDetails() {
            const searchTerm = document.getElementById("searchEmployee").value.trim();
            const filteredEmployeeIds = document.getElementById("filteredEmployeeIds").value;

            // If the search term is empty, pass all employee IDs
            if (searchTerm === "") {
                const allEmployeeIds = Array.from(document.querySelectorAll("#employeeTable tbody tr")).map(row => row.cells[0].textContent);
                window.location.href = `print_employee_details.php?employee_ids=${allEmployeeIds.join(",")}`;
            } else {
                window.location.href = `print_employee_details.php?employee_ids=${filteredEmployeeIds}`;
            }
        }
    </script>
</body>
</html>