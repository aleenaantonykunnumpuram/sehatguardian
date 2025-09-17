<?php
session_start();
require_once('../includes/db_connect.php');

// Redirect if not logged in as patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ../login.php');
    exit();
}

$patient_id = $_SESSION['user_id'];
$msg = "";

// Handle feedback form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $message = trim($_POST['message']);
    $stmt = $conn->prepare("INSERT INTO feedback (patient_id, message) VALUES (?, ?)");
    $stmt->bind_param("is", $patient_id, $message);
    if ($stmt->execute()) {
        $msg = "Feedback submitted successfully!";
    } else {
        $msg = "Failed to submit feedback. Please try again.";
    }
    $stmt->close();
}

// Fetch existing feedback by this patient
$stmt = $conn->prepare("SELECT message, reply, status, created_at FROM feedback WHERE patient_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$feedbacks = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Submit Feedback - Sehat Guardian</title>
<style>
  body {
    background-color: #e0f7fa;
    font-family: Arial, sans-serif;
    color: #004d40;
    padding: 20px;
    max-width: 600px;
    margin: auto;
  }
  h2 {
    color: #00796b;
    text-align: center;
  }
  form {
    background: #ffffffdd;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 8px 16px rgba(32,178,170,0.25);
    margin-bottom: 30px;
  }
  textarea {
    width: 100%;
    height: 100px;
    border: 2px solid #008080;
    border-radius: 8px;
    padding: 10px;
    font-size: 16px;
    resize: vertical;
    margin-bottom: 15px;
  }
  button, .btn-back {
    background-color: #008080;
    color: #fff;
    border: none;
    padding: 12px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    width: 100%;
    text-align: center;
    display: inline-block;
    text-decoration: none;
    font-weight: bold;
  }
  button:hover, .btn-back:hover {
    background-color: #006d6d;
  }
  .message {
    text-align: center;
    font-weight: bold;
    margin-bottom: 15px;
  }
  .feedback-list {
    list-style: none;
    padding: 0;
  }
  .feedback-item {
    background: #f0fbfb;
    border-left: 8px solid #008080;
    margin-bottom: 15px;
    padding: 12px 15px;
    border-radius: 8px;
  }
  .feedback-item p {
    margin: 6px 0;
  }
  .status {
    font-weight: bold;
    color: #00796b;
  }
  .reply {
    margin-top: 8px;
    padding: 10px;
    background: #def5f2;
    border-left: 4px solid #004d40;
    border-radius: 6px;
  }
  .back-container {
      max-width: 600px;
      margin: 20px auto;
  }
</style>
<script>
function validateForm() {
  const message = document.getElementById('message').value.trim();
  if (message === '') {
    alert('Please enter your feedback before submitting.');
    return false;
  }
  return true;
}
</script>
</head>
<body>

<h2>Submit Your Feedback / Complaint</h2>

<form method="post" onsubmit="return validateForm();">
  <textarea id="message" name="message" placeholder="Write your feedback or complaint here..." required></textarea>
  <button type="submit">Send Feedback</button>
</form>

<?php if ($msg): ?>
  <div class="message"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<h3>Your Previous Feedbacks</h3>

<?php if (count($feedbacks) > 0): ?>
  <ul class="feedback-list">
    <?php foreach ($feedbacks as $fb): ?>
      <li class="feedback-item">
        <p><strong>Message:</strong> <?= htmlspecialchars($fb['message']) ?></p>
        <p class="status">Status: <?= htmlspecialchars($fb['status']) ?></p>
        <?php if ($fb['reply']): ?>
          <div class="reply">
            <strong>Admin Reply:</strong> <?= htmlspecialchars($fb['reply']) ?>
          </div>
        <?php endif; ?>
        <small>Submitted on: <?= htmlspecialchars($fb['created_at']) ?></small>
      </li>
    <?php endforeach; ?>
  </ul>
<?php else: ?>
  <p>You have not submitted any feedback yet.</p>
<?php endif; ?>

<div class="back-container">
  <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
</div>

</body>
</html>
