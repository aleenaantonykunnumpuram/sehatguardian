<?php
session_start();
require_once("../includes/db_connect.php");

// Prevent cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];

// Editing
$edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$edit_medicine = null;

if ($edit_id) {
    $stmt = $conn->prepare("SELECT * FROM medicine_schedule WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $edit_id, $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_medicine = $result->fetch_assoc();
    $stmt->close();
}

// Mark as Taken (AJAX) - Now uses medicine_taken_log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['taken_id'])) {
    $taken_id = intval($_POST['taken_id']);
    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');
    
    // First, check if entry already exists
    $check_stmt = $conn->prepare("
        SELECT id FROM medicine_taken_log 
        WHERE patient_id = ? AND medicine_id = ? AND taken_date = ?
    ");
    $check_stmt->bind_param("iis", $patient_id, $taken_id, $today);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();
    
    if ($exists) {
        // Update existing record
        $update_stmt = $conn->prepare("
            UPDATE medicine_taken_log 
            SET taken_time = ? 
            WHERE patient_id = ? AND medicine_id = ? AND taken_date = ?
        ");
        $update_stmt->bind_param("siis", $now, $patient_id, $taken_id, $today);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Insert new record
        $insert_stmt = $conn->prepare("
            INSERT INTO medicine_taken_log (patient_id, medicine_id, taken_date, taken_time)
            VALUES (?, ?, ?, ?)
        ");
        $insert_stmt->bind_param("iiss", $patient_id, $taken_id, $today, $now);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    
    echo "DONE";
    exit();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add/Update
    $name = trim($_POST['name']);
    $dose = trim($_POST['dose']);
    $time = $_POST['time'];
    $from_date = $_POST['from_date'];
    $to_date = $_POST['to_date'];

    if (isset($_POST['update_id']) && $_POST['update_id']) {
        $update_id = intval($_POST['update_id']);
        $stmt = $conn->prepare("
            UPDATE medicine_schedule 
            SET name = ?, dose = ?, time = ?, from_date = ?, to_date = ?
            WHERE id = ? AND patient_id = ?
        ");
        $stmt->bind_param("sssssii", $name, $dose, $time, $from_date, $to_date, $update_id, $patient_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("
            INSERT INTO medicine_schedule (patient_id, name, dose, time, from_date, to_date, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')
        ");
        $stmt->bind_param("isssss", $patient_id, $name, $dose, $time, $from_date, $to_date);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: add_medicine.php");
    exit();
}

// Delete
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Delete from both tables
    $stmt = $conn->prepare("DELETE FROM medicine_taken_log WHERE medicine_id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $delete_id, $patient_id);
    $stmt->execute();
    $stmt->close();
    
    $stmt = $conn->prepare("DELETE FROM medicine_schedule WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $delete_id, $patient_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: add_medicine.php");
    exit();
}

// All medicines
$medicines = [];
$stmt = $conn->prepare("SELECT * FROM medicine_schedule WHERE patient_id = ? ORDER BY from_date ASC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $medicines[] = $row;
}
$stmt->close();

// Today's medicines with taken status from medicine_taken_log
$todayMedicines = [];
$today = date('Y-m-d');

// Get today's scheduled medicines and check if taken today
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
<html>
<head>
  <meta charset="UTF-8" />
  <title>Manage Medicines | Sehat Guardian</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #e3f0fc;
      color: #23395d;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .container {
      max-width: 700px;
      margin: 32px auto 48px;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 16px #acc6e0aa;
      padding: 20px 25px 40px;
    }
    h2 {
      color: #296dc1;
      text-align: center;
      margin-bottom: 26px;
    }
    .card {
      border: 1px solid #b6c9e1;
      border-radius: 10px;
      margin-bottom: 20px;
      box-shadow: 0 2px 7px #b6c9e122;
      background: #f7fbff;
    }
    .card-header {
      background: #296dc1;
      color: #fff;
      border-radius: 10px 10px 0 0;
      font-weight: 600;
      text-align: center;
      padding: 12px;
      letter-spacing: 0.5px;
    }
    .btn-custom {
      background: #4a90e2;
      color: #fff;
      border-radius: 6px;
      font-weight: 500;
      border: none;
      padding: 7px 18px;
      transition: background 0.22s;
    }
    .btn-custom:hover {
      background: #296dc1;
    }
    .btn-outline-primary, .btn-outline-danger {
      font-weight: 500;
      border-radius: 6px;
      padding: 7px 14px;
      font-size: 0.95rem;
      margin-left: 4px;
    }
    .btn-outline-primary { color: #296dc1; border-color: #296dc1; }
    .btn-outline-primary:hover { background: #296dc1; color: #fff; border-color: #296dc1;}
    .btn-outline-danger { color: #cd0031; border-color: #cd0031; }
    .btn-outline-danger:hover { background: #cd0031; color: #fff;}
    .table thead { background: #e6f1ff; }
    .badge-pending { background: #d5e8fa; color: #296dc1; }
    .badge-taken { background: #4a90e2; color: #fff; }
    .medicine-checkbox { accent-color: #296dc1; width: 22px; height: 22px; margin-right: 13px;}
    .list-group-item {
      background: #f3f9fe;
      border: 1px solid #c4dcfa;
      color: #23395d;
      margin-bottom: 10px;
      display: flex; 
      align-items: center; 
      justify-content: space-between;
      font-size: 1rem;
      border-radius: 6px;
      padding: 12px 16px;
    }
    
    /* Enhanced Modal Styling */
    .modal-header {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
      color: #fff;
      border-radius: 10px 10px 0 0;
      padding: 20px;
      border: none;
    }
    .modal-title {
      font-size: 1.5rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .modal-content {
      border-radius: 12px;
      border: none;
      box-shadow: 0 10px 40px rgba(0,0,0,0.3);
      overflow: hidden;
    }
    .modal-body {
      padding: 30px;
      background: #fff;
    }
    #alarmContent {
      font-size: 1.1rem;
      line-height: 1.8;
    }
    #alarmContent b {
      color: #296dc1;
      font-size: 1.3rem;
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
    
    /* Pulse animation for alarm icon */
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }
    .alarm-icon {
      animation: pulse 1s infinite;
      font-size: 1.8rem;
    }
  </style>
</head>
<body>
<div class="container">

  <h2>Medicine Manager</h2>

  <!-- Medicine Checklist -->
  <div class="card">
    <div class="card-header">
      Today's Medicine Checklist (<?= date('F j, Y') ?>)
    </div>
    <div class="card-body">
      <?php if(count($todayMedicines) > 0): ?>
        <ul class="list-group" id="medicine-checklist" style="margin-bottom:0;">
          <?php foreach($todayMedicines as $med): ?>
            <li class="list-group-item">
              <div style="display: flex; align-items: center;">
                <input type="checkbox" class="medicine-checkbox" data-id="<?= $med['id']; ?>" <?= $med['daily_status'] === 'Taken' ? 'checked disabled' : ''; ?> />
                <span><?= htmlspecialchars($med['name']) ?> <small class="text-muted" style="font-weight:600;">at <?= date("h:i A", strtotime($med['time'])) ?></small></span>
              </div>
              <span class="badge <?= $med['daily_status'] === 'Taken' ? 'badge-taken' : 'badge-pending' ?>"><?= $med['daily_status'] ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p style="margin: 0; padding: 10px;">No medicines scheduled for today.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Add/Edit Medicine Form -->
  <div class="card">
    <div class="card-header"><?= $edit_medicine ? "Edit Medicine" : "Add New Medicine" ?></div>
    <div class="card-body">
      <form method="POST" class="row g-3">
        <input type="hidden" name="update_id" value="<?= $edit_medicine ? htmlspecialchars($edit_medicine['id']) : '' ?>">
        <div class="col-md-6">
          <label class="form-label">Medicine Name</label>
          <input type="text" name="name" class="form-control" required value="<?= $edit_medicine ? htmlspecialchars($edit_medicine['name']) : '' ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Dosage</label>
          <input type="text" name="dose" class="form-control" required value="<?= $edit_medicine ? htmlspecialchars($edit_medicine['dose']) : '' ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Time</label>
          <input type="time" name="time" class="form-control" required value="<?= $edit_medicine ? htmlspecialchars($edit_medicine['time']) : '' ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">From Date</label>
          <input type="date" name="from_date" id="from_date" class="form-control" required value="<?= $edit_medicine ? htmlspecialchars($edit_medicine['from_date']) : '' ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">To Date</label>
          <input type="date" name="to_date" id="to_date" class="form-control" required value="<?= $edit_medicine ? htmlspecialchars($edit_medicine['to_date']) : '' ?>">
        </div>
        <div class="col-12 d-flex justify-content-end">
          <button type="submit" class="btn btn-custom"><?= $edit_medicine ? "Update" : "Add" ?></button>
          <?php if ($edit_medicine): ?>
            <a href="add_medicine.php" class="btn btn-outline-primary">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Medicine Schedule Table -->
  <div class="card">
    <div class="card-header">Your Medicine Schedule</div>
    <div class="card-body p-0">
      <?php if ($medicines): ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0">
            <thead>
              <tr>
                <th>Medicine</th><th>Dosage</th><th>Time</th><th>From</th><th>To</th><th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($medicines as $m): ?>
                <tr>
                  <td><?= htmlspecialchars($m['name']) ?></td>
                  <td><?= htmlspecialchars($m['dose']) ?></td>
                  <td><?= htmlspecialchars($m['time']) ?></td>
                  <td><?= htmlspecialchars($m['from_date']) ?></td>
                  <td><?= htmlspecialchars($m['to_date']) ?></td>
                  <td class="text-end">
                    <a href="add_medicine.php?edit_id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    <a href="add_medicine.php?delete_id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this medicine?');">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="p-3 mb-0">No medicines found. Start adding above!</p>
      <?php endif; ?>
    </div>
  </div>

</div>
<div class="text-center mt-4 mb-5">
  <a href="dashboard.php" class="btn btn-custom" style="min-width:150px;">&#8592; Back to Dashboard</a>
</div>

<!-- Enhanced Alarm Modal -->
<div class="modal fade" id="medicineAlarmModal" tabindex="-1" aria-labelledby="medicineAlarmLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="medicineAlarmLabel">
          <span class="alarm-icon">⏰</span> Medicine Reminder!
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="alarmContent"></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Set min/max for date pickers - using local time
  const todayDate = new Date();
  const year = todayDate.getFullYear();
  const month = String(todayDate.getMonth() + 1).padStart(2, '0');
  const day = String(todayDate.getDate()).padStart(2, '0');
  const today = `${year}-${month}-${day}`;
  
  const fiveYearsLater = new Date();
  fiveYearsLater.setFullYear(fiveYearsLater.getFullYear() + 5);
  const maxYear = fiveYearsLater.getFullYear();
  const maxMonth = String(fiveYearsLater.getMonth() + 1).padStart(2, '0');
  const maxDay = String(fiveYearsLater.getDate()).padStart(2, '0');
  const maxDate = `${maxYear}-${maxMonth}-${maxDay}`;
  
  console.log('Today\'s date (local):', today);

  const fromInput = document.getElementById('from_date');
  const toInput = document.getElementById('to_date');
  if (fromInput && toInput) {
    fromInput.min = today;
    fromInput.max = maxDate;
    toInput.max = maxDate;
    toInput.min = fromInput.value || today;
    fromInput.addEventListener('change', () => { toInput.min = fromInput.value || today; });
  }

  // Get today's medicines for JS
  const todayMedicines = <?php echo json_encode($todayMedicines); ?>;
  const repeatIntervalMinutes = 2; // Repeat reminder every 2 minutes
  let currentAlarmModal = null;
  let lastShownTime = {}; // Track when each medicine was last shown

  console.log('Today\'s medicines loaded:', todayMedicines);

  // FIXED: Check if medicine should show reminder
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

  // FIXED: Simplified reminder check - shows all overdue medicines
  function checkMedicineReminders() {
    const now = new Date();
    
    console.log('Checking reminders at:', now.toLocaleTimeString());
    
    // Find first pending medicine that needs reminder
    for (let medicine of todayMedicines) {
      if (medicine.daily_status === 'Pending') {
        const checkbox = document.querySelector(`.medicine-checkbox[data-id="${medicine.id}"]`);
        
        if (checkbox && !checkbox.checked && shouldShowReminder(medicine.time, medicine.id)) {
          console.log('Showing reminder for:', medicine.name, 'at', medicine.time);
          lastShownTime[medicine.id] = new Date();
          showAlarmPopup(medicine);
          break; // Show only one reminder at a time
        }
      }
    }
  }

  // Enhanced alarm popup with better formatting
  function showAlarmPopup(medicine) {
    const dosageText = medicine.dose || 'As prescribed';
    const timeFormatted = formatTime(medicine.time);
    
    // Close existing modal if open
    if (currentAlarmModal) {
      try {
        currentAlarmModal.hide();
      } catch (e) {
        console.log('Could not hide previous modal');
      }
    }
    
    document.getElementById('alarmContent').innerHTML = `
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
            ⚠️ This reminder will repeat every 2 minutes until you mark it as taken.
          </p>
        </div>
      </div>
    `;
    
    // Play notification sound
    playNotificationSound();
    
    // Show modal
    currentAlarmModal = new bootstrap.Modal(document.getElementById('medicineAlarmModal'));
    currentAlarmModal.show();
    
    // Request browser notification permission
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

  // Escape HTML to prevent XSS
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

  // Mark as taken via AJAX
  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('.medicine-checkbox').forEach(chk => {
      chk.addEventListener('change', function() {
        if(this.checked) {
          const medId = this.dataset.id;
          fetch(window.location.href, {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ taken_id: medId })
          })
          .then(res => res.text())
          .then((responseText) => {
            console.log('Server response:', responseText);
            
            if (responseText.trim() === 'DONE') {
              this.disabled = true;
              const badge = this.closest('.list-group-item').querySelector('.badge');
              badge.textContent = 'Taken';
              badge.className = 'badge badge-taken';
              
              // Close any open reminder modal for this medicine
              if (currentAlarmModal) {
                currentAlarmModal.hide();
              }
              
              // Clear the last shown time so it won't show again
              delete lastShownTime[parseInt(medId)];
              
              console.log('Medicine marked as taken:', medId);
              
              // Show success message
              alert('✅ Medicine marked as taken!');
            } else {
              throw new Error('Unexpected response: ' + responseText);
            }
          })
          .catch((error) => {
            console.error('Error:', error);
            alert('❌ Failed to update status. Try again.');
            this.checked = false; // Uncheck the checkbox
          });
        }
      });
    });
  });
</script>
</body>
</html>