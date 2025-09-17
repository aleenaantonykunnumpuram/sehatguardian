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

// Mark as Taken (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['taken_id'])) {
    $taken_id = intval($_POST['taken_id']);
    $update_stmt = $conn->prepare("UPDATE medicine_schedule SET status='Taken' WHERE id = ? AND patient_id = ?");
    $update_stmt->bind_param("ii", $taken_id, $patient_id);
    $update_stmt->execute();
    $update_stmt->close();
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
    $stmt = $conn->prepare("DELETE FROM medicine_schedule WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $delete_id, $patient_id);
    $stmt->execute();
    $stmt->close();
    header("Location: add_medicine.php");
    exit();
}

// All medicines and today's medicines
$medicines = [];
$stmt = $conn->prepare("SELECT * FROM medicine_schedule WHERE patient_id = ? ORDER BY from_date ASC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $medicines[] = $row;
}
$stmt->close();

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
      display: flex; align-items: center; justify-content: space-between;
      font-size: 1rem;
      border-radius: 6px;
      padding: 12px 16px;
      cursor: pointer;
    }
    /* Modal */
    .modal-header { background: #296dc1; color: #fff;}
    .modal-content { border-radius: 10px; }
    .btn-close-white { filter: brightness(0) invert(1); }
  </style>
</head>
<body>
<div class="container">

  <h2>Medicine Manager</h2>

  <!-- Medicine Checklist -->
  <div class="card">
    <div class="card-header">
      Today's Medicine Checklist
    </div>
    <div class="card-body">
      <?php if(count($todayMedicines) > 0): ?>
        <ul class="list-group" id="medicine-checklist" style="margin-bottom:0;">
          <?php foreach($todayMedicines as $med): ?>
            <li class="list-group-item">
              <div>
                <input type="checkbox" class="medicine-checkbox" data-id="<?= $med['id']; ?>" <?= $med['status'] === 'Taken' ? 'checked disabled' : ''; ?> />
                <span><?= htmlspecialchars($med['name']) ?> <small class="text-muted" style="font-weight:600;">at <?= date("h:i A", strtotime($med['time'])) ?></small></span>
              </div>
              <span class="badge <?= $med['status'] === 'Taken' ? 'badge-taken' : 'badge-pending' ?>"><?= $med['status'] ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p>No medicines scheduled for today.</p>
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
<div class="text-center mt-4">
  <a href="dashboard.php" class="btn btn-custom" style="min-width:150px;">&#8592; Back to Dashboard</a>
</div>

<!-- Alarm Modal -->
<div class="modal fade" id="medicineAlarmModal" tabindex="-1" aria-labelledby="medicineAlarmLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="medicineAlarmLabel">⏰ Medicine Reminder</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="alarmContent"></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Set min/max for date pickers
  const today = new Date().toISOString().split('T')[0];
  const fromInput = document.getElementById('from_date');
  const toInput = document.getElementById('to_date');
  fromInput.min = today;
  toInput.min = fromInput.value || today;
  fromInput.addEventListener('change', () => { toInput.min = fromInput.value || today; });

  // Get today's medicines for JS
  const todayMedicines = <?php echo json_encode($todayMedicines); ?>;
  const reminderWindowMinutes = 5;
  let shownMedicineIds = new Set();

  function isTimeInWindow(medicineTimeStr) {
    const now = new Date(), medicineTime = new Date();
    const [h, m] = medicineTimeStr.split(':');
    medicineTime.setHours(h, m, 0, 0);
    let diffMin = (now - medicineTime) / 60000;
    return diffMin >= 0 && diffMin <= reminderWindowMinutes;
  }

  function checkMedicineReminders() {
    const nowDateStr = new Date().toISOString().split('T')[0];
    todayMedicines.forEach((medicine) => {
      if (
        medicine.status === 'Pending' &&
        medicine.from_date <= nowDateStr &&
        medicine.to_date >= nowDateStr &&
        !shownMedicineIds.has(medicine.id) &&
        isTimeInWindow(medicine.time)
      ) {
        shownMedicineIds.add(medicine.id);
        showAlarmPopup(medicine);
      }
    });
  }
  checkMedicineReminders();
  setInterval(checkMedicineReminders, 30000);

  function showAlarmPopup(medicine) {
    document.getElementById('alarmContent').innerHTML = `
      <div>
        <b>It’s time to take your medicine:</b><br>
        <span style="font-size:1.15rem;color:#296dc1;">${medicine.name}</span><br>
        <span>Dosage: <b>${medicine.dose}</b></span><br>
        <span>Scheduled Time: <b>${medicine.time}</b></span>
      </div>
    `;
    (new bootstrap.Modal(document.getElementById('medicineAlarmModal'))).show();
  }

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
          .then(() => {
            this.disabled = true;
            this.closest('.list-group-item').querySelector('.badge').textContent = 'Taken';
            this.closest('.list-group-item').querySelector('.badge').className = 'badge badge-taken';
          })
          .catch(()=> alert('❌ Failed to update status. Try again.'));
        }
      });
    });
  });
</script>
</body>
</html>
