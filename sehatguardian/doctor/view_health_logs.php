<?php
session_start();
require_once("../includes/db_connect.php");

// Only allow doctor access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

// Fetch all patients for dropdown
$patients = [];
$stmt = $conn->prepare("SELECT user_id, username FROM users WHERE role = 'patient' ORDER BY username ASC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $patients[] = $row;
}
$stmt->close();

// Determine selected patient
$selected_patient = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$logs = [];
$patient_name = "";

// Fetch chosen patient's health log
if ($selected_patient) {
    // Get patient username
    foreach ($patients as $p) {
        if ($p['user_id'] == $selected_patient) {
            $patient_name = $p['username'];
            break;
        }
    }
    $stmt = $conn->prepare("SELECT * FROM health_log WHERE patient_id = ? ORDER BY log_date DESC");
    $stmt->bind_param("i", $selected_patient);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $logs[] = $row;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Health Logs (Doctor View)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4 text-info">🩺 Patient Health Logs</h2>
    <form method="GET" class="mb-4">
        <label for="patient_id" class="form-label">Select Patient:</label>
        <select name="patient_id" id="patient_id" class="form-select" onchange="this.form.submit()">
            <option value="">--Choose Patient--</option>
            <?php foreach ($patients as $p): ?>
                <option value="<?= $p['user_id'] ?>" <?= $selected_patient == $p['user_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($selected_patient && $patient_name): ?>
        <h4 class="mb-3"><?= htmlspecialchars($patient_name) ?>'s Health Log</h4>
        <?php if ($logs): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th><th>BP</th><th>Sugar</th><th>Water Intake</th><th>Sleep Hours</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['log_date']) ?></td>
                        <td><?= htmlspecialchars($log['bp']) ?></td>
                        <td><?= htmlspecialchars($log['sugar']) ?></td>
                        <td><?= htmlspecialchars($log['water_intake']) ?></td>
                        <td><?= htmlspecialchars($log['sleep_hours']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning">No health logs found for this patient.</div>
        <?php endif; ?>
    <?php elseif ($selected_patient): ?>
        <div class="alert alert-danger">Patient not found.</div>
    <?php endif; ?>
    <a href="dashboardr.php" class="btn btn-link mt-3">&larr; Back to Dashboard</a>
</div>
</body>
</html>
