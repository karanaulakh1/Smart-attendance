<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Attendance System</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>

:root{
    --bg:     #04080f;
    --text:   #eef2ff;
    --muted:  #5a6a85;
    --border: rgba(255,255,255,0.07);
    --accent: #3b6ef8;
    --cyan:   #22d3ee;
    --green:  #22c55e;
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

/* ── GRID BACKGROUND ── */
body::before{
    content:'';
    position:fixed; inset:0; z-index:0;
    background-image:
        linear-gradient(rgba(59,110,248,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(59,110,248,.06) 1px, transparent 1px);
    background-size:60px 60px;
    pointer-events:none;
}

/* gradient fade over grid */
body::after{
    content:'';
    position:fixed; inset:0; z-index:0;
    background:radial-gradient(ellipse 80% 60% at 50% -10%, rgba(59,110,248,.18) 0%, transparent 70%);
    pointer-events:none;
}

/* ── NAV ── */
nav{
    position:sticky; top:0; z-index:300;
    backdrop-filter:blur(20px);
    background:rgba(4,8,15,.85);
    border-bottom:1px solid var(--border);
    padding:0 48px; height:62px;
    display:flex; align-items:center; justify-content:space-between;
}
.nav-logo{
    display:flex; align-items:center; gap:12px;
    font-family:'Syne',sans-serif;
    font-size:17px; font-weight:700;
    letter-spacing:-.3px;
    text-decoration:none; color:var(--text);
}
.logo-icon{
    width:30px; height:30px;
    background:linear-gradient(135deg,var(--accent),var(--cyan));
    border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    font-size:15px;
}
.nav-links{ display:flex; align-items:center; gap:8px; }

.nav-pill{
    padding:7px 16px; border-radius:8px;
    font-size:13px; font-weight:600;
    text-decoration:none; transition:.2s;
    border:1px solid transparent;
    color:var(--muted);
}
.nav-pill:hover{ color:var(--text); background:rgba(255,255,255,.05); border-color:var(--border); }
.nav-pill.solid{
    background:var(--accent);
    color:#fff; border-color:var(--accent);
    box-shadow:0 0 20px rgba(59,110,248,.35);
}
.nav-pill.solid:hover{ background:#4f83ff; box-shadow:0 0 28px rgba(59,110,248,.5); }

/* ── HERO ── */
.hero{
    position:relative; z-index:1;
    display:grid;
    grid-template-columns:1fr 380px;
    gap:60px;
    align-items:center;
    max-width:1160px;
    margin:0 auto;
    padding:80px 48px 60px;
    min-height:calc(100vh - 62px);
}

/* LEFT */
.hero-left{ max-width:600px; }

.badge{
    display:inline-flex; align-items:center; gap:8px;
    border:1px solid rgba(59,110,248,.3);
    background:rgba(59,110,248,.08);
    padding:6px 14px; border-radius:6px;
    font-family:'DM Mono',monospace;
    font-size:11px; font-weight:500;
    color:#93c5fd; letter-spacing:.5px;
    margin-bottom:30px;
}
.badge .blink{
    width:6px; height:6px; border-radius:50%;
    background:#4ade80;
    animation:blink 1.4s infinite;
}
@keyframes blink{ 0%,100%{opacity:1} 50%{opacity:.2} }

h1{
    font-family:'Syne',sans-serif;
    font-size:66px; font-weight:800;
    line-height:1.0; letter-spacing:-3px;
    margin-bottom:26px;
}
.line-1{ display:block; color:var(--text); }
.line-2{
    display:block;
    background:linear-gradient(90deg,var(--accent),var(--cyan));
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
}
.line-3{ display:block; color:rgba(255,255,255,.25); }

.hero-desc{
    font-size:16px; color:var(--muted);
    line-height:1.85; max-width:480px;
    margin-bottom:40px;
}

/* stat chips */
.stat-chips{
    display:flex; gap:12px; flex-wrap:wrap;
    margin-bottom:36px;
}
.chip{
    display:flex; align-items:center; gap:8px;
    background:rgba(255,255,255,.04);
    border:1px solid var(--border);
    padding:8px 16px; border-radius:8px;
    font-size:13px;
}
.chip-val{ font-family:'Syne',sans-serif; font-weight:700; font-size:16px; }
.chip-val.c-blue{ color:var(--accent); }
.chip-val.c-green{ color:var(--green); }
.chip-val.c-cyan{ color:var(--cyan); }
.chip-label{ color:var(--muted); font-size:12px; }

/* cta row */
.cta-row{ display:flex; gap:12px; flex-wrap:wrap; }
.cta-btn{
    padding:13px 28px; border-radius:10px;
    font-family:'DM Sans',sans-serif;
    font-size:14px; font-weight:700;
    cursor:pointer; transition:.2s;
    text-decoration:none; border:none;
    display:inline-flex; align-items:center; gap:8px;
}
.cta-primary{
    background:var(--accent);
    color:#fff;
    box-shadow:0 6px 24px rgba(59,110,248,.35);
}
.cta-primary:hover{ background:#4f83ff; transform:translateY(-2px); box-shadow:0 10px 30px rgba(59,110,248,.5); }
.cta-ghost{
    background:transparent;
    color:var(--muted);
    border:1px solid var(--border);
}
.cta-ghost:hover{ color:var(--text); background:rgba(255,255,255,.04); border-color:rgba(255,255,255,.15); }

/* ── RIGHT — PORTAL ── */
.portal{
    background:rgba(255,255,255,.03);
    border:1px solid var(--border);
    border-radius:20px;
    padding:32px;
    backdrop-filter:blur(20px);
    position:relative;
}
/* corner accent */
.portal::before{
    content:'';
    position:absolute; top:0; left:0; right:0; height:1px;
    background:linear-gradient(90deg,transparent,var(--accent),var(--cyan),transparent);
}

.portal-label{
    font-family:'DM Mono',monospace;
    font-size:10px; font-weight:500;
    color:var(--muted); letter-spacing:1.2px;
    text-transform:uppercase;
    margin-bottom:20px;
    display:flex; align-items:center; gap:8px;
}
.portal-label::after{
    content:''; flex:1;
    height:1px; background:var(--border);
}

/* admin btn */
.admin-portal-btn{
    display:block; width:100%;
    padding:14px;
    background:linear-gradient(135deg,var(--accent),#5b8af9);
    border:none; border-radius:12px;
    color:#fff;
    font-family:'DM Sans',sans-serif;
    font-size:14px; font-weight:700;
    text-decoration:none; text-align:center;
    cursor:pointer; transition:.2s;
    box-shadow:0 6px 20px rgba(59,110,248,.3);
    margin-bottom:16px;
}
.admin-portal-btn:hover{ transform:translateY(-2px); box-shadow:0 10px 28px rgba(59,110,248,.45); }

.portal-sep{
    display:flex; align-items:center; gap:10px;
    font-size:11px; color:var(--muted); font-weight:600;
    margin-bottom:16px;
    font-family:'DM Mono',monospace;
}
.portal-sep::before,.portal-sep::after{
    content:''; flex:1; height:1px; background:var(--border);
}

/* student form */
.s-input{
    width:100%; height:46px;
    padding:0 14px;
    background:rgba(255,255,255,.05);
    border:1px solid var(--border);
    border-radius:10px;
    color:var(--text);
    font-family:'DM Sans',sans-serif;
    font-size:14px; outline:none;
    transition:.15s; margin-bottom:10px;
}
.s-input::placeholder{ color:var(--muted); }
.s-input:focus{ border-color:var(--accent); background:rgba(59,110,248,.07); }

.student-submit{
    width:100%; height:46px;
    border:none; border-radius:10px;
    background:rgba(34,197,94,.15);
    border:1px solid rgba(34,197,94,.25);
    color:#4ade80;
    font-family:'DM Sans',sans-serif;
    font-size:14px; font-weight:700;
    cursor:pointer; transition:.2s;
}
.student-submit:hover{ background:rgba(34,197,94,.25); transform:translateY(-1px); }

/* portal stats */
.portal-stats{
    display:grid; grid-template-columns:repeat(3,1fr);
    gap:8px; margin-top:20px;
    padding-top:20px;
    border-top:1px solid var(--border);
}
.pstat{ text-align:center; }
.pstat-n{
    font-family:'Syne',sans-serif;
    font-size:20px; font-weight:800;
    background:linear-gradient(135deg,var(--accent),var(--cyan));
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
}
.pstat-t{ font-size:10px; color:var(--muted); font-weight:600; text-transform:uppercase; letter-spacing:.6px; }

/* ── MARQUEE STRIP ── */
.strip{
    position:relative; z-index:1;
    border-top:1px solid var(--border);
    border-bottom:1px solid var(--border);
    background:rgba(255,255,255,.02);
    overflow:hidden; padding:14px 0;
    margin-bottom:0;
}
.marquee-inner{
    display:flex; gap:48px;
    animation:scroll 20s linear infinite;
    width:max-content;
}
.marquee-inner:hover{ animation-play-state:paused; }
@keyframes scroll{ to{ transform:translateX(-50%); } }
.m-item{
    display:flex; align-items:center; gap:10px;
    font-family:'DM Mono',monospace;
    font-size:12px; font-weight:500;
    color:var(--muted); white-space:nowrap;
}
.m-dot{
    width:5px; height:5px; border-radius:50%;
    background:var(--accent); opacity:.5;
}

/* ── FOOTER ── */
footer{
    position:relative; z-index:1;
    border-top:1px solid var(--border);
    padding:22px 48px;
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:12px;
    font-size:12px; color:var(--muted);
}
footer a{ color:var(--muted); text-decoration:none; transition:.15s; }
footer a:hover{ color:var(--text); }

/* ── MOBILE ── */
@media(max-width:960px){
    .hero{
        grid-template-columns:1fr;
        padding:48px 24px 40px;
        min-height:unset; gap:40px;
    }
    .hero-left{ max-width:100%; }
    h1{ font-size:48px; letter-spacing:-2px; }
    .portal{ max-width:480px; margin:0 auto; width:100%; }
}

@media(max-width:600px){
    nav{ padding:0 18px; }
    h1{ font-size:38px; letter-spacing:-1.5px; }
    .stat-chips{ gap:8px; }
    .chip{ padding:7px 12px; }
    footer{ padding:18px; flex-direction:column; align-items:center; text-align:center; }
}

@media(max-width:400px){
    h1{ font-size:30px; letter-spacing:-1px; }
    .line-3{ display:none; }
}

/* ── FADE IN ── */
.hero-left > *{
    opacity:0; animation:up .5s ease forwards;
}
.hero-left > *:nth-child(1){ animation-delay:.05s; }
.hero-left > *:nth-child(2){ animation-delay:.12s; }
.hero-left > *:nth-child(3){ animation-delay:.19s; }
.hero-left > *:nth-child(4){ animation-delay:.26s; }
.hero-left > *:nth-child(5){ animation-delay:.33s; }
.portal{ opacity:0; animation:up .5s ease .2s forwards; }
@keyframes up{
    from{ opacity:0; transform:translateY(18px); }
    to{   opacity:1; transform:translateY(0); }
}
</style>
</head>
<body>

<!-- NAV -->
<nav>
    <a href="index.php" class="nav-logo">
        <div class="logo-icon">📘</div>
        Smart Attendance
    </a>
    <div class="nav-links">
        <a href="aboutus.php" class="nav-pill">About Us</a>
        <a href="admin/admin_login.php" class="nav-pill solid">Admin Login</a>
    </div>
</nav>

<!-- HERO -->
<section class="hero">

    <!-- LEFT -->
    <div class="hero-left">

        <div class="badge">
            <span class="blink"></span>
            MAJOR PROJECT · 2026
        </div>

        <h1>
            <span class="line-1">Smart</span>
            <span class="line-2">Attendance</span>
            <span class="line-3">System</span>
        </h1>

        <p class="hero-desc">
            Biometric attendance automation using ESP32, fingerprint sensors,
            and a real-time web dashboard. Built for institutions that need
            accuracy without manual effort.
        </p>

        <div class="stat-chips">
            <div class="chip">
                <span class="chip-val c-blue">100%</span>
                <span class="chip-label">Accuracy</span>
            </div>
            <div class="chip">
                <span class="chip-val c-green">24/7</span>
                <span class="chip-label">Monitoring</span>
            </div>
            <div class="chip">
                <span class="chip-val c-cyan">100+</span>
                <span class="chip-label">Students</span>
            </div>
        </div>

        <div class="cta-row">
            <a href="admin/admin_login.php" class="cta-btn cta-primary">
                Admin Portal
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
            <a href="aboutus.php" class="cta-btn cta-ghost">Learn More</a>
        </div>

    </div>

    <!-- PORTAL CARD -->
    <div class="portal">

        <div class="portal-label">Quick Access</div>

        <a href="admin/admin_login.php" class="admin-portal-btn">
            Admin Login
        </a>

        <div class="portal-sep">or check attendance</div>

        <form action="view_attendance.php" method="GET">
            <input class="s-input" type="text" name="student_id"
                   placeholder="Enter your Student ID" required>
            <button type="submit" class="student-submit">
                View My Attendance
            </button>
        </form>

        <div class="portal-stats">
            <div class="pstat">
                <div class="pstat-n">ESP32</div>
                <div class="pstat-t">Hardware</div>
            </div>
            <div class="pstat">
                <div class="pstat-n">PHP</div>
                <div class="pstat-t">Backend</div>
            </div>
            <div class="pstat">
                <div class="pstat-n">TiDB</div>
                <div class="pstat-t">Database</div>
            </div>
        </div>

    </div>

</section>

<!-- MARQUEE STRIP -->
<div class="strip">
    <div class="marquee-inner">
        <?php
        $items = ["IoT Integration","Fingerprint Auth","Real-Time Dashboard","Auto Absent Marking","ESP32 Hardware","Biometric Verification","Attendance Analytics","Excel Export","Role-Based Access","Mobile Responsive","IoT Integration","Fingerprint Auth","Real-Time Dashboard","Auto Absent Marking","ESP32 Hardware","Biometric Verification","Attendance Analytics","Excel Export","Role-Based Access","Mobile Responsive"];
        foreach($items as $item){
            echo '<div class="m-item"><span class="m-dot"></span>'.$item.'</div>';
        }
        ?>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <span>Smart Attendance Monitoring System &copy; 2026 &mdash; Final Year Engineering Project</span>
    <div style="display:flex;gap:20px;">
        <a href="aboutus.php">About Us</a>
        <a href="admin/admin_login.php">Admin Login</a>
    </div>
</footer>

</body>
</html>