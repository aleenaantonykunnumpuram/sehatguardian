<?php
session_start();
//require_once 'auth.php';
require_once("includes/db_connect.php");
require_once("includes/auth.php");

// If already logged in, redirect to the correct dashboard
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'doctor') {
        header("Location: doctor/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'patient') {
        header("Location: patient/dashboard.php");
        exit();
    }
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role']; // Get role from form

    // Check user in database with selected role
    $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ? AND role = ?");
    $stmt->bind_param("ss", $username, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            // Disable caching
            header("Cache-Control: no-cache, no-store, must-revalidate");
            header("Pragma: no-cache");
            header("Expires: 0");

            // Redirect based on role
            if ($row['role'] === 'admin') {
                $_SESSION['admin_logged_in'] = true;
                header("Location: admin/dashboard.php");
            } elseif ($row['role'] === 'doctor') {
                header("Location: doctor/dashboard.php");
            } elseif ($row['role'] === 'patient') {
                header("Location: patient/dashboard.php");
            } else {
                $error = "Unknown role assigned!";
            }
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found with this role!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, #006d77, #83c5be);
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }
    .login-box {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0px 4px 20px rgba(0,0,0,0.15);
        width: 350px;
        text-align: center;
    }
    h2 {
        color: #006d77;
        margin-bottom: 20px;
    }
    input, select {
        width: 100%;
        padding: 12px;
        margin: 8px 0;
        border-radius: 6px;
        border: 1px solid #ccc;
        box-sizing: border-box;
    }
    .show-pass {
        display: flex;
        align-items: center;
        font-size: 14px;
        margin-top: 5px;
        margin-bottom: 10px;
        color: #333;
    }
    .show-pass input {
        margin-right: 6px;
        transform: scale(1.1);
    }
    button {
        background-color: #006d77;
        color: white;
        padding: 12px;
        width: 100%;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
    }
    button:hover {
        background-color: #004f52;
    }
    a {
        display: inline-block;
        margin-top: 15px;
        color: #006d77;
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }
    .error {
        color: red;
        margin-top: 10px;
    }
</style>
</head>
<body>
<div class="login-box">
    <h2><i class="fas fa-sign-in-alt"></i> Login</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" id="password" name="password" placeholder="Password" required>

        <select name="role" required>
            <option value="">Select Role</option>
            <option value="admin">Admin</option>
            <option value="doctor">Doctor</option>
            <option value="patient">Patient</option>
        </select>

        <label class="show-pass">
            <input type="checkbox" id="showPass" onclick="togglePassword()"> Show Password
        </label>

        <button type="submit">Login</button>
    </form>

    <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
</div>

<script>
function togglePassword() {
    var passField = document.getElementById("password");
    passField.type = (passField.type === "password") ? "text" : "password";
}
</script>
</body>
</html>
