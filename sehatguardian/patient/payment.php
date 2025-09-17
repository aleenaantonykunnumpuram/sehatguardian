<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];

// Validate appointment_id from GET
if (!isset($_GET['appointment_id']) || !is_numeric($_GET['appointment_id'])) {
    die("Invalid appointment.");
}
$appointment_id = intval($_GET['appointment_id']);

// Verify appointment belongs to patient and is approved for payment
$stmt = $conn->prepare("SELECT appointment_date, appointment_time 
                        FROM appointments 
                        WHERE appointment_id = ? AND patient_id = ? AND status = 'approved'");
$stmt->bind_param("ii", $appointment_id, $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Appointment not found or not approved for payment.");
}
$appointment = $result->fetch_assoc();

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_SESSION['user_id'];
    $cardholder = trim($_POST['cardholder']);
    $cardnumber = preg_replace('/\s+/', '', $_POST['cardnumber']); // remove spaces
    $expiry     = trim($_POST['expiry']);
    $cvv        = trim($_POST['cvv']);
    $address    = trim($_POST['address']);
    $amount     = 160.00; // Fixed or dynamic
    $status     = 'processing';

    // Basic validations
    if (empty($cardholder) || empty($cardnumber) || empty($expiry) || empty($cvv) || empty($address)) {
        $message = "<div class='message error'>All fields are required.</div>";
    } elseif (!preg_match('/^\d{16}$/', $cardnumber)) {
        $message = "<div class='message error'>Invalid card number. Must be exactly 16 digits.</div>";
    } elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
        $message = "<div class='message error'>Expiry must be in MM/YY format.</div>";
    } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
        $message = "<div class='message error'>Invalid CVV.</div>";
    } else {
        // Check if already paid
        $check = $conn->prepare("SELECT id FROM payments WHERE appointment_id = ? AND status IN ('processing','approved')");
        $check->bind_param("i", $appointment_id);
        $check->execute();
        $dup = $check->get_result();

        if ($dup->num_rows > 0) {
            $message = "<div class='message error'>A payment is already in process or approved for this appointment.</div>";
        } else {
            // Insert payment record
            $stmt = $conn->prepare("INSERT INTO payments 
                (patient_id, appointment_id, amount, status, payment_date, billing_name, billing_card, billing_expiry, billing_address) 
                VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?)");
            $stmt->bind_param("iidsssss", 
    $patient_id, 
    $appointment_id, 
    $amount, 
    $status, 
    $cardholder, 
    $cardnumber, 
    $expiry, 
    $address
);


            if ($stmt->execute()) {
                $message = "<div class='message success'>✅ Payment submitted successfully! Status: <b>Processing</b>. You'll be notified after admin review.</div>";
            } else {
                $message = "<div class='message error'>Database error: " . htmlspecialchars($stmt->error) . "</div>";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Payment for Appointment <?= htmlspecialchars($appointment_id) ?></title>
    <style>
        body {
            background: #f4f6fb;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #004d40;
        }
        .main-container {
            max-width: 500px;
            background: #fff;
            margin: 40px auto;
            border-radius: 16px;
            box-shadow: 0 4px 28px rgba(32,178,170,0.12);
            border: 1.5px solid #20b2aa;
            padding: 32px;
        }
        .topbar { text-align: right; margin-bottom: 10px; }
        .topbar a {
            color: #1687a7; text-decoration: none; font-weight: 600;
        }
        .topbar a:hover { text-decoration: underline; }
        h2 {
            color: #1687a7; margin-bottom: 24px; text-align:left;
        }
        .form-label { display:block; margin-bottom:8px; font-weight:500; color:#1687a7; }
        .form-item {
            width:100%; padding:11px; margin-bottom:18px;
            border-radius:8px; border:1.5px solid #20b2aa;
            font-size:1rem; background:#f7fcfe;
        }
        .form-row { display:flex; gap:16px; }
        .btn-main {
            width:100%; background:linear-gradient(90deg,#20b2aa 0%,#1687a7 100%);
            color:#fff; font-weight:600; border:none; border-radius:8px;
            font-size:1.1rem; padding:13px 0; cursor:pointer;
        }
        .btn-main:hover { background:linear-gradient(90deg,#1687a7 0%,#20b2aa 100%); }
        .desc { margin-top:20px; color:#666; text-align:center; font-size:0.95rem; }
        .message { padding:12px; border-radius:7px; text-align:center; margin-bottom:18px; }
        .message.success { background:#e7fbfa; color:#1687a7; border:1px solid #20b2aa; }
        .message.error { background:#ffe0e0; color:#a94442; border:1px solid #f55; }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="topbar">
            <a href="approve_appoinment.php">← Back to Approved Appointments</a>
        </div>
        <h2>Payment for Appointment on <?= date("M d, Y", strtotime($appointment['appointment_date'])) ?> 
            at <?= date("h:i A", strtotime($appointment['appointment_time'])) ?></h2>

        <?php if ($message) echo $message; ?>

        <form method="POST" id="paymentForm" autocomplete="off">
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
            🔒 Your payment is secure.<br>By proceeding, you agree to our Terms & Policy.
        </div>
    </div>

    <script>
        // Format card number spacing
        document.querySelector("input[name='cardnumber']").addEventListener("input", function(e) {
            this.value = this.value.replace(/\D/g, "").replace(/(.{4})/g, "$1 ").trim();
        });

        // Show "Processing payment..." during submit
        document.getElementById('paymentForm').onsubmit = function () {
            let msgblock = document.querySelector('.message');
            if (!msgblock) {
                msgblock = document.createElement('div');
                msgblock.className = 'message';
                document.querySelector('.main-container').prepend(msgblock);
            }
            msgblock.innerHTML = "Processing payment...";
        };
    </script>
</body>
</html>
