<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Attendance System</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>

:root{
    --bg:       #060b14;
    --surface:  rgba(255,255,255,0.05);
    --surface2: rgba(255,255,255,0.08);
    --border:   rgba(255,255,255,0.08);
    --accent:   #3b6ef8;
    --accent2:  #6ee7f7;
    --green:    #22c55e;
    --text:     #f0f4ff;
    --muted:    #94a3b8;
}

*, *::before, *::after{ margin:0; padding:0; box-sizing:border-box; }

html{ scroll-behavior:smooth; }

body{
    font-family:'DM Sans',sans-serif;
    min-height:100vh;
    background: var(--bg);
    color:var(--text);
    overflow-x:hidden;
}

/* ─── BG BLOBS ─── */
.blob{
    position:fixed;
    border-radius:50%;
    filter:blur(90px);
    opacity:.18;
    pointer-events:none;
    z-index:0;
}
.blob-1{
    width:600px; height:600px;
    background:#3b6ef8;
    top:-150px; left:-150px;
}
.blob-2{
    width:500px; height:500px;
    background:#6ee7f7;
    bottom:-100px; right:-100px;
}
.blob-3{
    width:350px; height:350px;
    background:#22c55e;
    top:50%; left:50%;
    transform:translate(-50%,-50%);
}

/* ─── NAVBAR ─── */
nav{
    position:sticky; top:0; z-index:200;
    width:100%;
    padding:0 48px;
    height:68px;
    display:flex; align-items:center; justify-content:space-between;
    backdrop-filter:blur(20px);
    background:rgba(6,11,20,.75);
    border-bottom:1px solid var(--border);
}

.nav-logo{
    display:flex; align-items:center; gap:10px;
    font-size:18px; font-weight:700;
    letter-spacing:-.3px;
    text-decoration:none; color:var(--text);
}
.nav-logo .dot{
    width:10px; height:10px;
    border-radius:50%;
    background:linear-gradient(135deg,var(--accent),var(--accent2));
    animation:pulse 2s infinite;
}
@keyframes pulse{
    0%,100%{ box-shadow:0 0 0 0 rgba(59,110,248,.5); }
    50%{ box-shadow:0 0 0 8px rgba(59,110,248,0); }
}

.nav-actions{ display:flex; align-items:center; gap:10px; }

.nav-tag{
    background:rgba(59,110,248,.15);
    border:1px solid rgba(59,110,248,.3);
    color:#93c5fd;
    padding:6px 14px; border-radius:50px;
    font-size:12px; font-weight:600;
    letter-spacing:.3px;
    display:none;
}

.nav-btn{
    padding:9px 20px; border-radius:10px;
    font-family:'DM Sans',sans-serif;
    font-size:13px; font-weight:600;
    cursor:pointer; transition:.2s;
    text-decoration:none;
    border:none;
}
.nav-btn-ghost{
    background:transparent;
    border:1px solid var(--border);
    color:var(--muted);
}
.nav-btn-ghost:hover{ background:var(--surface); color:var(--text); }

