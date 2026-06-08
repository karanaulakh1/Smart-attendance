<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us — Smart Attendance</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>

:root{
    --bg:      #060b14;
    --surface: rgba(255,255,255,0.04);
    --surface2:rgba(255,255,255,0.07);
    --border:  rgba(255,255,255,0.08);
    --accent:  #3b6ef8;
    --accent2: #6ee7f7;
    --green:   #22c55e;
    --purple:  #a78bfa;
    --amber:   #f59e0b;
    --text:    #f0f4ff;
    --muted:   #64748b;
}

*,*::before,*::after{ margin:0; padding:0; box-sizing:border-box; }
html{ scroll-behavior:smooth; }

body{
    font-family:'DM Sans',sans-serif;
    background:var(--bg);
    color:var(--text);
    min-height:100vh;
    overflow-x:hidden;
}

/* ── BLOBS ── */
.blob{ position:fixed; border-radius:50%; filter:blur(100px); opacity:.13; pointer-events:none; z-index:0; }
.blob-1{ width:550px;height:550px; background:var(--accent);   top:-180px; left:-180px; }
.blob-2{ width:450px;height:450px; background:var(--accent2);  bottom:-120px; right:-120px; }
.blob-3{ width:350px;height:350px; background:var(--purple);   top:50%; left:55%; transform:translate(-50%,-50%); }

/* ── NAV ── */
nav{
    position:sticky; top:0; z-index:200;
    backdrop-filter:blur(20px);
    background:rgba(6,11,20,.8);
    border-bottom:1px solid var(--border);
    padding:0 48px; height:64px;
    display:flex; align-items:center; justify-content:space-between;
}
.nav-logo{ display:flex; align-items:center; gap:10px; font-size:17px; font-weight:700; text-decoration:none; color:var(--text); }
.nav-logo .dot{ width:9px; height:9px; border-radius:50%; background:linear-gradient(135deg,var(--accent),var(--accent2)); animation:pulse 2s infinite; }
@keyframes pulse{ 0%,100%{ box-shadow:0 0 0 0 rgba(59,110,248,.5); } 50%{ box-shadow:0 0 0 8px rgba(59,110,248,0); } }
.nav-back{ display:inline-flex; align-items:center; gap:7px; padding:8px 18px; border-radius:10px; background:var(--surface); border:1px solid var(--border); color:var(--muted); font-size:13px; font-weight:600; text-decoration:none; transition:.2s; }
.nav-back:hover{ color:var(--text); background:var(--surface2); }

/* ── WRAP ── */
.wrap{ position:relative; z-index:1; max-width:1080px; margin:0 auto; padding:64px 48px; }

