<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

$patient_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
  SELECT appointment_id, appointment_date, appointment_time, doctor_id
  FROM appointments
  WHERE patient_id = ? AND status = 'approved'
  ORDER BY appointment_date DESC, appointment_time DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Approved Appointments - Payment</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #004d40;
      background: #f4f6fb;
      padding: 20px;
      margin: 0;
    }
    h2 {
      color: #00796b;
      margin-bottom: 24px;
      font-weight: 700;
      font-size: 1.8rem;
      text-align: center;
    }
    ul.appointment-list {
      list-style: none;
      padding-left: 0;
      max-width: 600px;
      margin: 0 auto;
    }
    ul.appointment-list li {
      background: #e0f2f1;
      padding: 16px 20px;
      margin-bottom: 16px;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0, 121, 107, 0.15);
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 1.1rem;
      transition: transform 0.2s ease;
    }
    ul.appointment-list li:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 12px rgba(0, 121, 107, 0.25);
    }
    ul.appointment-list li .date-time {
      color: #004d40;
      font-weight: 600;
    }
    ul.appointment-list li a.pay-link {
      background-color: #00796b;
      color: #ffffff;
      padding: 10px 18px;
      border-radius: 6px;
      font-weight: 600;
      text-decoration: none;
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
      box-shadow: 0 3px 6px rgba(0, 121, 107, 0.4);
    }
    ul.appointment-list li a.pay-link:hover {
      background-color: #004d40;
      box-shadow: 0 4px 12px rgba(0, 77, 64, 0.6);
    }
    p.no-data {
      color: #00796b;
      font-weight: 600;
      font-size: 1.2rem;
      text-align: center;
      margin-top: 40px;
    }
    /* Back to Home button */
    .back-home {
      position: absolute;
      top: 20px;
      right: 20px;
      background-color: #00897b;
      color: #ffffff;
      padding: 10px 16px;
      border-radius: 6px;
      font-weight: 600;
      text-decoration: none;
      box-shadow: 0 3px 6px rgba(0, 121, 107, 0.3);
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }
    .back-home:hover {
      background-color: #004d40;
      box-shadow: 0 4px 12px rgba(0, 77, 64, 0.6);
    }
  </style>
</head>
<body>
  <!-- Back to Home Button -->
  <a href="dashboard.php" class="back-home">🏠 Home</a>

  <h2>Your Approved Appointments</h2>

  <?php if ($result->num_rows > 0): ?>
    <ul class="appointment-list">
      <?php while ($row = $result->fetch_assoc()): ?>
        <li>
          <div class="date-time">
            <?= date("M d, Y", strtotime($row['appointment_date'])) ?> at <?= date("h:i A", strtotime($row['appointment_time'])) ?>
          </div>
          <a href="payment.php?appointment_id=<?= $row['appointment_id'] ?>" class="pay-link" aria-label="Pay for appointment on <?= date("M d, Y", strtotime($row['appointment_date'])) ?>">
            Pay Now
          </a>
        </li>
      <?php endwhile; ?>
    </ul>
  <?php else: ?>
    <p class="no-data">No approved appointments for payment yet.</p>
  <?php endif; ?>
</body>
</html>
