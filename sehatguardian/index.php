<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sehat Guardian - Smart Healthcare for Elderly</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f8f9fa;
    overflow-x: hidden;
    line-height: 1.6;
}

/* Navbar */
.navbar {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    padding: 18px 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 30px rgba(0,0,0,0.1);
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 1000;
    transition: all 0.4s ease;
}

.navbar.scrolled {
    padding: 12px 50px;
    background: rgba(255, 255, 255, 0.95);
}

.logo {
    display: flex;
    align-items: center;
    font-size: 1.8rem;
    font-weight: 800;
    color: #006d77;
    gap: 12px;
}

.logo i {
    color: #e74c3c;
    animation: pulse 2s ease-in-out infinite;
}

.navlinks {
    display: flex;
    align-items: center;
    gap: 10px;
}

.navlinks a {
    color: #2a4253;
    text-decoration: none;
    font-size: 16px;
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.navlinks a:hover, .navlinks a.active {
    background: linear-gradient(135deg, #e6f7fa, #b2ebf2);
    color: #006d77;
    transform: translateY(-2px);
}

.navlinks .btn {
    background: linear-gradient(135deg, #006d77, #00a8b5);
    color: #fff !important;
    padding: 12px 28px;
    margin-left: 15px;
    box-shadow: 0 4px 15px rgba(0,109,119,0.3);
}

.navlinks .btn:hover {
    background: linear-gradient(135deg, #00a8b5, #00c2d1);
    box-shadow: 0 6px 20px rgba(0,109,119,0.4);
}

/* Hero Section */
.hero-section {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    overflow: hidden;
    padding-top: 80px;
}

.slideshow-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
}

.slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transition: opacity 1.5s ease-in-out;
}

.slide.active {
    opacity: 1;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(0,109,119,0.85) 0%, rgba(0,168,181,0.75) 100%);
    z-index: 1;
}

.hero-pattern {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0.1;
    background-image: 
        radial-gradient(circle at 20% 50%, rgba(255,255,255,0.2) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.2) 0%, transparent 50%);
    z-index: 1;
}

.hero-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 60px 40px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 80px;
    align-items: center;
    position: relative;
    z-index: 2;
}

.slideshow-controls {
    position: absolute;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 12px;
    z-index: 3;
}

.slide-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255,255,255,0.5);
    border: 2px solid rgba(255,255,255,0.8);
    cursor: pointer;
    transition: all 0.3s ease;
}

.slide-dot.active {
    background: #ffd93d;
    border-color: #ffd93d;
    transform: scale(1.3);
}

.hero-content {
    color: #fff;
}

.hero-content h1 {
    font-size: 3.5rem;
    font-weight: 900;
    margin-bottom: 25px;
    line-height: 1.2;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}

.hero-content .highlight {
    color: #ffd93d;
}

.hero-content p {
    font-size: 1.3rem;
    margin-bottom: 40px;
    line-height: 1.8;
    opacity: 0.95;
}

.hero-buttons {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.btn-primary, .btn-secondary {
    padding: 16px 40px;
    font-size: 1.1rem;
    font-weight: 700;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #fff;
    color: #006d77;
    box-shadow: 0 6px 25px rgba(255,255,255,0.3);
}

.btn-primary:hover {
    background: #ffd93d;
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(255,217,61,0.4);
}

.btn-secondary {
    background: transparent;
    color: #fff;
    border: 2px solid #fff;
}

.btn-secondary:hover {
    background: rgba(255,255,255,0.15);
    transform: translateY(-3px);
}

/* Features Grid */
.hero-features {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.feature-box {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    padding: 30px;
    border-radius: 20px;
    transition: all 0.4s ease;
    cursor: pointer;
}

.feature-box:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.feature-box i {
    font-size: 2.5rem;
    color: #ffd93d;
    margin-bottom: 15px;
    display: block;
}

.feature-box h3 {
    font-size: 1.3rem;
    margin-bottom: 10px;
    color: #fff;
}

.feature-box p {
    font-size: 0.95rem;
    color: rgba(255,255,255,0.9);
    line-height: 1.6;
}

/* Key Features Section */
.key-features-section {
    padding: 120px 40px;
    background: #fff;
}

.section-title {
    text-align: center;
    margin-bottom: 80px;
}

.section-title h2 {
    font-size: 3rem;
    color: #006d77;
    margin-bottom: 20px;
    font-weight: 800;
}

.section-title p {
    font-size: 1.2rem;
    color: #666;
    max-width: 700px;
    margin: 0 auto;
}

.features-grid {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
}

.feature-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
    padding: 50px 35px;
    border-radius: 25px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    transition: all 0.4s ease;
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
}

.feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg, #006d77, #00a8b5);
    transform: scaleX(0);
    transition: transform 0.4s ease;
}