/* ── EYEBROW ── */
.eyebrow{ display:inline-flex; align-items:center; gap:8px; background:rgba(59,110,248,.1); border:1px solid rgba(59,110,248,.22); color:#93c5fd; padding:6px 16px; border-radius:50px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; margin-bottom:18px; }

/* ── HERO ── */
.hero-section{ text-align:center; margin-bottom:72px; }
.hero-section h1{ font-size:48px; font-weight:700; letter-spacing:-2px; line-height:1.1; margin-bottom:20px; }
.hero-section h1 .hl{ background:linear-gradient(135deg,var(--accent),var(--accent2)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
.hero-section p{ font-size:17px; color:var(--muted); line-height:1.85; max-width:620px; margin:0 auto 32px; }

/* tech pills */
.tech-row{ display:flex; flex-wrap:wrap; justify-content:center; gap:10px; }
.tech-pill{ background:var(--surface2); border:1px solid var(--border); padding:6px 16px; border-radius:50px; font-size:12px; font-weight:600; color:var(--muted); font-family:'DM Mono',monospace; transition:.2s; }
.tech-pill:hover{ color:var(--text); border-color:rgba(255,255,255,.15); }

/* ── FEATURES ── */
.about-grid{ display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:72px; }
.about-card{ background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:28px; position:relative; overflow:hidden; transition:.2s; }
.about-card:hover{ transform:translateY(-3px); border-color:rgba(255,255,255,.12); }
.about-card::before{ content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.about-card.c1::before{ background:linear-gradient(90deg,var(--accent),var(--accent2)); }
.about-card.c2::before{ background:linear-gradient(90deg,var(--green),#86efac); }
.about-card.c3::before{ background:linear-gradient(90deg,var(--purple),#c4b5fd); }
.about-card.c4::before{ background:linear-gradient(90deg,var(--amber),#fde68a); }
.about-card-icon{ font-size:28px; margin-bottom:14px; }
.about-card h3{ font-size:16px; font-weight:700; margin-bottom:8px; }
.about-card p{ font-size:13px; color:var(--muted); line-height:1.7; }

/* ── SECTION DIVIDER ── */
.section-divider{ display:flex; align-items:center; gap:16px; margin-bottom:32px; }
.section-divider h2{ font-size:22px; font-weight:700; letter-spacing:-.4px; white-space:nowrap; }
.section-divider::after{ content:''; flex:1; height:1px; background:var(--border); }

/* ── TEAM LABEL ── */
.team-label{ font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--muted); margin-bottom:20px; }

/* ── MEMBER CARDS ── */
.members-row{ display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:40px; }

.member-card{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:20px;
    padding:28px 20px;
    text-align:center;
    position:relative; overflow:hidden;
    transition:.2s;
}
.member-card:hover{ transform:translateY(-4px); border-color:rgba(255,255,255,.13); }
.member-card::before{ content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.member-card.m1::before{ background:linear-gradient(90deg,var(--accent),var(--accent2)); }
.member-card.m2::before{ background:linear-gradient(90deg,#7c3aed,var(--purple)); }
.member-card.m3::before{ background:linear-gradient(90deg,var(--green),#86efac); }

/* Photo */
.member-photo-wrap{
    width:88px; height:88px;
    border-radius:50%;
    margin:0 auto 16px;
    position:relative;
}
.member-photo{
    width:88px; height:88px;
    border-radius:50%;
    object-fit:cover;
    border:3px solid var(--border);
    display:block;
}
.member-photo-fallback{
    width:88px; height:88px;
    border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:28px; font-weight:800;
    color:#fff;
    border:3px solid var(--border);
}
.m1 .member-photo-fallback{ background:linear-gradient(135deg,var(--accent),#5b8af9); }
.m2 .member-photo-fallback{ background:linear-gradient(135deg,#7c3aed,var(--purple)); }
.m3 .member-photo-fallback{ background:linear-gradient(135deg,#16a34a,var(--green)); }

.member-num{ position:absolute; top:16px; right:16px; font-family:'DM Mono',monospace; font-size:11px; font-weight:600; color:var(--muted); background:var(--surface2); border:1px solid var(--border); padding:3px 9px; border-radius:50px; }
.member-name{ font-size:17px; font-weight:700; letter-spacing:-.3px; margin-bottom:5px; }
.member-role{ font-size:12px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:.7px; }

/* ── LEAD CARDS ── */
.leads-row{ display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:72px; }

.lead-card{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:20px;
    padding:28px;
    display:flex; align-items:center; gap:20px;
    position:relative; overflow:hidden;
    transition:.2s;
}
.lead-card:hover{ transform:translateY(-3px); border-color:rgba(255,255,255,.13); }
.lead-card::before{ content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.lead-card.l1::before{ background:linear-gradient(90deg,var(--amber),#fde68a); }
.lead-card.l2::before{ background:linear-gradient(90deg,#0ea5e9,var(--accent2)); }

.lead-photo{
    width:72px; height:72px;
    border-radius:16px;
    object-fit:cover;
    border:2px solid var(--border);
    flex-shrink:0;
    display:block;
}
.lead-photo-fallback{
    width:72px; height:72px;
    border-radius:16px;
    display:flex; align-items:center; justify-content:center;
    font-size:22px; font-weight:800;
    color:#fff; flex-shrink:0;
}
.l1 .lead-photo-fallback{ background:linear-gradient(135deg,#d97706,var(--amber)); box-shadow:0 6px 20px rgba(245,158,11,.25); }
.l2 .lead-photo-fallback{ background:linear-gradient(135deg,#0369a1,#0ea5e9); box-shadow:0 6px 20px rgba(14,165,233,.2); }

.lead-info{ flex:1; }
.lead-name{ font-size:18px; font-weight:700; letter-spacing:-.3px; margin-bottom:4px; }
.lead-title{ font-size:12px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:.7px; margin-bottom:8px; }
.lead-badge{ display:inline-block; padding:4px 12px; border-radius:50px; font-size:11px; font-weight:700; letter-spacing:.3px; font-family:'DM Mono',monospace; }
.l1 .lead-badge{ background:rgba(245,158,11,.12); color:#fbbf24; border:1px solid rgba(245,158,11,.2); }
.l2 .lead-badge{ background:rgba(14,165,233,.1);  color:#38bdf8; border:1px solid rgba(14,165,233,.18); }

/* ── FOOTER ── */
.footer-strip{ border-top:1px solid var(--border); padding-top:32px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px; }
.footer-strip p{ font-size:13px; color:var(--muted); }
.footer-strip a{ color:var(--muted); text-decoration:none; transition:.15s; }
.footer-strip a:hover{ color:var(--text); }

/* ── MOBILE ── */
@media(max-width:768px){
    nav{ padding:0 18px; }
    .wrap{ padding:36px 18px; }
    .hero-section h1{ font-size:32px; }
    .about-grid{ grid-template-columns:1fr; }
    .members-row{ grid-template-columns:1fr; }
    .leads-row{ grid-template-columns:1fr; }
    .lead-card{ flex-direction:column; text-align:center; }
}

/* ── FADE ── */
.fade-up{ opacity:0; transform:translateY(24px); animation:fadeUp .5s ease forwards; }
.fade-up:nth-child(1){ animation-delay:.05s; }
.fade-up:nth-child(2){ animation-delay:.12s; }
.fade-up:nth-child(3){ animation-delay:.19s; }
.fade-up:nth-child(4){ animation-delay:.26s; }
@keyframes fadeUp{ to{ opacity:1; transform:translateY(0); } }

</style>
</head>
<body>

<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>

<!-- NAV -->
<nav>
    <a href="index.php" class="nav-logo">
        <div class="dot"></div>
        Smart Attendance
    </a>
    <a href="index.php" class="nav-back">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Back to Home
    </a>
</nav>

<div class="wrap">

    <!-- HERO -->
    <div class="hero-section">
        <div class="eyebrow">Biometric Attendance Platform</div>
        <h1>Built for <span class="hl">Institutions</span></h1>
        <p>
            Smart Attendance automates attendance tracking using fingerprint biometrics
            and ESP32 hardware — giving institutions a reliable, tamper-proof system
            with real-time dashboards, analytics, and zero manual effort.
        </p>
        <div class="tech-row">
            <span class="tech-pill">ESP32</span>
            <span class="tech-pill">Fingerprint Sensor</span>
            <span class="tech-pill">PHP</span>
            <span class="tech-pill">MySQL / TiDB</span>
            <span class="tech-pill">Render</span>
            <span class="tech-pill">IoT</span>
        </div>
    </div>

    <!-- WHAT WE BUILT -->
    <div class="section-divider"><h2>What We Built</h2></div>
    <div class="about-grid" style="margin-bottom:72px;">
        <div class="about-card c1 fade-up">
            <div class="about-card-icon">📡</div>
            <h3>IoT Hardware Integration</h3>
            <p>An ESP32 microcontroller paired with a fingerprint sensor captures biometric data in real time and sends it to the web server over Wi-Fi — no manual input required.</p>
        </div>
        <div class="about-card c2 fade-up">
            <div class="about-card-icon">🔒</div>
            <h3>Biometric Authentication</h3>
            <p>Each member is enrolled with a unique fingerprint. The system verifies identity in under a second, ensuring tamper-proof and accurate attendance records.</p>
        </div>
        <div class="about-card c3 fade-up">
            <div class="about-card-icon">📊</div>
            <h3>Real-Time Dashboard</h3>
            <p>Admins get a live dashboard with attendance analytics, weekly charts, member management across multiple groups, and Excel exports.</p>
        </div>
        <div class="about-card c4 fade-up">
            <div class="about-card-icon">⏱️</div>
            <h3>IN / OUT Time Tracking</h3>
            <p>First scan marks IN time, second scan marks OUT time. Working hours are calculated automatically — giving a complete daily attendance record for every member.</p>
        </div>
    </div>

    <!-- TEAM -->
    <div class="section-divider"><h2>Our Team</h2></div>
    <div class="team-label">Developers</div>

    <div class="members-row">

        <!-- Member 1: Karan Aulakh -->
        <div class="member-card m1 fade-up">
            <div class="member-num">01</div>
            <div class="member-photo-wrap">
                <!-- Replace karan.jpg with your actual image filename -->
                <img class="member-photo" src="assets/team/karan.jpg"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                     alt="Karan Aulakh">
                <div class="member-photo-fallback" style="display:none;">KA</div>
            </div>
            <div class="member-name">Karan Aulakh</div>
            <div class="member-role">Developer</div>
        </div>

        <!-- Member 2: Sahijpal Sharma -->
        <div class="member-card m2 fade-up">
            <div class="member-num">02</div>
            <div class="member-photo-wrap">
                <img class="member-photo" src="assets/team/sahijpal.jpg"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                     alt="Sahijpal Sharma">
                <div class="member-photo-fallback" style="display:none;">SS</div>
            </div>
            <div class="member-name">Sahijpal Sharma</div>
            <div class="member-role">Developer</div>
        </div>

        <!-- Member 3: Abhinav Kumar -->
        <div class="member-card m3 fade-up">
            <div class="member-num">03</div>
            <div class="member-photo-wrap">
                <img class="member-photo" src="assets/team/abhinav.jpg"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                     alt="Abhinav Kumar">
                <div class="member-photo-fallback" style="display:none;">AK</div>
            </div>
            <div class="member-name">Abhinav Kumar</div>
            <div class="member-role">Developer</div>
        </div>

    </div>

    <!-- GUIDE & HOD -->
    <div class="team-label">Mentor &amp; Head of Department</div>

    <div class="leads-row">

        <!-- Project Guide: Rohan Dhaload -->
        <div class="lead-card l1 fade-up">
            <img class="lead-photo" src="assets/team/rohan.jpg"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                 alt="Rohan Dhaload">
            <div class="lead-photo-fallback" style="display:none;">RD</div>
            <div class="lead-info">
                <div class="lead-name">Rohan Dhaload</div>
                <div class="lead-title">Project Mentor</div>
                <span class="lead-badge">Mentor</span>
            </div>
        </div>

        <!-- HOD: Balwinder Singh -->
        <div class="lead-card l2 fade-up">
            <img class="lead-photo" src="assets/team/balwinder.jpg"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                 alt="Balwinder Singh">
            <div class="lead-photo-fallback" style="display:none;">BS</div>
            <div class="lead-info">
                <div class="lead-name">Balwinder Singh</div>
                <div class="lead-title">Head of Department</div>
                <span class="lead-badge">HOD</span>
            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <div class="footer-strip">
        <p>Smart Attendance &copy; <?php echo date('Y'); ?> &mdash; Biometric Attendance Platform</p>
        <div style="display:flex;gap:20px;">
            <a href="index.php">Home</a>
            <a href="admin/admin_login.php">Admin Login</a>
        </div>
    </div>

</div>

</body>
</html>