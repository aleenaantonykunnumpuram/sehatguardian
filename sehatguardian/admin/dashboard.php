<?php
// Start session only if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check if logged in as Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get admin name from session, fallback to default
$admin_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard - Sehat Guardian</title>
  <!-- Font Awesome 6.4.0 CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Arial, sans-serif;
    }

    body {
      display: flex;
      background-color: #e0f2f1; /* light teal background */
      min-height: 100vh;
    }
    .sidebar {
      width: 250px;
      height: 100vh;
      background: #004d40; /* dark teal */
      color: white;
      padding: 20px 10px;
      position: fixed;
      left: 0; top: 0;
    }
    .sidebar h2 {
      text-align: center;
      margin-bottom: 14px;
      font-size: 1.6em;
      color: #a7ffeb; /* bright teal */
    }
    .sidebar p {
      text-align: center;
      margin-bottom: 21px;
      font-size: 1em;
      opacity: 0.8;
    }
    .sidebar a {
      display: flex;
      align-items: center;
      padding: 12px 18px;
      margin: 8px 0;
      color: #b2dfdb; /* light teal text */
      text-decoration: none;
      border-radius: 8px;
      font-size: 1.05em;
      transition: 0.3s;
    }
    .sidebar a:hover {
      background-color: #00796b; /* medium teal highlight */
      color: #e0f2f1;
    }
    .sidebar a i {
      margin-right: 12px;
    }
    .main {
      margin-left: 250px;
      padding: 32px 28px;
      flex: 1;
      min-width: 0;
      color: #004d40; /* dark teal text */
    }
    .header {
      background-color: #00796b; /* medium teal */
      color: white;
      padding: 20px 24px;
      border-radius: 12px;
      margin-bottom: 24px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .header h2 {
      font-weight: 500;
      font-size: 2em;
      margin-bottom: 6px;
    }
    .header p {
      font-size: 1.2em;
      margin-top: 0;
    }
    .alert-box {
      margin-top: 10px;
      background-color: #b2dfdb; /* pale teal */
      padding: 15px;
      border-left: 8px solid #004d40; /* dark teal border */
      border-radius: 8px;
      font-weight: 500;
      color: #004d40; /* dark teal text */
      margin-bottom: 24px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .action-buttons {
      display: flex;
      gap: 15px;
      margin: 16px 0 32px 0;
      flex-wrap: wrap;
    }
    .action-buttons button {
      padding: 10px 22px;
      background-color: #004d40; /* dark teal */
      border: none;
      color: white;
      border-radius: 8px;
      cursor: pointer;
      font-size: 1em;
      transition: background 0.3s;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .action-buttons button:hover {
      background-color: #00251a; /* darker teal */
    }
    .cards {
      display: flex;
      gap: 18px;
      flex-wrap: wrap;
      margin-bottom: 32px;
    }
    .card {
      flex: 1 1 220px;
      background: white;
      padding: 22px 18px;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      min-width: 180px;
      color: #004d40;
    }
    .card h3 {
      font-size: 2.2em;
      margin-bottom: 9px;
      font-weight: 600;
    }
    .card p {
      font-size: 1em;
      margin-top: 2px;
      font-weight: 500;
    }
    .card small {
      display: block;
      margin-top: 7px;
      color: #1b5e20; /* darker green */
      font-size: 0.95em;
    }
    .card small[style] {
      color: #b71c1c !important; /* dark red for negative */
    }
    .lower-section {
      display: flex;
      gap: 18px;
      margin-top: 14px;
      flex-wrap: wrap;
    }
    .lower-section .card {
      flex: 1 1 260px;
      min-width: 220px;
      color: #004d40;
    }
  </style>
</head>
<body>
  <div class="sidebar">
  <h2><?= htmlspecialchars($admin_name) ?></h2>
  <p>System Admin</p>
  <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
  <a href="manage_doctors.php"><i class="fas fa-user-md"></i> Manage Doctors</a>
  <a href="manage_patients.php"><i class="fas fa-users"></i> View Patients</a>
  <a href="manage_appointments.php"><i class="fas fa-calendar"></i> Appointments</a>
  <a href="admin_payment.php"><i class="fas fa-credit-card"></i> Approve Payments</a> <!-- Added here -->
  <a href="view_alert.php"><i class="fas fa-envelope"></i> Alerts</a>
  <a href="dashboard_admin_report.php"><i class="fas fa-chart-bar"></i> Reports</a>
  <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
  <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

  <div class="main">
    <div class="header">
      <h2>Good Morning, <?= htmlspecialchars($admin_name) ?></h2>
      <p id="datetime"></p>
    </div>

    <div class="alert-box">
      <i class="fas fa-exclamation-triangle"></i> 
      System Alert: 3 patients have missed their medication reminders today. Please review and take action.
    </div>

    <div class="action-buttons">
      <button onclick="window.location.href='manage_doctors.php'"><i class="fas fa-user-plus"></i> Add Doctor</button>
      <button onclick="window.location.href='appointment_list.php'"><i class="fas fa-calendar-plus"></i> Schedule Appointment</button>
      <button><i class="fas fa-bell"></i> Send Reminder</button>
      <button><i class="fas fa-file-alt"></i> Generate Report</button>
    </div>

    <div class="cards">
      <div class="card">
        <h3>24</h3>
        <p>Active Patients</p>
        <small>+2 this week</small>
      </div>
      <div class="card">
        <h3>8</h3>
        <p>Doctors</p>
        <small>+1 this month</small>
      </div>
      <div class="card">
        <h3>12</h3>
        <p>Appointments Today</p>
        <small>+3 pending</small>
      </div>
      <div class="card">
        <h3>89</h3>
        <p>Medication Compliance</p>
        <small style="color: red;">-2% this week</small>
      </div>
    </div>

    <div class="lower-section">
      <div class="card">
        <h4>Recent Activities</h4>
        <p><i class="fas fa-calendar-plus"></i> New appointment scheduled - 2 hrs ago</p>
        <p><i class="fas fa-pills"></i> Medication reminder sent - 4 hrs ago</p>
      </div>
      <div class="card">
        <h4>Health Trends</h4>
        <p>Health trends and analytics will be displayed here.</p>
      </div>
      <div class="card">
        <h4>Upcoming Appointments</h4>
        <p><i class="fas fa-user-md"></i> Dr. Priya Mehta - Tomorrow 10:30 AM</p>
        <p><i class="fas fa-user-md"></i> Dr. Raj Kumar - Jul 11 - 2:00 PM</p>
      </div>
    </div>
  </div>

<script>
function updateDateTime() {
  const now = new Date();
  const formatted = now.toLocaleString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  });
  document.getElementById('datetime').textContent = formatted;
}
updateDateTime();
setInterval(updateDateTime, 1000); // Update every second
</script>
</body>
</html>
