
<?php
session_start();
require_once('../includes/db_connect.php');


header('Content-Type: application/json');


// Verify user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode([]);
    exit;
}


$result = $conn->query("SELECT alert_id, patient_id, alert_time, location, message FROM emergency_alerts WHERE status='Sent' ORDER BY alert_time DESC");


$alerts = [];
while ($row = $result->fetch_assoc()) {
    $alerts[] = $row;
}


echo json_encode($alerts);


$conn->close();
?> 