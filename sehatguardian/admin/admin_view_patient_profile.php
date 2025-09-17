<?php
$conn = new mysqli('localhost', 'root', '', 'sehat');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['patient_id'])) {
    die("Patient ID is required.");
}

$patient_id = intval($_GET['patient_id']);
if ($patient_id <= 0) {
    die("Invalid Patient ID.");
}

// Fetch patient user info
$user_stmt = $conn->prepare("SELECT username, email, phone FROM users WHERE user_id = ? AND role='patient'");
$user_stmt->bind_param("i", $patient_id);
$user_stmt->execute();
$user_res = $user_stmt->get_result();

if ($user_res->num_rows === 0) {
    die("Patient not found in users table.");
}

$user = $user_res->fetch_assoc();

// Fetch patient profile info (no age, but with date_of_birth)
$profile_stmt = $conn->prepare("SELECT date_of_birth, gender, contact_number, address, weight, height, blood_group, allergies, emergency_contact_name, emergency_contact_number, emergency_contact_relation FROM patient_profile WHERE patient_id = ?");

$profile_stmt->bind_param("i", $patient_id);
$profile_stmt->execute();
$profile_res = $profile_stmt->get_result();

$profile = $profile_res->num_rows > 0 ? $profile_res->fetch_assoc() : null;

// Calculate age from date_of_birth
$age = "";
if ($profile && !empty($profile['date_of_birth'])) {
    $dob = new DateTime($profile['date_of_birth']);
    $today = new DateTime();
    $age = $today->diff($dob)->y;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Profile - <?= htmlspecialchars($user['username']) ?></title>
    <style>
        body {
            background: #e0f7fa;
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .container {
            max-width: 700px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #b2dfdb;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #009688;
            color: white;
            width: 220px;
        }
    </style>
</head>
<body>
<div class="container">
    <a href="admin_patient_profile.php" class="back-btn">&#8592; Back to Patient List</a>
    <h2>Profile of <?= htmlspecialchars($user['username']) ?></h2>
    <table>
        <tr>
            <th>Name</th>
            <td><?= htmlspecialchars($user['username']) ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><?= htmlspecialchars($user['email']) ?></td>
        </tr>
        <tr>
            <th>Phone</th>
            <td><?= htmlspecialchars($user['phone']) ?></td>
        </tr>
        <?php if ($profile): ?>
            <tr>
                <th>Date of Birth</th>
                <td><?= htmlspecialchars($profile['date_of_birth']) ?></td>
            </tr>
            <tr>
                <th>Age</th>
                <td><?= htmlspecialchars($age) ?></td>
            </tr>
            <tr>
                <th>Gender</th>
                <td><?= htmlspecialchars($profile['gender']) ?></td>
            </tr>
            <tr>
                <th>Contact Number</th>
                <td><?= htmlspecialchars($profile['contact_number']) ?></td>
            </tr>
            <tr>
                <th>Address</th>
                <td><?= nl2br(htmlspecialchars($profile['address'])) ?></td>
            </tr>
            <tr>
                <th>Weight (kg)</th>
                <td><?= htmlspecialchars($profile['weight']) ?></td>
            </tr>
            <tr>
                <th>Height (cm)</th>
                <td><?= htmlspecialchars($profile['height']) ?></td>
            </tr>
            <tr>
                <th>Blood Group</th>
                <td><?= htmlspecialchars($profile['blood_group']) ?></td>
            </tr>
            <tr>
                <th>Allergies</th>
                <td><?= nl2br(htmlspecialchars($profile['allergies'])) ?></td>
            </tr>
            <tr>
                <th>Emergency Contact Person</th>
                <td><?= htmlspecialchars($profile['emergency_contact_name']) ?></td>
            </tr>
            <tr>
                <th>Emergency Contact Number</th>
                <td><?= htmlspecialchars($profile['emergency_contact_number']) ?></td>
            </tr>
            <tr>
                <th>Relation to Patient</th>
                <td><?= htmlspecialchars($profile['emergency_contact_relation']) ?></td>
            </tr>
            
        <?php else: ?>
            <tr><td colspan="2" style="text-align:center; color:#888;">No profile data available.</td></tr>
        <?php endif; ?>
    </table>
</div>
</body>
</html>
