<?php
session_start();
require_once('../includes/db_connect.php');

// Check if logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_admin.php');
    exit();
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['reply']) && !empty($_POST['feedback_id'])) {
    $reply = trim($_POST['reply']);
    $feedback_id = intval($_POST['feedback_id']);
    $stmt = $conn->prepare("UPDATE feedback SET reply=?, status='Replied' WHERE feedback_id=?");
    $stmt->bind_param("si", $reply, $feedback_id);
    $stmt->execute();
    $stmt->close();
    $msg = "Reply sent successfully!";
}

// Fetch all feedback with patient info
$sql = "SELECT f.feedback_id, f.message, f.reply, f.status, f.created_at, u.username AS patient_name
        FROM feedback f
        JOIN users u ON f.patient_id = u.user_id
        ORDER BY f.created_at DESC";
$result = $conn->query($sql);
$feedbacks = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin Feedback Management</title>
<style>
  body {
    background-color: #e0f7fa;
    font-family: Arial, sans-serif;
    color: #004d40;
    max-width: 900px;
    margin: 20px auto;
    padding: 10px 20px;
  }
  h2 {
    color: #00796b;
    text-align: center;
    margin-bottom: 25px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 25px;
  }
  th, td {
    padding: 12px 15px;
    border: 1px solid #008080;
    text-align: left;
    vertical-align: top;
  }
  th {
    background-color: #008080;
    color: white;
  }
  tr:nth-child(even) {
    background-color: #f0fbfb;
  }
  .reply-form textarea {
    width: 100%;
    height: 60px;
    resize: vertical;
    border-radius: 6px;
    border: 1.5px solid #008080;
    padding: 6px 8px;
    font-size: 14px;
  }
  .btn-submit-reply {
    background-color: #008080;
    color: white;
    padding: 8px 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 6px;
    font-weight: bold;
  }
  .btn-submit-reply:hover {
    background-color: #006d6d;
  }
  .status-tag {
    font-weight: bold;
    color: #00796b;
  }
  .message {
    text-align: center;
    font-weight: bold;
    margin-bottom: 15px;
    color: green;
  }
</style>
</head>
<body>

<h2>Patient Feedback Management</h2>

<?php if (!empty($msg)): ?>
  <div class="message"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>Patient</th>
      <th>Message</th>
      <th>Reply</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
  <?php if (count($feedbacks) > 0): ?>
    <?php foreach ($feedbacks as $fb): ?>
      <tr>
        <td><?= htmlspecialchars($fb['patient_name']) ?></td>
        <td><?= nl2br(htmlspecialchars($fb['message'])) ?></td>
        <td><?= nl2br(htmlspecialchars($fb['reply'] ?? '')) ?></td>
        <td class="status-tag"><?= htmlspecialchars($fb['status']) ?></td>
        <td>
          <?php if($fb['status'] === 'Pending'): ?>
            <form class="reply-form" method="post" action="">
              <input type="hidden" name="feedback_id" value="<?= $fb['feedback_id'] ?>">
              <textarea name="reply" placeholder="Write your reply here..." required></textarea>
              <button type="submit" class="btn-submit-reply">Send Reply</button>
            </form>
          <?php else: ?>
            <em>Replied</em>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php else: ?>
    <tr><td colspan="5" style="text-align:center;">No feedback found.</td></tr>
  <?php endif; ?>
  </tbody>
</table>

<a href="dashboard.php" style="display:inline-block; margin-top:20px; background:#008080; color:#fff; padding:10px 18px; border-radius:8px; text-decoration:none; font-weight:bold;">← Back to Dashboard</a>

</body>
</html>