.nav-btn-solid{
    background:linear-gradient(135deg,var(--accent),#5b8af9);
    color:#fff;
    box-shadow:0 4px 16px rgba(59,110,248,.3);
}
.nav-btn-solid:hover{ transform:translateY(-1px); box-shadow:0 6px 20px rgba(59,110,248,.4); }

/* ─── HERO ─── */
.hero{
    position:relative; z-index:1;
    min-height:calc(100vh - 68px);
    display:flex; align-items:center;
    padding:60px 48px;
    max-width:1280px;
    margin:0 auto;
    gap:60px;
}

/* LEFT */
.hero-left{ flex:1.2; }

.eyebrow{
    display:inline-flex; align-items:center; gap:8px;
    background:rgba(34,197,94,.1);
    border:1px solid rgba(34,197,94,.2);
    color:#4ade80;
    padding:7px 16px; border-radius:50px;
    font-size:12px; font-weight:700;
    letter-spacing:.6px; text-transform:uppercase;
    margin-bottom:28px;
}
.eyebrow .live-dot{
    width:7px; height:7px; border-radius:50%;
    background:#4ade80;
    animation:livePulse 1.5s infinite;
}
@keyframes livePulse{
    0%,100%{ opacity:1; }
    50%{ opacity:.3; }
}

h1{
    font-size:62px;
    font-weight:700;
    line-height:1.12;
    letter-spacing:-2px;
    margin-bottom:24px;
}
h1 .hl{
    background:linear-gradient(135deg,var(--accent),var(--accent2));
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
}

.hero-desc{
    font-size:17px;
    color:var(--muted);
    line-height:1.85;
    max-width:560px;
    margin-bottom:44px;
}

/* FEATURE GRID */
.features{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
    max-width:580px;
}
.feat{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:16px;
    padding:20px;
    backdrop-filter:blur(12px);
    transition:.2s;
}
.feat:hover{
    transform:translateY(-3px);
    background:var(--surface2);
    border-color:rgba(255,255,255,.12);
}
.feat-icon{
    font-size:26px; margin-bottom:10px;
}
.feat-title{
    font-size:14px; font-weight:700;
    margin-bottom:5px; letter-spacing:-.1px;
}
.feat-text{
    font-size:12px; color:var(--muted); line-height:1.6;
}

/* RIGHT */
.hero-right{
    flex:0 0 400px;
    display:flex; align-items:center; justify-content:center;
}

.portal{
    width:100%;
    background:rgba(255,255,255,.04);
    border:1px solid var(--border);
    backdrop-filter:blur(24px);
    border-radius:24px;
    padding:36px;
    box-shadow:0 24px 60px rgba(0,0,0,.4);
    position:relative;
    overflow:hidden;
}
.portal::before{
    content:'';
    position:absolute; top:0; left:0; right:0; height:3px;
    background:linear-gradient(90deg,var(--accent),var(--accent2),var(--green));
}

.portal-head{
    text-align:center;
    margin-bottom:28px;
}
.portal-head h2{
    font-size:22px; font-weight:700;
    letter-spacing:-.4px;
    margin-bottom:6px;
}
.portal-head p{
    font-size:13px; color:var(--muted);
}

/* divider */
.or-divider{
    display:flex; align-items:center; gap:12px;
    margin:20px 0;
    font-size:12px; color:var(--muted); font-weight:600;
}
.or-divider::before,
.or-divider::after{
    content:''; flex:1;
    height:1px; background:var(--border);
}

/* portal buttons */
.portal-btn{
    width:100%; height:52px;
    border:none; border-radius:13px;
    font-family:'DM Sans',sans-serif;
    font-size:15px; font-weight:700;
    cursor:pointer; transition:.2s;
    text-decoration:none;
    display:flex; align-items:center; justify-content:center;
    gap:10px;
}
.portal-btn:hover{ transform:translateY(-2px); }

.btn-admin{
    background:linear-gradient(135deg,var(--accent),#5b8af9);
    color:#fff;
    box-shadow:0 6px 20px rgba(59,110,248,.3);
    margin-bottom:0;
}
.btn-admin:hover{ box-shadow:0 8px 24px rgba(59,110,248,.45); }

/* student form */
.student-section{ margin-top:0; }
.s-input{
    width:100%;
    height:48px;
    padding:0 16px;
    background:rgba(255,255,255,.07);
    border:1px solid var(--border);
    border-radius:13px;
    color:var(--text);
    font-family:'DM Sans',sans-serif;
    font-size:14px;
    outline:none;
    transition:.15s;
    margin-bottom:10px;
}
.s-input::placeholder{ color:var(--muted); }
.s-input:focus{ border-color:var(--accent); background:rgba(59,110,248,.06); }

.btn-student{
    background:linear-gradient(135deg,#16a34a,var(--green));
    color:#fff;
    box-shadow:0 6px 20px rgba(34,197,94,.25);
}
.btn-student:hover{ box-shadow:0 8px 24px rgba(34,197,94,.4); }

/* stats strip */
.portal-stats{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:10px;
    margin-top:22px;
    padding-top:22px;
    border-top:1px solid var(--border);
}
.pstat{
    text-align:center;
}
.pstat-val{
    font-size:22px; font-weight:700;
    letter-spacing:-1px;
    background:linear-gradient(135deg,var(--accent),var(--accent2));
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
}
.pstat-label{
    font-size:10px; font-weight:600;
    color:var(--muted); text-transform:uppercase;
    letter-spacing:.6px;
}

/* ─── FOOTER ─── */
footer{
    position:relative; z-index:1;
    border-top:1px solid var(--border);
    padding:24px 48px;
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:12px;
    font-size:13px; color:var(--muted);
}
footer a{ color:var(--muted); text-decoration:none; transition:.15s; }
footer a:hover{ color:var(--text); }

/* ─── MOBILE ─── */
@media(max-width:1024px){
    .hero{
        flex-direction:column;
        padding:40px 24px;
        gap:40px;
        min-height:unset;
    }
    .hero-right{ flex:none; width:100%; max-width:480px; margin:0 auto; }
    h1{ font-size:44px; }
}

@media(max-width:640px){
    nav{ padding:0 20px; }
    .nav-tag{ display:none !important; }

    h1{ font-size:34px; letter-spacing:-1px; }
    .hero-desc{ font-size:15px; }
    .eyebrow{ font-size:11px; }

    .features{ grid-template-columns:1fr 1fr; gap:10px; }
    .feat{ padding:16px; }
    .feat-icon{ font-size:22px; margin-bottom:8px; }
    .feat-title{ font-size:13px; }

    .portal{ padding:24px 20px; }

    footer{ flex-direction:column; align-items:center; text-align:center; padding:20px; }

    .blob-1{ width:300px; height:300px; }
    .blob-2{ width:250px; height:250px; }
}

@media(max-width:380px){
    h1{ font-size:28px; }
    .features{ grid-template-columns:1fr; }
}
</style>
</head>
<body>

<!-- BG BLOBS -->
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>

<!-- NAVBAR -->
<nav>
    <a href="index.php" class="nav-logo">
        <div class="dot"></div>
        Smart Attendance
    </a>
    <div class="nav-actions">
        <span class="nav-tag">Major Project 2026</span>
        <a href="aboutus.php" class="nav-btn nav-btn-ghost">About Us</a>
        <a href="admin/admin_login.php" class="nav-btn nav-btn-solid">Admin Login</a>
    </div>
</nav>

<!-- HERO -->
<section class="hero">

    <!-- LEFT -->
    <div class="hero-left">

        <div class="eyebrow">
            <span class="live-dot"></span>
            IoT-Powered Attendance
        </div>

        <h1>Smart <span class="hl">Attendance</span><br>Monitoring<br>System</h1>

        <p class="hero-desc">
            An advanced attendance management platform using Fingerprint Authentication,
            ESP32 hardware, and a real-time web dashboard to automate tracking
            and analytics for educational institutions.
        </p>

        <div class="features">
            <div class="feat">
                <div class="feat-icon">📡</div>
                <div class="feat-title">IoT Integration</div>
                <div class="feat-text">Real-time monitoring via ESP32 hardware integration.</div>
            </div>
            <div class="feat">
                <div class="feat-icon">🔒</div>
                <div class="feat-title">Biometric Auth</div>
                <div class="feat-text">Fingerprint verification for secure, tamper-proof attendance.</div>
            </div>
            <div class="feat">
                <div class="feat-icon">📊</div>
                <div class="feat-title">Analytics</div>
                <div class="feat-text">Detailed reports and student attendance analytics.</div>
            </div>
            <div class="feat">
                <div class="feat-icon">⚡</div>
                <div class="feat-title">Automated</div>
                <div class="feat-text">Fully automated process with zero manual intervention.</div>
            </div>
        </div>

    </div>

    <!-- RIGHT PORTAL -->
    <div class="hero-right">
        <div class="portal">

            <div class="portal-head">
                <h2>System Portal</h2>
                <p>Admin panel or student attendance view</p>
            </div>

            <!-- ADMIN -->
            <a href="admin/admin_login.php" class="portal-btn btn-admin">
                Admin Login
            </a>

            <div class="or-divider">or</div>

            <!-- STUDENT -->
            <div class="student-section">
                <form action="view_attendance.php" method="GET">
                    <input class="s-input" type="text" name="student_id"
                           placeholder="Enter Student ID" required>
                    <button type="submit" class="portal-btn btn-student">
                        View My Attendance
                    </button>
                </form>
            </div>

            <!-- STATS -->
            <div class="portal-stats">
                <div class="pstat">
                    <div class="pstat-val">100%</div>
                    <div class="pstat-label">Accuracy</div>
                </div>
                <div class="pstat">
                    <div class="pstat-val">24/7</div>
                    <div class="pstat-label">Monitoring</div>
                </div>
                <div class="pstat">
                    <div class="pstat-val">100+</div>
                    <div class="pstat-label">Students</div>
                </div>
            </div>

        </div>
    </div>

</section>

<!-- FOOTER -->
<footer>
    <span>Smart Attendance Monitoring System &copy; 2026 &mdash; Final Year Engineering Project</span>
    <div style="display:flex;gap:20px;">
        <a href="admin/admin_login.php">Admin</a>
        <a href="aboutus.php">About Us</a>
    </div>
</footer>

</body>
</html>