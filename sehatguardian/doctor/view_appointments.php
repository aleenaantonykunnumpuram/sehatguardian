<?php
session_start();
require_once("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}
$doctor_id = $_SESSION['user_id'];

// Accept/Reject logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accept']) && isset($_POST['appt_id'])) {
        $appt_id = intval($_POST['appt_id']);
        $appt_date = $_POST['appt_date'];
        $appt_time = $_POST['appt_time'];
        $notes = trim($_POST['notes']);

        $stmt = $conn->prepare("
            UPDATE appointments 
            SET status='Approved', appointment_date=?, appointment_time=?, notes=?
            WHERE appointment_id=? AND doctor_id=?
        ");
        $stmt->bind_param("sssii", $appt_date, $appt_time, $notes, $appt_id, $doctor_id);
        $stmt->execute();
        $stmt->close();
    }
    if (isset($_POST['reject']) && isset($_POST['appt_id'])) {
        $appt_id = intval($_POST['appt_id']);
        $reason = trim($_POST['reason']);

        $stmt = $conn->prepare("
            UPDATE appointments 
            SET status='Rejected', notes=?
            WHERE appointment_id=? AND doctor_id=?
        ");
        $stmt->bind_param("sii", $reason, $appt_id, $doctor_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch pending appointment requests
$stmt = $conn->prepare("
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.notes AS patient_notes, u.username AS patient_name
    FROM appointments a
    JOIN users u ON a.patient_id = u.user_id
    WHERE a.doctor_id = ? AND a.status = 'Pending'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointment Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #e0f7fa; margin: 30px; }
        table { background: white; border-radius: 10px; }
        h2 { color: #006064; margin-bottom: 25px; }
        .btn-teal { background: #0097a7; color: white; }
        .btn-teal:hover { background: #007c91; }
        .btn-reject { background: #b71c1c; }
        .btn-reject:hover { background: #900000; }
        label { color: #006064; font-weight: 600; }
    </style>
</head>
<body>
<div class="container">
    <h2>Appointment Requests</h2>
    <?php if ($result && $result->num_rows > 0): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Requested Date</th>
                    <th>Requested Time</th>
                    <th>Symptoms</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['patient_name']) ?></td>
                    <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                    <td><?= htmlspecialchars($row['appointment_time']) ?></td>
                    <td><?= nl2br(htmlspecialchars($row['patient_notes'])) ?></td>
                    <td>
                        <form method="POST" style="display:inline-block; margin-bottom: 5px;">
                            <input type="hidden" name="appt_id" value="<?= $row['appointment_id'] ?>">
                            <label>Confirm Date:</label>
                            <input type="date" name="appt_date" value="<?= htmlspecialchars($row['appointment_date']) ?>" required>
                            <label>Confirm Time:</label>
                            <input type="time" name="appt_time" value="<?= htmlspecialchars($row['appointment_time']) ?>" required>
                            <label>Notes:</label>
                            <textarea name="notes" rows="2" placeholder="Any comments..."></textarea>
                            <button type="submit" name="accept" class="btn btn-teal btn-sm mt-1">Accept</button>
                        </form>
                        <form method="POST" style="display:inline-block; margin-left: 5px;">
                            <input type="hidden" name="appt_id" value="<?= $row['appointment_id'] ?>">
                            <label>Reason for Reject:</label>
                            <textarea name="reason" rows="2" required></textarea>
                            <button type="submit" name="reject" class="btn btn-reject btn-sm mt-1">Reject</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No appointment requests at the moment.</div>
    <?php endif; ?>
</div>
</body>
</html>
