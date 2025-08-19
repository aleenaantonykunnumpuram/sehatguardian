<?php
session_start();
include '../includes/db_connect.php'; // Update the path if needed

// Only logged-in patients can pay
if (!isset($_SESSION['user_id'])) { // Uncomment role check if needed
    header("Location: ../login.php");
    exit();
}

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_SESSION['user_id'];
    $cardholder = trim($_POST['cardholder']);
    $cardnumber = trim($_POST['cardnumber']);
    $expiry    = trim($_POST['expiry']);
    $cvv       = trim($_POST['cvv']);
    $address   = trim($_POST['address']);
    $amount    = 160; // Set this dynamically as per booking if needed
    $status    = 'processing';

    // Simple validation
    if (empty($cardholder) || empty($cardnumber) || empty($expiry) || empty($cvv) || empty($address)) {
        $message = "<div class='message' style='color:red;background:#ffe0e0;border:1px solid #f55;'>All fields are required.</div>";
    } elseif (!preg_match('/^\d{16}$/', str_replace(' ', '', $cardnumber))) {
        $message = "<div class='message' style='color:red;background:#ffe0e0;border:1px solid #f55;'>Invalid card number. Use 16 digits.</div>";
    } elseif (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
        $message = "<div class='message' style='color:red;background:#ffe0e0;border:1px solid #f55;'>Expiry date must be MM/YY.</div>";
    } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
        $message = "<div class='message' style='color:red;background:#ffe0e0;border:1px solid #f55;'>Invalid CVV.</div>";
    } else {
        // Store payment (simulate, do NOT store card details real-world!)
        $stmt = $conn->prepare("INSERT INTO payments (patient_id, amount, status, payment_date, billing_name, billing_card, billing_expiry, billing_address) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)");
        $stmt->bind_param("idsssss", $patient_id, $amount, $status, $cardholder, $cardnumber, $expiry, $address);

        if ($stmt->execute()) {
            $message = "<div class='message'>Payment submitted! Status: <b>Processing</b>. You'll be notified after admin review.</div>";
        } else {
            $message = "<div class='message' style='color:red;background:#ffe0e0;border:1px solid #f55;'>Something went wrong. Try again.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment Information</title>
  <style>
    body {
      background: #f4f6fb;
      font-family: 'Segoe UI', Arial, sans-serif;
    }
    .main-container {
      max-width: 500px;
      background: #fff;
      margin: 40px auto;
      border-radius: 16px;
      box-shadow: 0 4px 28px rgba(32,178,170,0.12);
      border: 1.5px solid #20b2aa;
      padding: 32px;
      position: relative;
    }
    .topbar {
      text-align: right;
      margin-bottom: 10px;
    }
    .topbar a {
      color: #1687a7;
      text-decoration: none;
      font-weight: 600;
    }
    .topbar a:hover {
      text-decoration: underline;
    }
    h2 {
      color: #1687a7;
      text-align: left;
      margin-bottom: 24px;
    }
    .form-label {
      display: block;
      margin-bottom: 10px;
      font-weight: 500;
      color: #1687a7;
    }
    .form-item {
      width: 100%;
      padding: 11px;
      margin-bottom: 18px;
      border-radius: 8px;
      border: 1.5px solid #20b2aa;
      font-size: 1rem;
      background: #f7fcfe;
      transition: border 0.2s;
    }
    .form-item:focus {
      border-color: #1687a7;
      outline: none;
    }
    .form-row {
      display: flex;
      gap: 16px;
      margin-bottom: 18px;
    }
    .btn-main {
      width: 100%;
      background: linear-gradient(90deg, #20b2aa 0%, #1687a7 100%);
      color: #fff;
      font-weight: 600;
      border: none;
      border-radius: 8px;
      font-size: 1.1rem;
      padding: 13px 0;
      cursor: pointer;
      margin-top: 10px;
      transition: background 0.2s;
    }
    .btn-main:hover {
      background: linear-gradient(90deg, #1687a7 0%, #20b2aa 100%);
    }
    .desc {
      margin-top: 20px;
      color: #666;
      text-align: center;
      font-size: 1rem;
    }
    .message {
      padding: 12px;
      border-radius: 7px;
      background: #e7fbfa;
      color: #1687a7;
      border: 1px solid #20b2aa;
      text-align: center;
      margin-bottom: 18px;
    }
  </style>
</head>
<body>
  <div class="main-container">
    <div class="topbar">
      <a href="dashboard.php">← Back to Dashboard</a>
    </div>
    <h2>Payment Information</h2>
    <?php if ($message) echo $message; ?>
    <form method="POST" action="" id="paymentForm" autocomplete="off">
      <label class="form-label">Cardholder Name *</label>
      <input type="text" name="cardholder" class="form-item" required>

      <label class="form-label">Card Number *</label>
      <input type="text" name="cardnumber" class="form-item" maxlength="19" placeholder="1234 5678 9012 3456" required>

      <div class="form-row">
        <div style="flex:2">
          <label class="form-label">Expiry Date *</label>
          <input type="text" name="expiry" class="form-item" placeholder="MM/YY" maxlength="5" required>
        </div>
        <div style="flex:1">
          <label class="form-label">CVV *</label>
          <input type="text" name="cvv" class="form-item" maxlength="4" required>
        </div>
      </div>

      <label class="form-label">Billing Address *</label>
      <input type="text" name="address" class="form-item" required>
      <button type="submit" class="btn-main">Pay $160 & Confirm Booking</button>
    </form>
    <div class="desc">
      <span>🔒 Your payment is secure and encrypted.<br>
      By proceeding, you agree to our Terms & Policy.</span>
    </div>
  </div>
  <script>
    document.getElementById('paymentForm').onsubmit = function(){
      var msgblock = document.querySelector('.message');
      if(!msgblock) {
        var n = document.createElement('div');
        n.className = 'message';
        n.innerHTML = 'Processing payment...';
        document.querySelector('.main-container').prepend(n);
      } else {
        msgblock.innerHTML = "Processing payment...";
      }
    };
  </script>
</body>
</html>
