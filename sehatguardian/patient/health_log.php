<?php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];
$msg = "";

// Handle new log submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_date = $_POST['log_date'] ?? date('Y-m-d');
    $bp = $_POST['bp'] ?? '';
    $sugar = $_POST['sugar'] ?? '';
    $water_intake = $_POST['water_intake'] ?? 0;
    $sleep_hours = $_POST['sleep_hours'] ?? 0;

    // Insert into DB
    $stmt = $conn->prepare("INSERT INTO health_log (patient_id, log_date, bp, sugar, water_intake, sleep_hours) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssdd", $patient_id, $log_date, $bp, $sugar, $water_intake, $sleep_hours);
    $stmt->execute();
    $stmt->close();
    $msg = "Health log submitted!";
}

// Fetch previous logs
$logs = [];
$stmt = $conn->prepare("SELECT * FROM health_log WHERE patient_id = ? ORDER BY log_date DESC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $logs[] = $row;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Health Log</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body {
    background-color: #e0f7f5;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #004d40;
    padding: 20px;
  }
  .container {
    max-width: 700px;
    margin: auto;
    background: #ffffffdd;
    padding: 30px 25px;
    border-radius: 15px;
    box-shadow: 0 8px 16px rgba(32,178,170,0.25);
  }
  h2 {
    font-weight: 700;
    font-size: 2.2rem;
    color: #00695c;
    margin-bottom: 20px;
    text-align: center;
  }
  .alert-success {
    font-size: 1.1rem;
    border-radius: 10px;
  }
  label {
    font-weight: 600;
    font-size: 1.1rem;
    color: #004d40;
  }
  input[type="date"],
  input[type="text"],
  input[type="number"] {
    font-size: 1.2rem;
    padding: 10px 12px;
    border: 2.5px solid #20b2aa;
    border-radius: 10px;
    background-color: #def5f2;
    transition: border-color 0.3s ease;
  }
  input[type="date"]:focus,
  input[type="text"]:focus,
  input[type="number"]:focus {
    border-color: #00796b;
    outline: none;
  }
  .form-control {
    border: none;
    background-color: transparent;
  }
  .btn-primary {
    background: linear-gradient(90deg, #20b2aa, #00796b);
    border: none;
    font-size: 1.3rem;
    padding: 12px 0;
    border-radius: 12px;
    font-weight: 700;
    box-shadow: 0 6px 12px rgba(32,178,170,0.3);
    transition: background 0.3s ease;
  }
  .btn-primary:hover, .btn-primary:focus {
    background: linear-gradient(90deg, #00796b, #004d40);
    box-shadow: 0 8px 17px rgba(0,121,107,0.5);
  }
  table {
    margin-top: 30px;
    border-collapse: separate;
    border-spacing: 0 8px;
    width: 100%;
  }
  thead th {
    color: #00796b;
    font-weight: 700;
    font-size: 1.1rem;
    padding-bottom: 8px;
  }
  tbody tr {
    background-color: #e0f7f5cc;
    border-radius: 12px;
    box-shadow: inset 0 0 8px rgb(0 121 107 / 0.15);
  }
  tbody td {
    padding: 14px 15px;
    font-size: 1.1rem;
    color: #004d40;
  }
  .btn-link {
    font-size: 1.1rem;
    color: #00796b;
    margin-top: 25px;
    display: inline-block;
  }
  .btn-link:hover {
    text-decoration: underline;
    color: #004d40;
  }
  @media (max-width: 575.98px) {
    input[type="date"],
    input[type="text"],
    input[type="number"] {
      font-size: 1rem;
      padding: 8px 10px;
    }
    label {
      font-size: 1rem;
    }
    .btn-primary {
      font-size: 1.2rem;
      padding: 10px 0;
    }
    tbody td {
      font-size: 1rem;
      padding: 10px 12px;
    }
  }
</style>
</head>
<body>

<div class="container">
  <h2>🩺 Health Log</h2>

  <?php if ($msg): ?>
    <div class="alert alert-success" role="alert">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <form method="POST" class="row g-3">
    <div class="col-md-4">
      <label for="log_date" class="form-label">Date</label>
      <input type="date" id="log_date" name="log_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="col-md-4">
      <label for="bp" class="form-label">Blood Pressure</label>
      <input type="text" id="bp" name="bp" class="form-control" placeholder="Eg: 120/80">
    </div>
    <div class="col-md-4">
      <label for="sugar" class="form-label">Sugar Level</label>
      <input type="text" id="sugar" name="sugar" class="form-control" placeholder="Eg: 90 mg/dL">
    </div>
    <div class="col-md-6">
      <label for="water_intake" class="form-label">Water Intake (L)</label>
      <input type="number" id="water_intake" name="water_intake" step="0.1" class="form-control" placeholder="Eg: 2.5">
    </div>
    <div class="col-md-6">
      <label for="sleep_hours" class="form-label">Sleep Hours</label>
      <input type="number" id="sleep_hours" name="sleep_hours" step="0.1" class="form-control" placeholder="Eg: 7.5">
    </div>
    <div class="col-12 text-center">
      <button type="submit" class="btn btn-primary">Submit Log</button>
    </div>
  </form>

  <h4 class="mt-4" style="color: #00796b;">Your Previous Logs</h4>

  <table class="table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Blood Pressure</th>
        <th>Sugar Level</th>
        <th>Water Intake (L)</th>
        <th>Sleep Hours</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($logs)): ?>
        <?php foreach ($logs as $log): ?>
          <tr>
            <td><?= htmlspecialchars($log['log_date']) ?></td>
            <td><?= htmlspecialchars($log['bp']) ?></td>
            <td><?= htmlspecialchars($log['sugar']) ?></td>
            <td><?= htmlspecialchars($log['water_intake']) ?></td>
            <td><?= htmlspecialchars($log['sleep_hours']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="5" class="text-center text-muted">No logs found</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <a href="dashboard.php" class="btn btn-link">&larr; Back to Dashboard</a>
</div>

</body>
</html>