.feature-card:hover::before {
    transform: scaleX(1);
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 50px rgba(0,109,119,0.15);
    border-color: #00a8b5;
}

.feature-icon {
    width: 90px;
    height: 90px;
    margin: 0 auto 25px;
    background: linear-gradient(135deg, #006d77, #00a8b5);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: #fff;
    box-shadow: 0 8px 25px rgba(0,109,119,0.3);
    transition: all 0.4s ease;
}

.feature-card:hover .feature-icon {
    transform: scale(1.1) rotate(5deg);
}

.feature-card h3 {
    font-size: 1.6rem;
    color: #006d77;
    margin-bottom: 18px;
    font-weight: 700;
}

.feature-card p {
    color: #555;
    line-height: 1.8;
    font-size: 1.05rem;
}

.feature-badge {
    position: absolute;
    top: 20px;
    right: 20px;
    background: #e74c3c;
    color: #fff;
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
}

/* How It Works Section */
.how-it-works {
    padding: 120px 40px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.steps-container {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 50px;
}

.step {
    text-align: center;
    position: relative;
}

.step-number {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #006d77, #00a8b5);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 800;
    margin: 0 auto 25px;
    box-shadow: 0 5px 20px rgba(0,109,119,0.3);
}

.step h3 {
    font-size: 1.5rem;
    color: #006d77;
    margin-bottom: 15px;
}

.step p {
    color: #666;
    line-height: 1.7;
}

/* CTA Section */
.cta-section {
    padding: 100px 40px;
    background: linear-gradient(135deg, #006d77 0%, #00a8b5 100%);
    text-align: center;
    color: #fff;
}

.cta-section h2 {
    font-size: 3rem;
    margin-bottom: 25px;
    font-weight: 800;
}

.cta-section p {
    font-size: 1.3rem;
    margin-bottom: 40px;
    opacity: 0.95;
}

/* Footer */
footer {
    background: #1a1a1a;
    color: #fff;
    padding: 60px 40px 30px;
}

.footer-content {
    max-width: 1400px;
    margin: 0 auto;
}

.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 40px;
    margin-bottom: 40px;
}

.footer-section h3 {
    color: #00a8b5;
    margin-bottom: 20px;
    font-size: 1.3rem;
}

.footer-section p, .footer-section a {
    color: #ccc;
    text-decoration: none;
    line-height: 2;
    display: block;
}

.footer-section a:hover {
    color: #00a8b5;
}

.social-links {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.social-links a {
    width: 45px;
    height: 45px;
    background: #333;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.social-links a:hover {
    background: #00a8b5;
    transform: translateY(-3px);
}

.footer-bottom {
    border-top: 1px solid #333;
    padding-top: 30px;
    text-align: center;
    color: #999;
}

/* Animations */
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.15); }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.feature-card {
    animation: fadeInUp 0.6s ease forwards;
}

.feature-card:nth-child(1) { animation-delay: 0.1s; }
.feature-card:nth-child(2) { animation-delay: 0.2s; }
.feature-card:nth-child(3) { animation-delay: 0.3s; }
.feature-card:nth-child(4) { animation-delay: 0.4s; }

/* Mobile Menu */
.menu-toggle {
    display: none;
    flex-direction: column;
    cursor: pointer;
    gap: 5px;
}

.menu-toggle span {
    width: 25px;
    height: 3px;
    background: #006d77;
    transition: 0.3s;
}

/* Responsive */
@media (max-width: 1024px) {
    .hero-container {
        grid-template-columns: 1fr;
        gap: 50px;
        text-align: center;
    }
    
    .hero-content h1 {
        font-size: 2.8rem;
    }
    
    .hero-buttons {
        justify-content: center;
    }
    
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .navbar {
        padding: 15px 20px;
    }
    
    .navlinks {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: rgba(255,255,255,0.98);
        flex-direction: column;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .navlinks.active {
        display: flex;
    }
    
    .navlinks a {
        margin: 10px 0;
    }
    
    .menu-toggle {
        display: flex;
    }
    
    .hero-content h1 {
        font-size: 2.2rem;
    }
    
    .hero-features {
        grid-template-columns: 1fr;
    }
    
    .section-title h2 {
        font-size: 2.2rem;
    }
    
    .key-features-section,
    .how-it-works,
    .cta-section {
        padding: 80px 20px;
    }
}

@media (max-width: 640px) {
    .features-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar" id="navbar">
    <div class="logo">
        <i class="fas fa-heartbeat"></i> Sehat Guardian
    </div>
    <div class="navlinks" id="navlinks">
        <a href="#home" class="active">Home</a>
        <a href="#features">Features</a>
        <a href="#how-it-works">How It Works</a>
    
        <a href="login_admin.php" style="color: #006d77;">Login</a>
        <a href="register_patient.php" class="btn">Get Started</a>
    </div>
    <div class="menu-toggle" id="menuToggle">
        <span></span>
        <span></span>
        <span></span>
    </div>
</nav>

<!-- Hero Section -->
<section id="home" class="hero-section">
    <!-- Slideshow Background -->
    <div class="slideshow-background">
        <img src="oldpeo.jpg" class="slide active" alt="Elderly Care">
        <img src="oldpeo1.jpg" class="slide" alt="Healthcare Support">
        <img src="oldpeo2.jpg" class="slide" alt="Family Care">
    </div>
    <div class="hero-overlay"></div>
    <div class="hero-pattern"></div>
    
    <div class="hero-container">
        <div class="hero-content">
            <h1>Complete Care at Your <span class="highlight">Fingertips</span></h1>
            <p>Monitor health, manage medications, book appointments, and get emergency help instantly. Everything your loved ones need for safe, independent living.</p>
          <div class="hero-buttons">
    <a href="register_patient.php">
        <button class="btn-primary">Start Now</button>
    </a>
</div>

        </div>
        
        <div class="hero-features">
            <div class="feature-box">
                <i class="fas fa-bell"></i>
                <h3>Smart Medicine Reminders</h3>
                <p>Never miss a dose with intelligent medication tracking and alerts</p>
            </div>
            <div class="feature-box">
                <i class="fas fa-ambulance"></i>
                <h3>Emergency Alert</h3>
                <p>One-tap SOS to instantly notify doctors and family members</p>
            </div>
            <div class="feature-box">
                <i class="fas fa-notes-medical"></i>
                <h3>Health Log Tracking</h3>
                <p>Record vitals, symptoms, and activities in one secure place</p>
            </div>
            <div class="feature-box">
                <i class="fas fa-calendar-check"></i>
                <h3>Easy Appointments</h3>
                <p>Book doctor visits and manage schedules effortlessly</p>
            </div>
        </div>
    </div>
    
    <!-- Slideshow Controls -->
    <div class="slideshow-controls">
        <span class="slide-dot active" onclick="goToSlide(0)"></span>
        <span class="slide-dot" onclick="goToSlide(1)"></span>
        <span class="slide-dot" onclick="goToSlide(2)"></span>
    </div>
</section>

<!-- Key Features Section -->
<section id="features" class="key-features-section">
    <div class="section-title">
        <h2>Powerful Features for Complete Care</h2>
        <p>Everything you need to keep your loved ones healthy, safe, and connected</p>
    </div>
    
    <div class="features-grid">
        <div class="feature-card">
            <span class="feature-badge">Critical</span>
            <div class="feature-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Emergency Doctor Alert</h3>
            <p>Instant emergency notifications sent to registered doctors  with  location sharing. Help them by taking quick actions .</p>
        </div>
        
        <div class="feature-card">
            <span class="feature-badge">Essential</span>
            <div class="feature-icon">
                <i class="fas fa-pills"></i>
            </div>
            <h3>Medicine Reminder System</h3>
            <p>Smart medication tracking with dosage information, and automatic  reminders. Never miss a medication again.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-heartbeat"></i>
            </div>
            <h3>Daily Health Log</h3>
            <p>Track basic daily health reading . Generate comprehensive reports for doctor visits with trend analysis.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <h3>Appointment Booking</h3>
            <p>Schedule doctor visits or book an appointments.</p>
        </div>
        
        <div class="feature-card" style="grid-column: 2 / 3;">
            <div class="feature-icon">
                <i class="fas fa-users"></i>
            </div>
            <h3>Family Connection Hub</h3>
            <p>Keep family members updated with health reports, medication schedules, and appointment details. Share care responsibilities easily.</p>
        </div>
        
        <div class="feature-card" style="grid-column: 3 / 4;">
            <div class="feature-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3>Secure & Private</h3>
            <p>Bank-level encryption protects all health data. HIPAA compliant with complete privacy controls for peace of mind.</p>
        </div>
    </div>
</section>

<!-- How It Works -->
<section id="how-it-works" class="how-it-works">
    <div class="section-title">
        <h2>How It Works</h2>
        <p>Getting started is simple and takes just minutes</p>
    </div>
    
    <div class="steps-container">
        <div class="step">
            <div class="step-number">1</div>
            <h3>Create Account</h3>
            <p>Sign up with basic information  in under 2 minutes</p>
        </div>
        
        <div class="step">
            <div class="step-number">2</div>
            <h3>Add profile Details and Book Appointment </h3>
            <p>Enter personal information,book appointment with your doctors</p>
        </div>
        
        <div class="step">
            <div class="step-number">3</div>
            <h3>Set Reminders and record health log </h3>
            <p>Configure medication schedules and record daily health records</p>
        </div>
        
        <div class="step">
            <div class="step-number">4</div>
            <h3>Stay Protected</h3>
            <p>Monitor health daily and get instant help during emergencies</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <h2>Ready to Transform Elderly Care?</h2>
    <p>Join thousands of families who trust Sehat Guardian for comprehensive health management</p>
      <div class="hero-buttons">
    <a href="register_patient.php">
        <button class="btn-primary">Start Now </button>
    </a>
</div>
</section>

<!-- Footer -->
<footer>
    <div class="footer-content">
        <div class="footer-grid">
            <div class="footer-section">
                <h3>Sehat Guardian</h3>
                <p>Empowering elderly care through smart technology and compassionate support.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>Quick Links</h3>
                <a href="#">Home</a>
                <a href="#">Features</a>
                <a href="#">Pricing</a>
                <a href="#">About Us</a>
            </div>
            
            <div class="footer-section">
                <h3>Support</h3>
                <a href="#">Help Center</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact Us</a>
            </div>
            
            <div class="footer-section">
                <h3>Contact</h3>
                <p>📍 123 Healthcare Avenue<br>Idukki, Kerala 685602</p>
                <p>📞 +91 9876543210</p>
                <p>✉️ info@sehatguardian.com</p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2025 Sehat Guardian. All rights reserved. Made with ❤️ for better elderly care.</p>
        </div>
    </div>
</footer>

<script>
// Slideshow functionality
let currentSlide = 0;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.slide-dot');

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.classList.remove('active');
        dots[i].classList.remove('active');
    });
    
    slides[index].classList.add('active');
    dots[index].classList.add('active');
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
    showSlide(currentSlide);
}

function goToSlide(index) {
    currentSlide = index;
    showSlide(currentSlide);
}

// Auto-advance slideshow every 4 seconds
setInterval(nextSlide, 4000);

// Navbar scroll effect
window.addEventListener('scroll', () => {
    const navbar = document.getElementById('navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// Mobile menu toggle
const menuToggle = document.getElementById('menuToggle');
const navlinks = document.getElementById('navlinks');

menuToggle.addEventListener('click', () => {
    navlinks.classList.toggle('active');
});

// Close mobile menu when clicking on a link
document.querySelectorAll('.navlinks a').forEach(link => {
    link.addEventListener('click', () => {
        navlinks.classList.remove('active');
    });
});

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>
</body>
</html>