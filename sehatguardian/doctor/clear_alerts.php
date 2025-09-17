<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/sehatguardian/includes/auth.php');
checkAuth('doctor');
require_once($_SERVER['DOCUMENT_ROOT'].'/sehatguardian/includes/db_connect.php');

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['doctor_id']) || $input['doctor_id'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

$doctor_id = intval($input['doctor_id']);

// Clear all alerts for patients assigned to this doctor with status = 'Sent'
$sql = "UPDATE emergency_alerts ea
        JOIN appointments a ON ea.patient_id = a.patient_id
        SET ea.status = 'Cleared'
        WHERE a.doctor_id = ? AND ea.status = 'Sent' AND a.status IN ('Approved','Completed')";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $doctor_id);
$success = $stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['success' => $success]);
