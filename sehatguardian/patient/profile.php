<?php
session_start();

// For demo/testing: assume logged-in user_id is 3, replace with session user ID in real app
$logged_in_user_id = 3;

$conn = new mysqli('localhost', 'root', '', 'sehat');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$error = "";

// Default profile values
$profile = [
    'date_of_birth' => '',
    'gender' => '',
    'contact_number' => '',
    'address' => '',
    'weight' => '',
    'height' => '',
    'blood_group' => '',
    'allergies' => '',
    'emergency_contact_name' => '',
    'emergency_contact_number' => '',
    'emergency_contact_relation' => ''
];

$age = "";

// Check profile exists
$stmt = $conn->prepare("SELECT date_of_birth, gender, contact_number, address, weight, height, blood_group, allergies, emergency_contact_name, emergency_contact_number, emergency_contact_relation FROM patient_profile WHERE patient_id = ?");
$stmt->bind_param("i", $logged_in_user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile_exists = ($result->num_rows > 0);

if ($profile_exists) {
    $profile = $result->fetch_assoc();

    // Calculate age if DOB exists
    if (!empty($profile['date_of_birth'])) {
        $dob = new DateTime($profile['date_of_birth']);
        $today = new DateTime();
        $age = $today->diff($dob)->y;
    }
}

// Handle form submission (create or update)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $contact_number = trim($_POST['contact_number']);
    $address = trim($_POST['address']);
    $weight = floatval($_POST['weight']);
    $height = floatval($_POST['height']);
    $blood_group = $_POST['blood_group'];
    $allergies = trim($_POST['allergies']);
    $emergency_contact_name = trim($_POST['emergency_contact_name']);
    $emergency_contact_number = trim($_POST['emergency_contact_number']);
    $emergency_contact_relation = trim($_POST['emergency_contact_relation']);

    if ($profile_exists) {
        // Update
        $update_stmt = $conn->prepare("UPDATE patient_profile 
            SET date_of_birth=?, gender=?, contact_number=?, address=?, weight=?, height=?, blood_group=?, allergies=?, emergency_contact_name=?, emergency_contact_number=?, emergency_contact_relation=?
            WHERE patient_id=?");
        $update_stmt->bind_param("ssssddsssssi", $date_of_birth, $gender, $contact_number, $address, $weight, $height, $blood_group, $allergies, $emergency_contact_name, $emergency_contact_number, $emergency_contact_relation, $logged_in_user_id);
        if ($update_stmt->execute()) {
            $message = "Profile updated successfully!";
            $profile = [
                'date_of_birth' => $date_of_birth,
                'gender' => $gender,
                'contact_number' => $contact_number,
                'address' => $address,
                'weight' => $weight,
                'height' => $height,
                'blood_group' => $blood_group,
                'allergies' => $allergies,
                'emergency_contact_name' => $emergency_contact_name,
                'emergency_contact_number' => $emergency_contact_number,
                'emergency_contact_relation' => $emergency_contact_relation
            ];
            // Recalculate age
            if (!empty($date_of_birth)) {
                $dob = new DateTime($date_of_birth);
                $today = new DateTime();
                $age = $today->diff($dob)->y;
            }
        } else {
            $error = "Error updating profile: " . $update_stmt->error;
        }
    } else {
        // Create
        $insert_stmt = $conn->prepare("INSERT INTO patient_profile 
            (patient_id, date_of_birth, gender, contact_number, address, weight, height, blood_group, allergies, emergency_contact_name, emergency_contact_number, emergency_contact_relation) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("issssddsssss", $logged_in_user_id, $date_of_birth, $gender, $contact_number, $address, $weight, $height, $blood_group, $allergies, $emergency_contact_name, $emergency_contact_number, $emergency_contact_relation);
        if ($insert_stmt->execute()) {
            $message = "Profile created successfully!";
            $profile_exists = true;
            $profile = [
                'date_of_birth' => $date_of_birth,
                'gender' => $gender,
                'contact_number' => $contact_number,
                'address' => $address,
                'weight' => $weight,
                'height' => $height,
                'blood_group' => $blood_group,
                'allergies' => $allergies,
                'emergency_contact_name' => $emergency_contact_name,
                'emergency_contact_number' => $emergency_contact_number,
                'emergency_contact_relation' => $emergency_contact_relation
            ];
            // Recalculate age
            if (!empty($date_of_birth)) {
                $dob = new DateTime($date_of_birth);
                $today = new DateTime();
                $age = $today->diff($dob)->y;
            }
        } else {
            $error = "Error creating profile: " . $insert_stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Patient Profile</title>
<style>
    body {
        background: #e0f7fa;
        font-family: Arial, sans-serif;
        padding: 20px;
    }
    .container {
        max-width: 600px;
        background: #fff;
        padding: 30px 40px;
        margin: 20px auto;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0,128,128,0.1);
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
    h2 {
        color: #009688;
        text-align: center;
    }
    label {
        color: #00796b;
        font-weight: 600;
        display: block;
        margin-top: 15px;
    }
    input[type=text], input[type=number], input[type=date], select, textarea {
        width: 100%;
        padding: 10px;
        border: 1.5px solid #b2dfdb;
        border-radius: 5px;
        font-size: 16px;
        margin-top: 5px;
        box-sizing: border-box;
    }
    input[readonly] {
        background-color: #f5f5f5;
    }
    button {
        margin-top: 20px;
        background-color: #01a9b4;
        color: white;
        padding: 12px;
        border: none;
        border-radius: 6px;
        width: 100%;
        font-weight: 700;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    button:hover {
        background-color: #00796b;
    }
    .message {
        color: #00796b;
        font-weight: 700;
        text-align: center;
        margin-bottom: 15px;
    }
    .error {
        color: #c62828;
        font-weight: 700;
        text-align: center;
        margin-bottom: 15px;
    }
</style>
</head>
<body>

<div class="container">
    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>

    <h2>My Patient Profile</h2>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php elseif ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <label>Date of Birth:</label>
        <input type="date" name="date_of_birth" value="<?= htmlspecialchars($profile['date_of_birth']) ?>" required />

        <label>Age:</label>
        <input type="number" value="<?= htmlspecialchars($age) ?>" readonly />

        <label>Gender:</label>
        <select name="gender" required>
            <option value="">--Select--</option>
            <option value="Male" <?= $profile['gender']=='Male'?'selected':'' ?>>Male</option>
            <option value="Female" <?= $profile['gender']=='Female'?'selected':'' ?>>Female</option>
            <option value="Other" <?= $profile['gender']=='Other'?'selected':'' ?>>Other</option>
        </select>

        <label>Contact Number:</label>
        <input type="text" name="contact_number" value="<?= htmlspecialchars($profile['contact_number']) ?>" required />

        <label>Address:</label>
        <textarea name="address" rows="3"><?= htmlspecialchars($profile['address']) ?></textarea>

        <label>Weight (kg):</label>
        <input type="number" step="0.01" name="weight" value="<?= htmlspecialchars($profile['weight']) ?>" required />

        <label>Height (cm):</label>
        <input type="number" step="0.01" name="height" value="<?= htmlspecialchars($profile['height']) ?>" required />

        <label>Blood Group:</label>
        <select name="blood_group">
            <option value="">--Select--</option>
            <?php 
            $blood_groups = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
            foreach ($blood_groups as $bg) {
                $selected = ($profile['blood_group'] == $bg) ? 'selected' : '';
                echo "<option value='$bg' $selected>$bg</option>";
            }
            ?>
        </select>

        <label>Allergies:</label>
        <textarea name="allergies" rows="3"><?= htmlspecialchars($profile['allergies']) ?></textarea>

        <label>Emergency Contact Person:</label>
        <input type="text" name="emergency_contact_name" value="<?= htmlspecialchars($profile['emergency_contact_name']) ?>" required />

        <label>Emergency Contact Number:</label>
        <input type="text" name="emergency_contact_number" value="<?= htmlspecialchars($profile['emergency_contact_number']) ?>" required />

        <label>Relation to Patient:</label>
        <input type="text" name="emergency_contact_relation" value="<?= htmlspecialchars($profile['emergency_contact_relation']) ?>" required />

        <button type="submit" id="saveBtn"><?= $profile_exists ? 'Update Profile' : 'Create Profile' ?></button>
    </form>
</div>

</body>
</html>
