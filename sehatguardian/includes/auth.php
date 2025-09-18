<?php
// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

function checkAuth($roleRequired = null) {
    // Not logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header('Location: /sehatguardian/login_admin.php');
        exit();
    }

    if ($roleRequired && $_SESSION['role'] !== $roleRequired) {
        header('Location: /sehatguardian/login_admin.php');
        exit();
    }
}
?>
