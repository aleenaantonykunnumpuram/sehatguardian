<?php
session_start();
require_once('../includes/db_connect.php');

header('Content-Type: text/html; charset=UTF-8');

$is_ajax = (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_ajax) {
    header('Content-Type: application/json');

    // Verify user is logged in and is a patient
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $patient_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO emergency_alerts (patient_id) VALUES (?)");
    $stmt->bind_param("i", $patient_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Alert sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: '.$stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Emergency Alert</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4fbfb;
    margin: 0;
}
#alertBtn {
    background: #008080;
    color: #fff;
    padding: 18px 40px;
    border: none;
    border-radius: 8px;
    font-size: 1.3em;
    cursor: pointer;
    margin: 80px auto 0;
    display: block;
    box-shadow: 0 2px 8px #b2dfd9;
    transition: background 0.2s;
}
#alertBtn:hover {
    background: #009faf;
}
.alert {
    padding: 25px 30px;
    margin: 18px auto;
    width: 360px;
    color: #fff;
    background: #008080;
    border-radius: 7px;
    font-size: 1.1em;
    text-align: center;
    box-shadow: 0 1px 5px #bbf1ee;
    display: none;
}
.alert.error {
    background: #e74c3c;
}
#dashboardBtn {
    display: block;
    width: 200px;
    margin: 20px auto 0;
    padding: 14px 20px;
    background: #fff;
    color: #008080;
    border: 2px solid #008080;
    border-radius: 8px;
    text-align: center;
    font-size: 1.1em;
    text-decoration: none;
    transition: background 0.2s, color 0.2s;
    box-shadow: 0 2px 8px #b2dfd9;
}
#dashboardBtn:hover {
    background: #008080;
    color: #fff;
}

</style>
</head>
<body>

<button id="alertBtn">🚨 Send Emergency Alert</button>
<a href="dashboard.php" id="dashboardBtn">← Back to Dashboard</a>

<div id="alertBox" class="alert"></div>

<script>
document.getElementById('alertBtn').onclick = function() {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", window.location.href, true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4) {
            var box = document.getElementById('alertBox');
            try {
                var resp = JSON.parse(xhr.responseText);
                box.textContent = resp.message;
                box.className = 'alert' + (resp.success ? '' : ' error');
            } catch (e) {
                box.textContent = "Unexpected error.";
                box.className = "alert error";
            }
            box.style.display = 'block';
        }
    };
    xhr.send();
};
</script>
</body>
</html>
