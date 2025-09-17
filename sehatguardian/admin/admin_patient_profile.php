<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'sehat');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all patients from users table
$patients = $conn->query("SELECT user_id, username, email FROM users WHERE role='patient' ORDER BY username ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Patient List</title>
    <style>
        body {
            background: #e0f7fa;
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .container {
            max-width: 900px;
            background: #fff;
            padding: 30px 40px;
            margin: auto;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,128,128,0.1);
        }
        h2 {
            color: #009688;
            text-align: center;
            margin-bottom: 25px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #b2dfdb;
            text-align: left;
        }
        th {
            background: #009688;
            color: white;
        }
        tr:hover {
            background: #b2dfdb;
        }
        a.view-btn {
            background-color: #01a9b4;
            color: white;
            padding: 7px 14px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.25s ease;
        }
        a.view-btn:hover {
            background-color: #00796b;
        }
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            color: #01a9b4;
            text-decoration: none;
            font-weight: 700;
            border: 2px solid #01a9b4;
            padding: 6px 16px;
            border-radius: 5px;
            transition: all 0.25s;
        }
        .back-btn:hover {
            background-color: #01a9b4;
            color: white;
        }
    </style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="back-btn">&#8592; Back to Dashboard</a>
    <h2>Patient List</h2>
    <table>
        <thead>
            <tr>
                <th>Patient Name</th>
                <th>Email</th>
                <th>View Profile</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($patient = $patients->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($patient['username']) ?></td>
                <td><?= htmlspecialchars($patient['email']) ?></td>
                <td><a class="view-btn" href="admin_view_patient_profile.php?patient_id=<?= $patient['user_id'] ?>">View Profile</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
