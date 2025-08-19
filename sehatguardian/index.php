<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sehat Guardian - Home</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: #f8f9fa;
    }
    /* Navbar */
    .navbar {
        background: #006d77;
        color: white;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }
    .navbar h1 {
        margin: 0;
        font-size: 22px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .navbar a {
        color: white;
        text-decoration: none;
        margin-left: 20px;
        font-weight: bold;
        transition: color 0.3s ease;
    }
    .navbar a:hover {
        color: #c0fdfb;
    }

    /* Hero Section */
    .hero {
        background: linear-gradient(to right, #006d77, #00afb9);
        color: white;
        padding: 60px 20px;
        text-align: center;
    }
    .hero h2 {
        font-size: 40px;
        margin-bottom: 10px;
    }
    .hero p {
        font-size: 18px;
        max-width: 600px;
        margin: auto;
    }
    .hero button {
        margin-top: 20px;
        background: white;
        color: #006d77;
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s;
    }
    .hero button:hover {
        background: #c0fdfb;
    }

    /* Features Section */
    .features {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        padding: 40px 20px;
    }
    .card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        box-shadow: 0px 4px 15px rgba(0,0,0,0.1);
        transition: transform 0.2s ease-in-out;
    }
    .card:hover {
        transform: scale(1.02);
    }
    .card i {
        font-size: 35px;
        color: #006d77;
        margin-bottom: 10px;
    }
    .card h3 {
        color: #006d77;
    }

    /* Footer */
    footer {
        background: #006d77;
        color: white;
        text-align: center;
        padding: 15px;
        margin-top: 20px;
    }
</style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <h1><i class="fas fa-heartbeat"></i> Sehat Guardian</h1>
    <div>
        <a href="login_admin.php">Login</a>
        <a href="register_patient.php">Register</a>
        <a href="about.php">About</a>
    </div>
</div>

<!-- Hero Section -->
<div class="hero">
    <h2>Welcome to Sehat Guardian</h2>
    <p>Your smart health management system for elderly care — stay healthy, stay safe, stay connected.</p>
    <button onclick="window.location.href='register_patient.php'">Get Started</button>
</div>

<!-- Features -->
<div class="features">
    <div class="card">
        <i class="fas fa-pills"></i>
        <h3>Medicine Reminders</h3>
        <p>Never miss your medication with timely reminders.</p>
    </div>
    <div class="card">
        <i class="fas fa-heart"></i>
        <h3>Health Tracking</h3>
        <p>Track BP, sugar levels, and daily health notes.</p>
    </div>
    <div class="card">
        <i class="fas fa-user-md"></i>
        <h3>Doctor Appointments</h3>
        <p>Book and manage doctor visits easily.</p>
    </div>
    <div class="card">
        <i class="fas fa-bell"></i>
        <h3>Emergency Alerts</h3>
        <p>Instant alerts to doctors and family in case of emergency.</p>
    </div>
</div>

<!-- Footer -->
<footer>
    <p>&copy; 2025 Sehat Guardian | All Rights Reserved</p>
</footer>

</body>
</html>
