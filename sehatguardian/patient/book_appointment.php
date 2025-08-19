<?php
session_start();
require_once("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}
$patient_id = $_SESSION['user_id'];

// Fetch doctors with specialization
$doctors = [];
$sql = "
    SELECT u.user_id, u.username, dp.specialization
    FROM users u
    LEFT JOIN doctor_profile dp ON u.user_id = dp.doctor_id
    WHERE u.role = 'doctor'
    ORDER BY u.username
";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $doctors[] = $row;
}

// Handle booking submission
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appt'])) {
    $doctor_id = intval($_POST['doctor_id']);
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $symptoms = trim($_POST['symptoms']);

    $stmt = $conn->prepare("
        INSERT INTO appointments (
            patient_id, doctor_id, appointment_date, appointment_time, status, notes
        ) VALUES (?, ?, ?, ?, 'Pending', ?)
    ");
    $stmt->bind_param("iisss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $symptoms);
    $stmt->execute();
    $stmt->close();

    $msg = "Appointment request sent! The doctor will confirm or decline.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Book Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #e0f7fa; }
        .container { max-width: 650px; background: #fff; padding: 30px; margin: 40px auto; border-radius: 12px; }
        h2 { text-align: center; color: #006064; margin-bottom: 25px; }
        .btn-teal { background: #0097a7; color: white; }
        .btn-teal:hover { background: #007c91; }
        label { color: #006064; font-weight: 600; }
    </style>
</head>
<body>
<div class="container">
    <h2>Book Appointment</h2>
    <?php if ($msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label for="doctor_id">Choose Doctor</label>
            <select name="doctor_id" id="doctor_id" class="form-select" required>
                <option value="">-- Select Doctor --</option>
                <?php foreach ($doctors as $doc): ?>
                    <option value="<?= $doc['user_id'] ?>">
                        <?= htmlspecialchars($doc['username']) ?> - <?= htmlspecialchars($doc['specialization'] ?? 'General') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="appointment_date">Date</label>
            <input type="date" name="appointment_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="mb-3">
            <label for="appointment_time">Time</label>
            <input type="time" name="appointment_time" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="symptoms">Symptoms / Notes</label>
            <textarea name="symptoms" class="form-control" rows="3" placeholder="Describe your symptoms or concerns" required></textarea>
        </div>
        <button type="submit" name="book_appt" class="btn btn-teal w-100">Send Appointment Request</button>
    </form>
    <a href="dashboard.php" class="btn btn-link mt-3">&larr; Back to Dashboard</a>
</div>
</body>
</html>
