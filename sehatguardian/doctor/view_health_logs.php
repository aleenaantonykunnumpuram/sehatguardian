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

// Function to highlight abnormal values
function highlight($value, $type) {
    $class = '';
    switch($type) {
        case 'bp':
            // BP in format "120/80"
            if (preg_match('/(\d+)\/(\d+)/', $value, $matches)) {
                $systolic = intval($matches[1]);
                $diastolic = intval($matches[2]);
                if ($systolic > 140 || $diastolic > 90) $class = 'table-danger';
            }
            break;
        case 'sugar':
            if ($value > 140) $class = 'table-danger';
            break;
        case 'water':
            if ($value < 1) $class = 'table-warning';
            break;
        case 'sleep':
            if ($value < 5 || $value > 9) $class = 'table-warning';
            break;
    }
    return $class;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Health Logs - Doctor View</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .header { margin-bottom: 30px; }
        table th, table td { text-align: center; vertical-align: middle; }
        .table-danger { background-color: #f8d7da !important; color: #721c24; font-weight: bold; }
        .table-warning { background-color: #fff3cd !important; color: #856404; font-weight: bold; }
        .legend-box { display: inline-block; width: 20px; height: 20px; margin-right: 8px; vertical-align: middle; border-radius: 3px; }
        .legend-danger { background-color: #f8d7da; border: 1px solid #721c24; }
        .legend-warning { background-color: #fff3cd; border: 1px solid #856404; }
        .table-striped > tbody > tr.table-danger > td,
        .table-striped > tbody > tr.table-warning > td {
            background-color: inherit !important;
            color: inherit !important;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <h2 class="header text-primary">🩺 Patient Health Logs</h2>

    <form method="GET" class="mb-3">
        <label for="patient_id" class="form-label fw-semibold">Select Patient:</label>
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
        <h4 class="mb-2 text-success"><?= htmlspecialchars($patient_name) ?>'s Health Log</h4>

        <!-- Legend -->
        <div class="mb-3">
            <span class="legend-box legend-danger"></span> Abnormal BP/Sugar
            &nbsp;&nbsp;
            <span class="legend-box legend-warning"></span> Low Water Intake or Sleep Issue
        </div>

        <?php if ($logs): ?>
            <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>BP (Systolic/Diastolic)</th>
                        <th>Sugar (mg/dL)</th>
                        <th>Water Intake (L)</th>
                        <th>Sleep Hours</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['log_date']) ?></td>
                        <td class="<?= highlight($log['bp'], 'bp') ?>"><?= htmlspecialchars($log['bp']) ?></td>
                        <td class="<?= highlight($log['sugar'], 'sugar') ?>"><?= htmlspecialchars($log['sugar']) ?></td>
                        <td class="<?= highlight($log['water_intake'], 'water') ?>"><?= htmlspecialchars($log['water_intake']) ?></td>
                        <td class="<?= highlight($log['sleep_hours'], 'sleep') ?>"><?= htmlspecialchars($log['sleep_hours']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
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
