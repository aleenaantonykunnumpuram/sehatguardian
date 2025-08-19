<?php
session_start();
require_once("../includes/db_connect.php");

// Only allow admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch all appointments with doctor, patient, and status
$stmt = $conn->prepare("
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.notes,
        d.username AS doctor_name,
        p.username AS patient_name
    FROM appointments a
    JOIN users d ON a.doctor_id = d.user_id
    JOIN users p ON a.patient_id = p.user_id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointment Overview (Admin)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #e0f7fa; }
        .container { max-width: 1000px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 12px; }
        h2 { color: #006064; text-align: center; margin-bottom: 24px; }
        .badge-pending { background-color: #ff9800; }
        .badge-approved { background-color: #4caf50; }
        .badge-rejected { background-color: #f44336; }
        .badge-completed { background-color: #0097a7; }
    </style>
</head>
<body>
<div class="container">
    <h2>Appointment Overview</h2>
    <?php if ($result && $result->num_rows > 0): ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Doctor</th>
                    <th>Patient</th>
                    <th>Status</th>
                    <th>Symptoms/Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                    <td><?= htmlspecialchars($row['appointment_time']) ?></td>
                    <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                    <td><?= htmlspecialchars($row['patient_name']) ?></td>
                    <td>
                        <?php
                        $status = strtolower($row['status']);
                        $badgeClass = 'badge-pending';
                        if ($status === 'approved') $badgeClass = 'badge-approved';
                        else if ($status === 'rejected') $badgeClass = 'badge-rejected';
                        else if ($status === 'completed') $badgeClass = 'badge-completed';
                        ?>
                        <span class="badge <?= $badgeClass ?>">
                            <?= ucfirst($row['status']) ?>
                        </span>
                    </td>
                    <td><?= nl2br(htmlspecialchars($row['notes'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No appointments found.</div>
    <?php endif; ?>
    <a href="dashboard.php" class="btn btn-link">&larr; Back to Dashboard</a>
</div>
</body>
</html>
