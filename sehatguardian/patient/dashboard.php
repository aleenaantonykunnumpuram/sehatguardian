<?php
session_start();
require_once('../includes/db_connect.php');

// Prevent back-button caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Handle AJAX emergency alert submission FIRST (before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'emergency_alert') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    $patient_id = $_SESSION['user_id'];
    $location = trim($_POST['location'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($location) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Location and message are required']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO emergency_alerts (patient_id, location, message, status) VALUES (?, ?, ?, 'Sent')");
    $stmt->bind_param("iss", $patient_id, $location, $message);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Emergency alert sent successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// Check login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login_admin.php");
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

// FIXED: Fetch today's medicines with taken status from medicine_taken_log
$todayMedicines = [];
$stmt = $conn->prepare("
    SELECT 
        ms.*,
        mtl.taken_time,
        mtl.taken_date,
        CASE WHEN mtl.id IS NOT NULL THEN 'Taken' ELSE 'Pending' END as daily_status
    FROM medicine_schedule ms
    LEFT JOIN medicine_taken_log mtl 
        ON ms.id = mtl.medicine_id 
        AND mtl.patient_id = ? 
        AND DATE(mtl.taken_date) = DATE(?)
    WHERE ms.patient_id = ?
        AND ms.from_date <= ?
        AND ms.to_date >= ?
    ORDER BY ms.time ASC
");
$stmt->bind_param("isiss", $patient_id, $today, $patient_id, $today, $today);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $todayMedicines[] = $row;
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
.alert-box { padding: 15px 20px; border-radius: 8px; margin-bottom: 15px; display: none; font-weight: 500; }
.alert-box.success { background-color: #28a745; color: white; }
.alert-box.error { background-color: #dc3545; color: white; }

/* Medicine Reminder Modal Styling */
.modal-header.reminder-header {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: #fff;
  border-radius: 10px 10px 0 0;
  padding: 20px;
  border: none;
}
.modal-title.reminder-title {
  font-size: 1.5rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 10px;
}
.reminder-modal .modal-content {
  border-radius: 12px;
  border: none;
  box-shadow: 0 10px 40px rgba(0,0,0,0.3);
  overflow: hidden;
}
.reminder-modal .modal-body {
  padding: 30px;
  background: #fff;
}
#reminderContent {
  font-size: 1.1rem;
  line-height: 1.8;
}
.medicine-name-highlight {
  color: #296dc1;
  font-size: 1.4rem;
  font-weight: 700;
  display: block;
  margin: 15px 0;
}
.btn-close-white {
  filter: brightness(0) invert(1);
  opacity: 1;
}

/* Pulse animation for alarm icon */
@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}
.alarm-icon {
  animation: pulse 1s infinite;
  font-size: 1.8rem;
}

/* Animation for modal */
@keyframes slideIn {
  from {
    transform: translateY(-50px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}
.modal.show .modal-dialog {
  animation: slideIn 0.3s ease-out;
}

/* Loading spinner */
.btn-loading {
  pointer-events: none;
  opacity: 0.6;
}
.btn-loading::after {
  content: "";
  display: inline-block;
  width: 14px;
  height: 14px;
  margin-left: 8px;
  border: 2px solid #ffffff;
  border-radius: 50%;
  border-top-color: transparent;
  animation: spinner 0.6s linear infinite;
}
@keyframes spinner {
  to { transform: rotate(360deg); }
}
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
        <li class="nav-item"><a class="nav-link" href="feedback.php"><i class="fas fa-comment-alt me-2"></i> Feedback</a></li>
        <li class="nav-item mt-3"><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
      </ul>
    </nav>

    <!-- Main Content -->
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
      <h2 class="greeting">Welcome, <?= htmlspecialchars($user['username']); ?>!</h2>
      <h5>Current Time: <span id="live-time"></span></h5>

      <!-- Alert Box -->
      <div id="alertBox" class="alert-box"></div>

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

      <!-- Emergency Alert Card -->
      <div class="row mb-4">
        <div class="col-md-4">
          <div class="card bg-danger text-white shadow text-center">
            <div class="card-body">
              <h5 class="card-title"><i class="fas fa-bell me-2"></i> Emergency Alert</h5>
              <p class="card-text">Send an instant alert to your doctor!</p>
              <button id="emergencyBtn" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#alertModal">Send Alert</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Emergency Alert Modal -->
      <div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <form id="alertForm">
            <div class="modal-content">
              <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="alertModalLabel"><i class="fas fa-bell me-2"></i> Send Emergency Alert</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label for="location" class="form-label">Location</label>
                  <input type="text" id="location" name="location" class="form-control" placeholder="Enter your current location" required>
                </div>
                <div class="mb-3">
                  <label for="message" class="form-label">Message</label>
                  <textarea id="message" name="message" class="form-control" placeholder="Describe your emergency" rows="3" required></textarea>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" id="submitAlertBtn" class="btn btn-danger">Send Alert</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Medicine Reminder Modal -->
      <div class="modal fade reminder-modal" id="medicineReminderModal" tabindex="-1" aria-labelledby="medicineReminderLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header reminder-header">
              <h5 class="modal-title reminder-title" id="medicineReminderLabel">
                <span class="alarm-icon">⏰</span> Medicine Reminder!
              </h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div id="reminderContent"></div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Live time display
setInterval(() => {
  document.getElementById("live-time").textContent = new Date().toLocaleTimeString();
}, 1000);

// FIXED: Handle Emergency Alert Submission with proper error handling
document.getElementById('alertForm').addEventListener('submit', function(e){
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitAlertBtn');
    const location = document.getElementById('location').value.trim();
    const message = document.getElementById('message').value.trim();
    
    // Validation
    if (!location || !message) {
        showAlert('Please fill in all fields', 'error');
        return;
    }
    
    // Disable button and show loading
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;
    
    // Prepare form data
    const formData = new FormData();
    formData.append('action', 'emergency_alert');
    formData.append('location', location);
    formData.append('message', message);
    
    // Send AJAX request using Fetch API
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Re-enable button
        submitBtn.classList.remove('btn-loading');
        submitBtn.disabled = false;
        
        // Show alert message
        showAlert(data.message, data.success ? 'success' : 'error');
        
        if (data.success) {
            // Close modal
            const alertModal = bootstrap.Modal.getInstance(document.getElementById('alertModal'));
            if (alertModal) {
                alertModal.hide();
            }
            // Reset form
            document.getElementById('alertForm').reset();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        submitBtn.classList.remove('btn-loading');
        submitBtn.disabled = false;
        showAlert('An error occurred. Please try again.', 'error');
    });
});

// Helper function to show alert messages
function showAlert(message, type) {
    const alertBox = document.getElementById('alertBox');
    alertBox.textContent = message;
    alertBox.className = 'alert-box ' + type;
    alertBox.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        alertBox.style.display = 'none';
    }, 5000);
}

// ==================== MEDICINE REMINDER SYSTEM ====================

// Get today's medicines from PHP
const todayMedicines = <?php echo json_encode($todayMedicines); ?>;
const repeatIntervalMinutes = 2; // Repeat reminder every 2 minutes
let currentReminderModal = null;
let lastShownTime = {}; // Track when each medicine was last shown

console.log('Today\'s medicines loaded:', todayMedicines);

// Check if medicine should show reminder
function shouldShowReminder(medicineTimeStr, medicineId) {
  const now = new Date();
  const medicineTime = new Date();
  const [h, m] = medicineTimeStr.split(':');
  medicineTime.setHours(parseInt(h), parseInt(m), 0, 0);
  
  // Don't show if medicine time hasn't arrived yet
  if (now < medicineTime) {
    return false;
  }
  
  // Check if enough time passed since last reminder
  if (lastShownTime[medicineId]) {
    const timeSinceLastShown = (now - lastShownTime[medicineId]) / 60000;
    return timeSinceLastShown >= repeatIntervalMinutes;
  }
  
  return true; // Show if time has passed and not shown before
}

// Only show reminders for PENDING medicines (not Taken)
function checkMedicineReminders() {
  const now = new Date();
  
  console.log('Checking reminders at:', now.toLocaleTimeString());
  
  // Find first pending medicine that needs reminder
  for (let medicine of todayMedicines) {
    // CRITICAL: Only show reminder if status is 'Pending', NOT 'Taken'
    if (medicine.daily_status === 'Pending' && shouldShowReminder(medicine.time, medicine.id)) {
      console.log('✅ Showing reminder for:', medicine.name, 'at', medicine.time);
      lastShownTime[medicine.id] = new Date();
      showReminderPopup(medicine);
      break; // Show only one reminder at a time
    }
  }
}

// Show reminder popup
function showReminderPopup(medicine) {
  const dosageText = medicine.dose || 'As prescribed';
  const timeFormatted = formatTime(medicine.time);
  
  if (currentReminderModal) {
    try {
      currentReminderModal.hide();
    } catch (e) {
      console.log('Could not hide previous modal');
    }
  }
  
  document.getElementById('reminderContent').innerHTML = `
    <div style="text-align: center;">
      <p style="font-size: 1.1rem; margin-bottom: 10px;">
        <b>It's time to take your medicine:</b>
      </p>
      <span class="medicine-name-highlight">${escapeHtml(medicine.name)}</span>
      <div style="margin-top: 20px; text-align: left;">
        <p style="margin: 8px 0;">
          <strong>Dosage:</strong> <span style="color: #296dc1; font-weight: 600;">${escapeHtml(dosageText)}</span>
        </p>
        <p style="margin: 8px 0;">
          <strong>Scheduled Time:</strong> <span style="color: #296dc1; font-weight: 600;">${timeFormatted}</span>
        </p>
      </div>
      <div style="margin-top: 25px; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #f59e0b;">
        <p style="margin: 0; color: #856404; font-weight: 500;">
          ⚠️ This reminder will repeat every 2 minutes. Go to <a href="add_medicine.php" style="color: #d97706; font-weight: 600;">Medicine Reminder</a> to mark as taken.
        </p>
      </div>
    </div>
  `;
  
  playNotificationSound();
  
  currentReminderModal = new bootstrap.Modal(document.getElementById('medicineReminderModal'));
  currentReminderModal.show();
  
  requestNotificationPermission(medicine);
}

// Format time to 12-hour format
function formatTime(timeStr) {
  const [h, m] = timeStr.split(':');
  let hour = parseInt(h);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  hour = hour % 12 || 12;
  return `${hour}:${m} ${ampm}`;
}

// Escape HTML
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Play notification sound
function playNotificationSound() {
  try {
    const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZURE');
    audio.play().catch(e => console.log('Audio play failed:', e));
  } catch (e) {
    console.log('Audio not supported');
  }
}

// Request browser notification permission
function requestNotificationPermission(medicine) {
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
  }
  
  if ('Notification' in window && Notification.permission === 'granted') {
    new Notification('Medicine Reminder', {
      body: `Time to take ${medicine.name} (${medicine.dose})`,
      tag: 'medicine-' + medicine.id,
      icon: 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y="75" font-size="75">💊</text></svg>'
    });
  }
}

// Initial check on page load
checkMedicineReminders();

// Check every 30 seconds for reminders
setInterval(checkMedicineReminders, 30000);

// Also check every minute for accuracy
setInterval(checkMedicineReminders, 60000);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
