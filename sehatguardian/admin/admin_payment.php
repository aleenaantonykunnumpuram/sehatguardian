<?php
session_start();
include '../includes/db_connect.php'; // Adjust path as needed

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Approve/reject logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = intval($_POST['payment_id']);
    $action = $_POST['action'];
    if ($action == 'approve') {
        $stmt = $conn->prepare("UPDATE payments SET status='approved' WHERE id=?");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
    } elseif ($action == 'reject' && !empty($_POST['reason'])) {
        $reason = $_POST['reason'];
        $stmt = $conn->prepare("UPDATE payments SET status='rejected', rejection_reason=? WHERE id=?");
        $stmt->bind_param("si", $reason, $payment_id);
        $stmt->execute();
    }
}

// Fetch payments for review
$res = $conn->query("SELECT * FROM payments WHERE status='processing' ORDER BY payment_date ASC");
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Admin - Review Payments</title>
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #e6f7fa; }
    table { width: 100%; border-collapse: collapse; background: #fff; }
    th, td { border: 1px solid #20b2aa; padding: 10px; text-align: left; }
    th { background: #20b2aa; color: #fff; }
    tr:nth-child(even) { background: #f7fcfe; }
    .btn { padding: 7px 20px; font-weight: 600; border-radius: 7px; border: none; cursor: pointer; }
    .approve { background: #20b2aa; color: #fff; }
    .reject { background: #cc3a1a; color: #fff; }
  </style>
</head>
<body>
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Pending Payments</h2>
    <a href="dashboard.php" style="text-decoration: none; background: #20b2aa; color: #fff; padding: 8px 15px; border-radius: 7px; font-weight: 600;">Back to Dashboard</a>
  </div>

  <table>
    <tr>
      <th>ID</th>
      <th>Patient ID</th>
      <th>Name</th>
      <th>Amount</th>
      <th>Date</th>
      <th>Action</th>
    </tr>
    <?php while ($row = $res->fetch_assoc()): ?>
    <tr>
      <td><?= $row['id'] ?></td>
      <td><?= $row['patient_id'] ?></td>
      <td><?= htmlspecialchars($row['billing_name']) ?></td>
      <td>$<?= $row['amount'] ?></td>
      <td><?= $row['payment_date'] ?></td>
      <td>
        <form method="post" style="display:inline;">
          <input type="hidden" name="payment_id" value="<?= $row['id'] ?>">
          <button class="btn approve" name="action" value="approve" type="submit">Approve</button>
        </form>
        <form method="post" style="display:inline;">
          <input type="hidden" name="payment_id" value="<?= $row['id'] ?>">
          <input type="text" name="reason" placeholder="Rejection reason" required>
          <button class="btn reject" name="action" value="reject" type="submit">Reject</button>
        </form>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
</body>

</html>
