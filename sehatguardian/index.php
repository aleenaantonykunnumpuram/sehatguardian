

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sehat Guardian - Home</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
html, body {
    margin: 0;
    padding: 0;
    width: 100vw;
    height: 100vh;
    box-sizing: border-box;
}
body {
    font-family: Arial, sans-serif;
    background: #f8f9fa;
    width: 100vw;
    height: 100vh;
    overflow-x: hidden;
}

/* Navbar */
.navbar {
    background: #fff;
    padding: 10px 36px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1.5px solid #e7e7e7;
    box-shadow: 0 2px 12px rgba(34,71,124,0.035);
    z-index: 10;
    position: relative;
}
.logo {
    display: flex;
    align-items: center;
    font-size: 1.4rem;
    font-weight: bold;
    color: #22677c;
    gap: 11px;
    letter-spacing: 0.5px;
}
.navlinks a {
    color: #2a4253;
    text-decoration: none;
    margin-left: 24px;
    font-size: 17px;
    font-weight: 500;
    padding: 6px 18px;
    border-radius: 5px;
    transition: 0.2s;
}
.navlinks a:hover, .navlinks a.active {
    background: #e6f7fa;
    color: #006d77;
}
.navlinks .btn {
    background: #609daa;
    color: #fff !important;
    border-radius: 7px;
    font-weight: bold;
    margin-left: 32px;
    transition: 0.16s;
}
.navlinks .btn:hover {
    background: #36868b;
}

/* Slideshow Hero Section */
.hero-section {
    position: relative;
    width: 100vw;
    height: 92vh;
    min-height: 480px;
    max-width: 100vw;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: flex-start;
}
#slideshow-bg {
    position: absolute;
    top: 0; left: 0;
    width: 100vw; height: 100%;
    z-index: 0;
    overflow: hidden;
}
.slide {
    position: absolute;
    top: 0; left: 0;
    width: 100vw; height: 100%;
    object-fit: cover;
    opacity: 0;
    transition: opacity 1.2s ease;
    z-index: 1;
}
.slide.active {
    opacity: 1;
    z-index: 2;
}
.hero-overlay {
    position: absolute;
    top: 0; left: 0;
    width: 100vw; height: 100%;
    background: rgba(0,150,136,0.31); /* like the blue over your image */
    z-index: 3;
}
.hero-content {
    position: relative;
    z-index: 4;
    color: #fff;
    max-width: 650px;
    margin-left: 5vw;
}
.hero-content h1 {
    font-size: 3.6vw;
    font-weight: 800;
    margin: 0 0 12px 0;
    line-height: 1.07em;
    letter-spacing: 0.01em;
}
.hero-content p {
    font-size: 1.2vw;
    margin-bottom: 33px;
    line-height: 1.5;
    max-width: 90%;
}
.hero-actions {
    display: flex;
    gap: 18px;
}
.hero-actions .btn-main {
    background: #fff;
    color: #006d77;
    border: none;
    font-size: 1.1vw;
    border-radius: 8px;
    padding: 14px 34px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 16px #006d7717;
    transition: background 0.2s, color 0.2s;
}
.hero-actions .btn-main:hover {
    background: #daf6f4;
    color: #22677c;
}
.hero-actions .btn-outline {
    background: transparent;
    border: 2px solid #fff;
    color: #fff;
    font-size: 1.1vw;
    border-radius: 8px;
    padding: 14px 34px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
}
.hero-actions .btn-outline:hover {
    background: #fff;
    color: #22677c;
}

/* Responsive */
@media (max-width: 850px) {
    .hero-content h1 { font-size: 32px; }
    .hero-content p { font-size: 1rem; }
    .hero-actions .btn-main, .hero-actions .btn-outline { font-size: 0.99rem; padding: 10px 19px;}
    .hero-section { height: 65vh; min-height: 320px; }
}
@media (max-width: 600px) {
    .navbar { padding: 9px 2vw; }
    .hero-content {
        margin-left: 0; padding: 12vw 5vw 0 5vw; max-width: 100vw;
    }
    .hero-section { padding: 0; min-height: 240px; height: 49vh; }
    .hero-content h1 { font-size: 18px; }
    .hero-content p { font-size: 0.88rem; }
    .hero-actions { flex-direction: column; gap: 13px; }
}
</style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="logo">
        <i class="fas fa-heart"></i> Sehat Guardian
    </div>
    <div class="navlinks">
        <a href="home.php" class="active">Home</a>
        <a href="about.php">About</a>
        <a href="services.php">Services</a>
        <a href="contact.php">Contact</a>
        <a href="login_admin.php">Login</a>
        <a href="register_patient.php" class="btn">Register</a>
    </div>
</div>

<div class="hero-section">
    <div id="slideshow-bg">
        <img src="oldpeo.jpg" class="slide active" alt="Elderly 1" />
        <img src="oldpeo1.jpg" class="slide" alt="Elderly 2" />
        <img src="oldpeo2.jpg" class="slide" alt="Elderly 3" />
    </div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>Smart Health<br>Management for<br>Elderly Care</h1>
        <p>
            Stay healthy, stay safe, stay connected with our comprehensive health monitoring system designed specifically for elderly care and family peace of mind.
        </p>
        <div class="hero-actions">
            <button class="btn-main" onclick="window.location.href='register_patient.php'">Get Started Today</button>
            <button class="btn-outline" onclick="window.location.href='about.php'">Learn More</button>
        </div>
    </div>
</div>

<footer>
    &copy; 2025 Sehat Guardian | All Rights Reserved
</footer>
<script>
let slides = document.querySelectorAll('.slide');
let idx = 0;
setInterval(() => {
    slides[idx].classList.remove('active');
    idx = (idx + 1) % slides.length;
    slides[idx].classList.add('active');
}, 3500);
</script>
</body>
</html>
