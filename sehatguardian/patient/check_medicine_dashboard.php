<?php
require_once('../includes/db_connect.php');
$id = intval($_GET['id'] ?? 0);
$status = "";
if ($id > 0) {
    $q = $conn->prepare("SELECT status FROM medicine_schedule WHERE id=? LIMIT 1");
    $q->bind_param("i", $id);
    $q->execute();
    $result = $q->get_result();
    if ($row = $result->fetch_assoc()) $status = $row['status'];
}
echo json_encode(['status' => $status]);
?>
