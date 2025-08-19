<?php
// ✅ Include auth and DB connection
require_once($_SERVER['DOCUMENT_ROOT'].'/sehatguardian/includes/auth.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/sehatguardian/includes/db_connect.php');

// Check if admin is logged in
checkAuth('admin');

$message = "";

/** 
 * Handle adding doctor
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_doctor'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password_input = $_POST['password'];
    $specialization = trim($_POST['specialization']);

    if ($username && $email && $password_input) {
        // ✅ Password strength validation
        if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?~\\/-]).{8,}$/', $password_input)) {
            $message = "⚠️ Password must be at least 8 chars, include 1 uppercase, 1 number, 1 special char.";
        } else {
            $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);

            $conn->begin_transaction(); // ✅ Start transaction

            try {
                // Insert into users table
                $stmt = $conn->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, 'doctor', ?)");
                $stmt->bind_param("sss", $username, $hashed_password, $email);

                if ($stmt->execute()) {
                    $doctor_id = $stmt->insert_id;

                    // Insert into doctor_profile
                    $stmt2 = $conn->prepare("INSERT INTO doctor_profile (doctor_id, specialization) VALUES (?, ?)");
                    $stmt2->bind_param("is", $doctor_id, $specialization);
                    $stmt2->execute();
                    $stmt2->close();

                    $conn->commit();
                    $message = "✅ Doctor added successfully.";
                } else {
                    throw new Exception("Error inserting into users.");
                }
                $stmt->close();
            } catch (Exception $e) {
                $conn->rollback();
                $message = "❌ Failed to add doctor: " . $e->getMessage();
            }
        }
    } else {
        $message = "⚠️ Please fill all required fields.";
    }
}

/** 
 * Handle delete doctor
 */
if (isset($_GET['delete'])) {
    $delete_user_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=? AND role='doctor'");
    $stmt->bind_param("i", $delete_user_id);
    if ($stmt->execute()) {
        $message = "✅ Doctor deleted successfully.";
    }
    $stmt->close();
}

/** 
 * Fetch all doctors
 */
$doctors = [];
$sql = "
SELECT u.user_id, u.username, u.email, dp.specialization
FROM users u 
LEFT JOIN doctor_profile dp ON u.user_id = dp.doctor_id
WHERE u.role = 'doctor'
ORDER BY u.username ASC
";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Doctors - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #e0f7f7; padding: 30px; }
        .container { max-width: 900px; margin: auto; }
        h2 { text-align: center; color: #006064; margin-bottom: 20px; }
        .message { text-align: center; font-weight: bold; margin-bottom: 15px; }
        .message.ok { color: green; }
        .message.error { color: red; }
        .form-section, .table-section { background: #ffffff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
        form input { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; }
        form button { background: #00838f; color: #fff; border: none; padding: 12px 18px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        form button:hover { background: #006064; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ccc; }
        th { background: #006064; color: #fff; }
        .btn-delete { background: #d32f2f; color: #fff; padding: 6px 12px; border-radius: 4px; text-decoration: none; }
        .btn-delete:hover { background: #b71c1c; }
        .logout { text-align: right; margin-bottom: 15px; }
        .logout a { color: #006064; text-decoration: none; font-weight: bold; }
        .logout a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <div class="logout"><a href="dashboard.php">← Back to Dashboard</a></div>
    <h2>Manage Doctors</h2>
    <?php if($message) echo "<div class='message " . (strpos($message, '✅') === 0 ? 'ok' : 'error') . "'>{$message}</div>"; ?>

    <div class="form-section">
        <h3>Add Doctor</h3>
        <form method="POST">
            <input type="text" name="username" placeholder="Doctor Username" required>
            <input type="email" name="email" placeholder="Doctor Email" required>
            <input type="password" name="password" placeholder="Temporary Password" required
                pattern="^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?~\\/-]).{8,}$"
                title="8+ characters, 1 uppercase, 1 number, 1 special char">
            <input type="text" name="specialization" placeholder="Specialization (optional)">
            <button type="submit" name="add_doctor">Add Doctor</button>
        </form>
    </div>

    <div class="table-section">
        <h3>All Doctors</h3>
        <table>
            <tr>
                <th>Username</th><th>Email</th><th>Specialization</th><th>Action</th>
            </tr>
            <?php if(count($doctors) > 0): ?>
                <?php foreach($doctors as $doc): ?>
                <tr>
                    <td><?= htmlspecialchars($doc['username']) ?></td>
                    <td><?= htmlspecialchars($doc['email']) ?></td>
                    <td><?= htmlspecialchars($doc['specialization']) ?></td>
                    <td><a class="btn-delete" href="?delete=<?= $doc['user_id'] ?>" onclick="return confirm('Delete this doctor?');">Delete</a></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center;">No doctors found.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
</body>
</html>
