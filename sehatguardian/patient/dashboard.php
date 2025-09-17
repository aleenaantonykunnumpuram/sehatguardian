<?php
session_start();
require_once('../includes/db_connect.php');

// Prevent back-button caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Check login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Fetch patient info
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch recent appointments
$stmt = $conn->prepare("
    SELECT u.username AS doctor_name, dp.specialization, a.appointment_date, a.appointment_time, a.status
    FROM appointments a
    JOIN users u ON a.doctor_id = u.user_id
    LEFT JOIN doctor_profile dp ON dp.doctor_id = u.user_id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 5
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patient Dashboard - Sehat Guardian</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; }
.sidebar { height: 100vh; background-color: #007b83; color: white; }
.sidebar a { color: white; text-decoration: none; display: block; padding: 10px; }
.sidebar a:hover, .sidebar a.active { background-color: #005f66; }
.card { border-radius: 15px; }
.greeting { font-size: 1.4rem; margin-bottom: 15px; }
.alert-success, .alert-warning, .alert-danger { border-radius: 12px; }
</style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <nav class="col-md-3 col-lg-2 d-md-block sidebar py-4">
      <div class="text-center mb-4">
        <h3>Sehat Guardian</h3>
      </div>
      <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link active" href="profile.php"><i class="fas fa-home me-2"></i> Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="add_medicine.php"><i class="fas fa-pills me-2"></i> Medicine Reminder</a></li>
        <li class="nav-item"><a class="nav-link" href="book_appointment.php"><i class="fas fa-calendar-check me-2"></i> Appointments</a></li>
        <li class="nav-item"><a class="nav-link" href="history.php"><i class="fas fa-history me-2"></i> Appointment History</a></li>
        <li class="nav-item"><a class="nav-link" href="approve_appoinment.php"><i class="fas fa-credit-card me-2"></i> Payment</a></li>
        <li class="nav-item"><a class="nav-link" href="health_log.php"><i class="fas fa-notes-medical me-2"></i> Health Log</a></li>
        <li class="nav-item"><a class="nav-link" href="alerts.php"><i class="fas fa-bell me-2"></i> Alerts</a></li>
        <li class="nav-item"><a class="nav-link" href="feedback.php"><i class="fas fa-comment-alt me-2"></i> Feedback</a></li>
        <li class="nav-item mt-3"><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
      </ul>
    </nav>

    <!-- Main Content -->
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
      <h2 class="greeting">👋 Welcome, <?= htmlspecialchars($user['username']); ?>!</h2>
      <h5>Current Time: <span id="live-time"></span></h5>

      <!-- Stats Cards -->
      <div class="row mb-4">
        <div class="col-md-4">
          <div class="card bg-primary text-white shadow">
            <div class="card-body">
              <h5 class="card-title"><i class="fas fa-pills me-2"></i> Medicines</h5>
              <p class="card-text">Check your medicine schedule.</p>
              <a href="add_medicine.php" class="btn btn-light btn-sm">View Now</a>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card bg-success text-white shadow">
            <div class="card-body">
              <h5 class="card-title"><i class="fas fa-calendar-check me-2"></i> Appointments</h5>
              <p class="card-text">View and manage appointments.</p>
              <a href="book_appointment.php" class="btn btn-light btn-sm">View Now</a>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card bg-info text-white shadow">
            <div class="card-body">
              <h5 class="card-title"><i class="fas fa-notes-medical me-2"></i> Health Log</h5>
              <p class="card-text">Keep your vitals updated daily.</p>
              <a href="health_log.php" class="btn btn-light btn-sm">Update Now</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Appointments -->
      <div class="card">
        <div class="card-header"><i class="fas fa-calendar-alt me-2"></i> Recent Appointments</div>
        <div class="card-body">
          <?php if(count($appointments) > 0): ?>
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Doctor</th>
                  <th>Specialization</th>
                  <th>Date</th>
                  <th>Time</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($appointments as $app): ?>
                  <tr>
                    <td><?= htmlspecialchars($app['doctor_name']) ?></td>
                    <td><?= htmlspecialchars($app['specialization'] ?? '-') ?></td>
                    <td><?= date("M d, Y", strtotime($app['appointment_date'])) ?></td>
                    <td><?= date("h:i A", strtotime($app['appointment_time'])) ?></td>
                    <td>
                      <span class="badge bg-<?= $app['status']=='Approved'?'success':'warning' ?>"><?= ucfirst($app['status']) ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p>No recent appointments found.</p>
          <?php endif; ?>
        </div>
      </div>

    </main>
  </div>
</div>

<script>
setInterval(() => {
  document.getElementById("live-time").textContent = new Date().toLocaleTimeString();
}, 1000);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
