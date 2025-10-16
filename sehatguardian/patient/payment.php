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

// -------------------- CARD PAYMENT --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cardholder'])) {
    $cardholder = trim($_POST['cardholder']);
    $cardnumber = preg_replace('/\s+/', '', $_POST['cardnumber']);
    $expiry     = trim($_POST['expiry']);
    $cvv        = trim($_POST['cvv']);
    $address    = trim($_POST['address']);
    $amount     = 160.00;
    $status     = 'processing';
    $payment_method = 'Card';

    if (empty($cardholder) || empty($cardnumber) || empty($expiry) || empty($cvv) || empty($address)) {
        $message = "<div class='message error'>All fields are required.</div>";
    } elseif (!preg_match('/^\d{16}$/', $cardnumber)) {
        $message = "<div class='message error'>Invalid card number.</div>";
    } elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
        $message = "<div class='message error'>Expiry must be in MM/YY format.</div>";
    } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
        $message = "<div class='message error'>Invalid CVV.</div>";
    } else {
        // Prevent duplicate payments
        $check = $conn->prepare("SELECT id FROM payments WHERE appointment_id = ? AND status IN ('processing','approved')");
        $check->bind_param("i", $appointment_id);
        $check->execute();
        $dup = $check->get_result();

        if ($dup->num_rows > 0) {
            $message = "<div class='message error'>A payment is already in process or approved for this appointment.</div>";
        } else {
            // Only store last 4 digits for security
            $card_last4 = substr($cardnumber, -4);

            $stmt = $conn->prepare("INSERT INTO payments 
                (patient_id, appointment_id, amount, payment_method, status, billing_name, card_last4, billing_address, payment_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iidsssss", 
                $patient_id, 
                $appointment_id, 
                $amount, 
                $payment_method, 
                $status, 
                $cardholder, 
                $card_last4, 
                $address
            );

            if ($stmt->execute()) {
                $message = "<div class='message success'>✅ Payment submitted successfully! Status: <b>Processing</b>.</div>";
            } else {
                $message = "<div class='message error'>Database error: " . htmlspecialchars($stmt->error) . "</div>";
            }
            $stmt->close();
        }
    }
}

// -------------------- QR PAYMENT --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['txn_id'])) {
    $txn_id = trim($_POST['txn_id']);
    $upi_id = trim($_POST['upi_id']);
    $amount = 160.00;
    $status = 'processing';
    $payment_method = 'QR';

    if (empty($txn_id)) {
        $message = "<div class='message error'>Transaction ID is required.</div>";
    } elseif (!preg_match('/^[A-Za-z0-9]{6,12}$/', $txn_id)) {
        $message = "<div class='message error'>Invalid transaction ID format.</div>";
    } else {
        $check = $conn->prepare("SELECT id FROM payments WHERE appointment_id = ? AND status IN ('processing','approved')");
        $check->bind_param("i", $appointment_id);
        $check->execute();
        $dup = $check->get_result();

        if ($dup->num_rows > 0) {
            $message = "<div class='message error'>A payment is already in process or approved for this appointment.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO payments 
                (patient_id, appointment_id, amount, payment_method, status, transaction_id, upi_id, payment_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iidssss", 
                $patient_id, 
                $appointment_id, 
                $amount, 
                $payment_method, 
                $status, 
                $txn_id, 
                $upi_id
            );

            if ($stmt->execute()) {
                $message = "<div class='message success'>✅ QR Payment submitted! Status: <b>Processing</b>.</div>";
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
    max-width: 550px;
    background: #fff;
    margin: 40px auto;
    border-radius: 16px;
    box-shadow: 0 4px 28px rgba(32,178,170,0.12);
    border: 1.5px solid #20b2aa;
    padding: 32px;
}
.topbar { text-align: right; margin-bottom: 10px; }
.topbar a { color: #1687a7; text-decoration: none; font-weight: 600; }
h2 { color: #1687a7; margin-bottom: 24px; text-align:left; }

.tabs {
    display: flex;
    justify-content: space-around;
    margin-bottom: 20px;
    border-bottom: 2px solid #20b2aa;
}
.tab {
    padding: 10px 20px;
    cursor: pointer;
    font-weight: 600;
    color: #1687a7;
}
.tab.active {
    border-bottom: 3px solid #1687a7;
    color: #004d40;
}
.tab-content { display: none; }
.tab-content.active { display: block; }

.form-label { display:block; margin-bottom:8px; font-weight:500; color:#1687a7; }
.form-item {
    width:100%; padding:11px; margin-bottom:18px;
    border-radius:8px; border:1.5px solid #20b2aa;
    font-size:1rem; background:#f7fcfe;
}
.btn-main {
    width:100%; background:linear-gradient(90deg,#20b2aa 0%,#1687a7 100%);
    color:#fff; font-weight:600; border:none; border-radius:8px;
    font-size:1.1rem; padding:13px 0; cursor:pointer;
}
.qr-section { text-align:center; padding:20px; }
.qr-section img { width:200px; height:200px; margin-bottom:15px; }
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

    <div class="tabs">
        <div class="tab active" data-tab="card">💳 Card Payment</div>
        <div class="tab" data-tab="qr">📱 QR Payment</div>
    </div>

    <!-- CARD PAYMENT TAB -->
    <div class="tab-content active" id="card">
        <form method="POST" autocomplete="off">
            <label class="form-label">Cardholder Name *</label>
            <input type="text" name="cardholder" class="form-item" required>

            <label class="form-label">Card Number *</label>
            <input type="text" name="cardnumber" class="form-item" maxlength="19" placeholder="1234 5678 9012 3456" required>

            <div style="display:flex; gap:16px;">
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
    </div>

    <!-- QR PAYMENT TAB -->
    <div class="tab-content" id="qr">
        <div class="qr-section">
            <img src="qr.png" alt="QR Code">
            <p><b>Scan this QR code with any UPI app</b></p>
            <p>UPI ID: <span style="color:#1687a7;">hospital@upi</span></p>
            <p>Amount: <b>$160.00</b></p>

            <form method="POST" autocomplete="off">
                <label class="form-label">Transaction ID / Reference Number *</label>
                <input type="text" name="txn_id" class="form-item" maxlength="12" placeholder="Enter 12-digit transaction ID" required>

                <label class="form-label">Your UPI ID (Optional)</label>
                <input type="text" name="upi_id" class="form-item" placeholder="yourname@upi">

                <button type="submit" class="btn-main">Submit Payment Details</button>
            </form>
        </div>
    </div>
</div>

<script>
// Tabs switching
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById(tab.dataset.tab).classList.add('active');
    });
});

// Format card number
document.querySelector("input[name='cardnumber']").addEventListener("input", function(e) {
    this.value = this.value.replace(/\D/g, "").replace(/(.{4})/g, "$1 ").trim();
});
</script>
</body>
</html>
