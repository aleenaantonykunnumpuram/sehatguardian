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

// Add or Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $stmt = $conn->prepare("DELETE FROM medicine_schedule WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $delete_id, $patient_id);
    $stmt->execute();
    $stmt->close();
    header("Location: add_medicine.php");
    exit();
}

// Fetch All
$medicines = [];
$stmt = $conn->prepare("SELECT * FROM medicine_schedule WHERE patient_id = ? ORDER BY from_date ASC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $medicines[] = $row;
}
$stmt->close();

// Fetch today's medicines for alarm
$todayMedicines = [];
$today = date('Y-m-d');
foreach ($medicines as $row) {
    if (
        $row['status'] === 'Pending' &&
        $row['from_date'] <= $today &&
        $row['to_date'] >= $today
    ) {
        $todayMedicines[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title>Manage Medicines | Sehat Guardian</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f5f7fa; }
    .card { border-radius: 10px; }
    .card-header { font-weight: bold; }
    .btn-custom { min-width: 90px; }
  </style>
</head>
<body>
<div class="container py-5">
  <h2 class="mb-4 text-primary">📋 Manage Your Medicines</h2>
  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white">
      <?= $edit_medicine ? "Edit Medicine" : "Add New Medicine" ?>
    </div>
    <div class="card-body">
      <form method="POST" class="row g-3">
        <input type="hidden" name="update_id" value="<?= $edit_medicine ? htmlspecialchars($edit_medicine['id']) : '' ?>">
        <div class="col-md-6">
          <label class="form-label">Medicine Name</label>
          <input type="text" name="name" class="form-control" placeholder="Eg: Paracetamol" required value="<?= $edit_medicine ? htmlspecialchars($edit_medicine['name']) : '' ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Dosage</label>
          <input type="text" name="dose" class="form-control" placeholder="Eg: 500mg" required value="<?= $edit_medicine ? htmlspecialchars($edit_medicine['dose']) : '' ?>">
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
          <button type="submit" class="btn btn-success btn-custom"><?= $edit_medicine ? "Update" : "Add" ?></button>
          <?php if ($edit_medicine): ?>
            <a href="add_medicine.php" class="btn btn-secondary ms-2 btn-custom">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
  <div class="card shadow-sm">
    <div class="card-header bg-dark text-white">
      📅 Your Medicine Schedule
    </div>
    <div class="card-body p-0">
      <?php if ($medicines): ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Medicine</th>
                <th>Dosage</th>
                <th>Time</th>
                <th>From</th>
                <th>To</th>
                <th class="text-end">Actions</th>
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
                    <a href="add_medicine.php?edit_id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary btn-custom">Edit</a>
                    <a href="add_medicine.php?delete_id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger btn-custom" onclick="return confirm('Are you sure you want to delete this medicine?');">Delete</a>
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
  <a href="dashboard.php" class="btn btn-link mt-3">&larr; Back to Dashboard</a>
</div>

<!-- ALARM Modal -->
<div class="modal fade" id="medicineAlarmModal" tabindex="-1" aria-labelledby="medicineAlarmLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title" id="medicineAlarmLabel">⏰ Medicine Reminder!</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="alarmContent">
        <!-- Content set by JS -->
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const today = new Date().toISOString().split('T')[0];
  const fiveYearsLater = new Date();
  fiveYearsLater.setFullYear(fiveYearsLater.getFullYear() + 5);
  const maxDate = fiveYearsLater.toISOString().split('T')[0];

  const fromInput = document.getElementById('from_date');
  const toInput = document.getElementById('to_date');
  fromInput.min = today;
  fromInput.max = maxDate;
  toInput.max = maxDate;
  toInput.min = fromInput.value || today;
  fromInput.addEventListener('change', () => {
    toInput.min = fromInput.value || today;
  });

  // Medicine alarm logic
  // Get medicines for today from PHP
  const todayMedicines = <?php echo json_encode($todayMedicines); ?>;

  todayMedicines.forEach(function(medicine) {
    // Get target time
    const now = new Date();
    const medicineTime = new Date();
    const [h, m, s] = medicine.time.split(':');
    medicineTime.setHours(h, m, s ? s : 0, 0);

    // If time today is still upcoming, set timeout
    if (medicineTime > now) {
      const timeoutMs = medicineTime.getTime() - now.getTime();
      setTimeout(function() {
        showAlarmPopup(medicine);
      }, timeoutMs);
    }
    // If already time, show immediately (if you reload at that time)
    else if (
      today === medicine.from_date &&
      today <= medicine.to_date &&
      medicine.status === 'Pending' &&
      now.getHours() === Number(h) && now.getMinutes() === Number(m)
    ) {
      showAlarmPopup(medicine);
    }
  });

  // Alarm pop-up function
  function showAlarmPopup(medicine) {
    const alarmContent = document.getElementById('alarmContent');
    alarmContent.innerHTML = `
      <div class="mb-2">
        <strong>It's time to take your medicine:</strong><br>
        <span class="fs-5 text-primary">${medicine.name}</span><br>
        <span class="text-secondary">Dosage: <b>${medicine.dose}</b></span><br>
        <span class="text-dark">Scheduled Time: <b>${medicine.time}</b></span>
      </div>
    `;
    const alarmModal = new bootstrap.Modal(document.getElementById('medicineAlarmModal'));
    alarmModal.show();
    // Optional: can play a sound here for extra notification
    // var audio = new Audio('alarm.mp3'); audio.play();
  }
</script>
</body>
</html>
