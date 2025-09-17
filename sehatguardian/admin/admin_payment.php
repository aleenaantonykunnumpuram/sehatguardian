<?php
session_start();
include '../includes/db_connect.php'; // Adjust path as needed

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Approve/reject logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }
    $payment_id = intval($_POST['payment_id']);
    $action = $_POST['action'];
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE payments SET status='approved' WHERE id=?");
        $stmt->bind_param("i", $payment_id);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Payment #$payment_id has been approved.";
        } else {
            $_SESSION['admin_message'] = "Error approving payment #$payment_id.";
        }
        $stmt->close();
    } elseif ($action === 'reject' && !empty(trim($_POST['reason']))) {
        $reason = trim($_POST['reason']);
        $stmt = $conn->prepare("UPDATE payments SET status='rejected', rejection_reason=? WHERE id=?");
        $stmt->bind_param("si", $reason, $payment_id);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Payment #$payment_id has been rejected.";
        } else {
            $_SESSION['admin_message'] = "Error rejecting payment #$payment_id.";
        }
        $stmt->close();
    } else {
        $_SESSION['admin_message'] = "Rejection reason is required when rejecting a payment.";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch payments for review with patient username
$res = $conn->query("
    SELECT p.*, u.username AS patient_username 
    FROM payments p 
    JOIN users u ON p.patient_id = u.user_id 
    WHERE p.status='processing' 
    ORDER BY p.payment_date ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin - Review Payments</title>
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #e6f7fa; margin: 20px; }
    .message {
      font-weight: 600;
      margin-bottom: 15px;
      padding: 10px 15px;
      border-radius: 6px;
      max-width: 800px;
      margin-left: auto;
      margin-right: auto;
    }
    .message.success {
      background-color: #d0f0ed;
      color: #00796b;
      border: 1px solid #20b2aa;
    }
    .message.error {
      background-color: #ffd6d6;
      color: #cc3a1a;
      border: 1px solid #cc3a1a;
    }
    table {
      width: 100%;
      max-width: 900px;
      margin: auto;
      border-collapse: collapse;
      background: #fff;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    th, td {
      border: 1px solid #20b2aa;
      padding: 10px 12px;
      text-align: left;
      vertical-align: middle;
    }
    th {
      background: #20b2aa;
      color: #fff;
      font-weight: 700;
    }
    tr:nth-child(even) {
      background: #f7fcfe;
    }
    .btn {
      padding: 7px 20px;
      font-weight: 600;
      border-radius: 7px;
      border: none;
      cursor: pointer;
      font-size: 0.9rem;
      transition: background-color 0.3s ease;
    }
    .approve {
      background: #20b2aa;
      color: #fff;
      margin-right: 8px;
    }
    .approve:hover {
      background: #168986;
    }
    .reject {
      background: #cc3a1a;
      color: #fff;
    }
    .reject:hover {
      background: #992811;
    }
    input[type="text"].reason-input {
      padding: 6px 8px;
      border-radius: 6px;
      border: 1px solid #ccc;
      width: 180px;
      font-size: 0.9rem;
      margin-right: 6px;
    }
    .header-bar {
      max-width: 900px;
      margin: 20px auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
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
      background-color: #168986;
    }
  </style>
</head>
<body>

  <div class="header-bar">
    <h2>Pending Payments</h2>
    <a href="dashboard.php">Back to Dashboard</a>
  </div>

  <?php if (isset($_SESSION['admin_message'])): ?>
    <div class="message <?= (strpos($_SESSION['admin_message'], 'Error') === 0 ? 'error' : 'success') ?>">
      <?= htmlspecialchars($_SESSION['admin_message']) ?>
    </div>
    <?php unset($_SESSION['admin_message']); ?>
  <?php endif; ?>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Patient ID</th>
        <th>Patient Name</th>
        <th>Amount</th>
        <th>Date</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($res->num_rows === 0): ?>
        <tr><td colspan="6" style="text-align:center; padding: 20px; font-style: italic; color: #00796b;">No pending payments found.</td></tr>
      <?php else: ?>
        <?php while ($row = $res->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['patient_id'] ?></td>
            <td><?= htmlspecialchars($row['patient_username']) ?></td>
            <td>$<?= number_format($row['amount'], 2) ?></td>
            <td><?= htmlspecialchars($row['payment_date']) ?></td>
            <td>
              <form method="post" style="display:inline;">
                <input type="hidden" name="payment_id" value="<?= $row['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button class="btn approve" name="action" value="approve" type="submit" title="Approve Payment">Approve</button>
              </form>
              <form method="post" style="display:inline;">
                <input type="hidden" name="payment_id" value="<?= $row['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="text" name="reason" placeholder="Rejection reason" required class="reason-input" aria-label="Rejection Reason">
                <button class="btn reject" name="action" value="reject" type="submit" title="Reject Payment">Reject</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php endif; ?>
    </tbody>
  </table>

</body>

</html>
