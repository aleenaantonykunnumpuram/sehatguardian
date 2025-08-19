<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/sehatguardian/includes/db_connect.php');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password_input = $_POST['password'];
    $phone = trim($_POST['phone']);

    // Basic validation for required fields
    if (!$username || !$email || !$password_input) {
        $message = "Please fill all required fields.";
    }
    // Validate email format using filter_var
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    }
    // Validate phone if provided: start with +91 and next digit 6-9 with total 13 chars (+91 + 10 digits)
    elseif ($phone && !preg_match('/^\+91[6-9]\d{9}$/', $phone)) {
        $message = "Phone number must start with +91 followed by a 10-digit number starting with 6,7,8 or 9.";
    }
    // Validate password: min 8 chars, at least 1 upper, 1 lower, 1 number, 1 special char
    elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?~\\/\\-]).{8,}$/', $password_input)) {
        $message = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
    } else {
        // Check if username or email exists
        $stmt_check = $conn->prepare("SELECT user_id FROM users WHERE username=? OR email=?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = "Username or Email already exists.";
        } else {
            $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);
            $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password, role, phone) VALUES (?, ?, ?, 'patient', ?)");
            $stmt_insert->bind_param("ssss", $username, $email, $hashed_password, $phone);

            if ($stmt_insert->execute()) {
                $patient_id = $stmt_insert->insert_id;
                // Create empty patient profile row
                $stmt_profile = $conn->prepare("INSERT INTO patient_profile (patient_id) VALUES (?)");
                $stmt_profile->bind_param("i", $patient_id);
                $stmt_profile->execute();
                $stmt_profile->close();

                $message = "Registration successful! You can now <a href='login.php'>login</a>.";
            } else {
                $message = "Registration failed. Please try again.";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Registration</title>
    <style>
        body { font-family: Arial, sans-serif; background: #e0f7f7; padding: 40px; }
        .form-container { max-width: 400px; margin: auto; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #006064; margin-bottom: 20px; }
        input[type=text], input[type=email], input[type=password], input[type=tel] {
            width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px;
        }
        button {
            background: #00838f; color: white; border: none; padding: 12px; width: 100%; border-radius: 5px; cursor: pointer;
            font-weight: bold;
        }
        button:hover { background: #006064; }
        .message { text-align: center; margin: 15px 0; color: red; }
        .message.success { color: green; }
        a { color: #006064; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Patient Registration</h2>
    <?php if($message): ?>
        <div class="message <?= strpos($message, 'successful') !== false ? 'success' : '' ?>"><?= $message ?></div>
    <?php endif; ?>
    <form method="POST" novalidate>
        <input type="text" name="username" placeholder="Username" required autofocus>
        <input type="email" name="email" placeholder="Email" required>
        <input type="tel" name="phone" placeholder="Phone (+91xxxxxxxxxx)" pattern="\+91[6-9][0-9]{9}" title="Phone should start with +91 followed by a 10-digit number starting with 6,7,8, or 9.">
        <input type="password" name="password" placeholder="Password" required
               pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?~\/\-]).{8,}$"
               title="Minimum 8 characters with at least 1 uppercase, 1 lowercase, 1 number, and 1 special character">
        <button type="submit">Register</button>
    </form>
    <p style="text-align:center; margin-top: 15px;">Already have an account? <a href="login_admin.php">Login here</a></p>
</div>
</body>
</html>
