<?php
// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unified authentication function
function checkAuth($roleRequired = null) {
    // Not logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header('Location: /sehatguardian/login.php');
        exit();
    }
    // Role check if needed
    if ($roleRequired && $_SESSION['role'] !== $roleRequired) {
        // Optionally redirect to forbidden or dashboard
        header('Location: /sehatguardian/login.php');
        exit();
    }
}
?>
