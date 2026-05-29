<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Smart Attendance System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Poppins',sans-serif;
    min-height:100vh;
    background:
    linear-gradient(rgba(15,23,42,0.85),rgba(15,23,42,0.85)),
    url('https://images.unsplash.com/photo-1526379095098-d400fd0bf935?q=80&w=1920');
    background-size:cover;
    background-position:center;
    overflow-x:hidden;
    color:white;
}

/* NAVBAR */

.navbar-custom{
    width:100%;
    padding:20px 60px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    backdrop-filter:blur(10px);
    background:rgba(255,255,255,0.05);
    border-bottom:1px solid rgba(255,255,255,0.1);
}

.logo{
    font-size:30px;
    font-weight:700;
}

.project-tag{
    background:#2563eb;
    padding:10px 20px;
    border-radius:30px;
    font-size:14px;
    font-weight:500;
}

/* HERO */

.hero{
    min-height:90vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:40px;
}

.hero-container{
    width:1300px;
    display:grid;
    grid-template-columns:1.2fr 0.8fr;
    gap:40px;
}

/* LEFT SIDE */

.left-section{
    padding-top:40px;
}

.title{
    font-size:65px;
    font-weight:700;
    line-height:1.2;
    margin-bottom:25px;
}

.title span{
    color:#60a5fa;
}

.description{
    font-size:18px;
    color:#cbd5e1;
    line-height:1.9;
    margin-bottom:40px;
    max-width:700px;
}

/* FEATURE CARDS */

.features{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:25px;
}

.feature-card{
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.1);
    backdrop-filter:blur(12px);
    padding:25px;
    border-radius:22px;
    transition:0.3s;
}

.feature-card:hover{
    transform:translateY(-6px);
    background:rgba(255,255,255,0.12);
}

.feature-icon{
    font-size:38px;
    margin-bottom:15px;
}

.feature-title{
    font-size:20px;
    font-weight:600;
    margin-bottom:10px;
}

.feature-text{
    color:#cbd5e1;
    font-size:14px;
    line-height:1.7;
}

/* RIGHT PANEL */

.right-section{
    display:flex;
    align-items:center;
    justify-content:center;
}

.portal-card{
    width:100%;
    max-width:430px;
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.1);
    backdrop-filter:blur(18px);
    border-radius:30px;
    padding:45px;
    box-shadow:0 10px 40px rgba(0,0,0,0.3);
}

.portal-title{
    text-align:center;
    font-size:34px;
    font-weight:700;
    margin-bottom:10px;
}

.portal-sub{
    text-align:center;
    color:#cbd5e1;
    margin-bottom:35px;
}

/* INPUT */

.form-control{
    height:58px;
    border-radius:15px;
    border:none;
    margin-bottom:20px;
    background:rgba(255,255,255,0.12);
    color:white;
    padding-left:18px;
}

.form-control::placeholder{
    color:#dbeafe;
}

.form-control:focus{
    background:rgba(255,255,255,0.15);
    color:white;
    box-shadow:none;
    border:1px solid #60a5fa;
}

/* BUTTONS */

.main-btn{
    width:100%;
    height:58px;
    border:none;
    border-radius:15px;
    font-size:17px;
    font-weight:600;
    transition:0.3s;
    margin-bottom:18px;
}

.admin-btn{
    background:linear-gradient(135deg,#2563eb,#3b82f6);
    color:white;
}

.admin-btn:hover{
    transform:translateY(-2px);
}

.student-btn{
    background:linear-gradient(135deg,#059669,#10b981);
    color:white;
}

.student-btn:hover{
    transform:translateY(-2px);
}

/* STATS */

.stats{
    margin-top:30px;
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:15px;
}

.stat-box{
    background:rgba(255,255,255,0.08);
    padding:18px;
    border-radius:18px;
    text-align:center;
}

.stat-box h3{
    font-size:26px;
    font-weight:700;
}

.stat-box p{
    color:#cbd5e1;
    font-size:13px;
}

/* FOOTER */

.footer{
    text-align:center;
    padding:25px;
    color:#cbd5e1;
    font-size:14px;
}

/* MOBILE */

@media(max-width:1000px){

.hero-container{
    grid-template-columns:1fr;
}

.title{
    font-size:45px;
}

.features{
    grid-template-columns:1fr;
}

.navbar-custom{
    padding:20px;
}

}

</style>

</head>

<body>

<!-- NAVBAR -->

<div class="navbar-custom">

<div class="logo">
📘 Smart Attendance
</div>

<div class="project-tag">
Major Project 2026
</div>

</div>

<!-- HERO -->

<section class="hero">

<div class="hero-container">

<!-- LEFT -->

<div class="left-section">

<h1 class="title">
Smart <span>Attendance</span><br>
Monitoring System
</h1>

<p class="description">

An advanced attendance management system using
Fingerprint Authentication, ESP32 and Web Dashboard
to automate attendance tracking and analytics for institutions.

</p>

<div class="features">

<div class="feature-card">

<div class="feature-icon">📡</div>

<div class="feature-title">
IoT Integration
</div>

<div class="feature-text">
Real-time attendance monitoring using ESP32 hardware integration.
</div>

</div>

<div class="feature-card">

<div class="feature-icon">🔒</div>

<div class="feature-title">
Secure Authentication
</div>

<div class="feature-text">
Biometric verification for secure attendance management.
</div>

</div>

<div class="feature-card">

<div class="feature-icon">📊</div>

<div class="feature-title">
Attendance Analytics
</div>

<div class="feature-text">
Generate attendance reports and detailed student analytics.
</div>

</div>

<div class="feature-card">

<div class="feature-icon">⚡</div>

<div class="feature-title">
Automated System
</div>

<div class="feature-text">
Fully automated attendance process without manual intervention.
</div>

</div>

</div>

</div>

<!-- RIGHT -->

<div class="right-section">

<div class="portal-card">

<h2 class="portal-title">
System Portal
</h2>

<p class="portal-sub">
Access Admin Panel or Student Attendance
</p>

<a href="admin/admin_login.php">

<button class="main-btn admin-btn">
Admin Login
</button>

</a>

<form action="view_attendance.php" method="GET">

<input type="text"
name="student_id"
class="form-control"
placeholder="Enter Student ID"
required>

<button type="submit"
class="main-btn student-btn">

View Attendance

</button>

</form>

<div class="stats">

<div class="stat-box">
<h3>100%</h3>
<p>Accuracy</p>
</div>

<div class="stat-box">
<h3>24/7</h3>
<p>Monitoring</p>
</div>

<div class="stat-box">
<h3>100+</h3>
<p>Students</p>
</div>

</div>

</div>

</div>

</div>

</section>

<!-- FOOTER -->

<div class="footer">

Smart Attendance Monitoring System © 2026 | Final Year Engineering Project

</div>

</body>
</html>