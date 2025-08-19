<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
$patient_id = $_SESSION['user_id'];

// Fetch appointment data
$stmt = $conn->prepare("
    SELECT a.*, u.username AS doctor_name, dp.specialization
    FROM appointments a
    JOIN users u ON a.doctor_id = u.user_id
    LEFT JOIN doctor_profile dp ON dp.doctor_id = u.user_id
    WHERE a.appointment_id = ? AND a.patient_id = ? AND a.status = 'Approved'
");
$stmt->bind_param("ii", $appointment_id, $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$app = $result->fetch_assoc();

if (!$app) {
    echo "Receipt not available for this appointment, or not approved.";
    exit();
}

// You can fetch patient name from session or DB
$patient_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Patient';

?>
<!DOCTYPE html>
<html>
<head>
<title>Appointment Receipt #<?= $appointment_id ?></title>
<style>
body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6fb; color: #16697A; margin: 0; }
.receipt-box {
  background: #fff;
  border: 2px solid #20b2aa;
  box-shadow: 0 2px 12px rgba(32,178,170,0.1);
  border-radius: 15px;
  max-width: 520px;
  margin: 30px auto;
  padding: 36px;
}
h2 { text-align:center; color: #1687a7; margin-bottom: 18px; }
.details { font-size: 1.07em; margin-bottom: 15px; }
.field { margin-bottom: 9px; }
.print-btn {
  display: block;
  margin: 32px auto 0 auto;
  background: #20b2aa;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 12px 34px;
  font-weight: 600;
  font-size: 1.08em;
  cursor: pointer;
}
</style>
</head>
<body>
<div class="receipt-box">
  <h2>Appointment Receipt</h2>
  <div class="details">
    <div class="field"><strong>Receipt No:</strong> <?= $appointment_id ?></div>
    <div class="field"><strong>Patient Name:</strong> <?= htmlspecialchars($patient_name) ?></div>
    <div class="field"><strong>Doctor:</strong> <?= htmlspecialchars($app['doctor_name']) ?></div>
    <div class="field"><strong>Specialization:</strong> <?= htmlspecialchars($app['specialization'] ?? '-') ?></div>
    <div class="field"><strong>Date:</strong> <?= date("M d, Y", strtotime($app['appointment_date'])) ?></div>
    <div class="field"><strong>Time:</strong> <?= date("h:i A", strtotime($app['appointment_time'])) ?></div>
    <div class="field"><strong>Status:</strong> <?= htmlspecialchars($app['status']) ?></div>
    <?php if(!empty($app['notes'])): ?>
      <div class="field"><strong>Notes:</strong> <?= htmlspecialchars($app['notes']) ?></div>
    <?php endif; ?>
  </div>
  <div style="text-align:center;margin-top:28px;">
    <em>Thank you for booking with Sehat Guardian!</em>
  </div>
  <button class="print-btn" onclick="window.print()">Download as PDF</button>
</div>
</body>
</html>
