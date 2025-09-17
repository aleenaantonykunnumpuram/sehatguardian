<?php
session_start();
include '../includes/db_connect.php';

// Redirect if patient not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];

// Fetch appointment history including appointment_id for receipt link
$stmt1 = $conn->prepare("
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, 
           u.username AS doctor_name, dp.specialization
    FROM appointments a
    JOIN users u ON a.doctor_id = u.user_id
    LEFT JOIN doctor_profile dp ON dp.doctor_id = u.user_id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt1->bind_param("i", $patient_id);
$stmt1->execute();
$result1 = $stmt1->get_result();


// Fetch payment history for this patient
$stmt2 = $conn->prepare("
    SELECT id, amount, payment_date, status, rejection_reason 
    FROM payments 
    WHERE patient_id = ? 
    ORDER BY payment_date DESC
");
$stmt2->bind_param("i", $patient_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Patient Dashboard - History</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f6fb;
    margin: 20px;
    color: #004d40;
  }
  h2 {
    color: #1687a7;
    margin-top: 40px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 30px;
  }
  th, td {
    padding: 12px 15px;
    border: 1px solid #ddd;
    text-align: left;
  }
  thead {
    background-color: #20b2aa;
    color: white;
  }
  tbody tr:nth-child(even) {
    background-color: #f7fcfe;
  }
  .no-data {
    margin-top: 20px;
    font-size: 1.1rem;
    color: #666;
  }
  .btn {
    display: inline-block;
    padding: 6px 12px;
    background-color: #20b2aa;
    color: #fff;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.85rem;
    margin-left: 8px;
  }
  .btn:hover {
    background-color: #1687a7;
  }
  /* Payment status styling – blue theme */
  .status-approved {
      color: #0d47a1;
      font-weight: bold;
  }
  .status-rejected {
      color: #b71c1c;
      font-weight: bold;
  }
  .status-processing {
      color: #1565c0;
      font-weight: normal;
      font-style: italic;
  }
  .rejection-reason {
      font-size: 0.9rem;
      color: #b71c1c;
      margin-top: 4px;
  }
  .no-data {
      margin-top: 20px;
      font-size: 1.1rem;
      color: #666;
  }
  /* Header bar with back button for navigation */
  .header-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
  }
  .header-bar a {
      background: #20b2aa;
      color: #fff;
      padding: 8px 15px;
      border-radius: 7px;
      font-weight: 600;
      text-decoration: none;
      transition: background-color 0.3s ease;
  }
  .header-bar a:hover {
      background-color: #1687a7;
  }
</style>
<!-- Load Font Awesome CDN for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>

<div class="header-bar">
  <h1>Patient Dashboard</h1>
  <a href="dashboard.php">Back to Dashboard</a>
</div>

<h2>Your Appointment History</h2>
<?php if ($result1->num_rows > 0): ?>
  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Time</th>
        <th>Doctor</th>
        <th>Specialization</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result1->fetch_assoc()): ?>
        <tr>
          <td><?= date("M d, Y", strtotime($row['appointment_date'])) ?></td>
          <td><?= date("h:i A", strtotime($row['appointment_time'])) ?></td>
          <td><?= htmlspecialchars($row['doctor_name']) ?></td>
          <td><?= htmlspecialchars($row['specialization'] ?? '-') ?></td>
          <td style="text-transform: capitalize;">
            <?= htmlspecialchars($row['status']) ?>
            <?php if (strtolower($row['status']) === 'approved'): ?>
              <a href="download_pdf.php?appointment_id=<?= $row['appointment_id'] ?>" class="btn" target="_blank" rel="noopener">
                <i class="fas fa-file-download"></i> Download Receipt
              </a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
<?php else: ?>
  <p class="no-data">You have no appointment history.</p>
<?php endif; ?>

<h2>Your Payment History</h2>
<?php if ($result2->num_rows > 0): ?>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Amount</th>
        <th>Date</th>
        <th>Status</th>
        <th>Rejection Reason</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result2->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['id']) ?></td>
          <td>$<?= number_format($row['amount'], 2) ?></td>
          <td><?= htmlspecialchars($row['payment_date']) ?></td>
          <td class="status-<?= htmlspecialchars($row['status']) ?>">
            <?= ucfirst(htmlspecialchars($row['status'])) ?>
          </td>
          <td>
            <?php if ($row['status'] === 'rejected' && !empty($row['rejection_reason'])): ?>
              <div class="rejection-reason"><?= htmlspecialchars($row['rejection_reason']) ?></div>
            <?php else: ?>
              &mdash;
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
<?php else: ?>
  <p class="no-data">You have no payment history.</p>
<?php endif; ?>

</body>

</html>

<?php
$stmt1->close();
$stmt2->close();
?>
