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

*,*::before,*::after{ margin:0; padding:0; box-sizing:border-box; }
html{ scroll-behavior:smooth; }

body{
    font-family:'DM Sans',sans-serif;
    min-height:100vh;
    background:var(--bg);
    color:var(--text);
    overflow-x:hidden;
}

/* ── BLOBS ── */
.blob{
    position:fixed; border-radius:50%;
    filter:blur(90px); opacity:.18;
    pointer-events:none; z-index:0;
}
.blob-1{ width:600px;height:600px; background:#3b6ef8; top:-150px;left:-150px; }
.blob-2{ width:500px;height:500px; background:#6ee7f7; bottom:-100px;right:-100px; }
.blob-3{ width:350px;height:350px; background:#22c55e; top:50%;left:50%; transform:translate(-50%,-50%); }

/* ── NAV ── */
nav{
    position:sticky; top:0; z-index:200;
    width:100%; padding:0 48px; height:68px;
    display:flex; align-items:center; justify-content:space-between;
    backdrop-filter:blur(20px);
    background:rgba(6,11,20,.75);
    border-bottom:1px solid var(--border);
}
.nav-logo{
    display:flex; align-items:center; gap:10px;
    font-size:18px; font-weight:700; letter-spacing:-.3px;
    text-decoration:none; color:var(--text);
}
.nav-logo .dot{
    width:10px; height:10px; border-radius:50%;
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
    font-size:12px; font-weight:600; letter-spacing:.3px;
}
.nav-btn{
    padding:9px 20px; border-radius:10px;
    font-family:'DM Sans',sans-serif;
    font-size:13px; font-weight:600;
    cursor:pointer; transition:.2s;
    text-decoration:none; border:none;
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

/* ── HERO ── */
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
@keyframes livePulse{ 0%,100%{opacity:1} 50%{opacity:.3} }

h1{
    font-size:62px; font-weight:700;
    line-height:1.1; letter-spacing:-2px;
    margin-bottom:24px;
}
h1 .hl{
    background:linear-gradient(135deg,var(--accent),var(--accent2));
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
}

.hero-desc{
    font-size:17px; color:var(--muted);
    line-height:1.85; max-width:560px;
    margin-bottom:36px;
}

/* TECH STACK ROW */
.stack-row{
    display:flex; flex-wrap:wrap; gap:10px;
    margin-bottom:40px;
}
.stack-tag{
    display:inline-flex; align-items:center; gap:7px;
    background:var(--surface);
    border:1px solid var(--border);
    padding:7px 14px; border-radius:8px;
    font-size:12px; font-weight:600;
    color:var(--muted);
    font-family:'DM Mono',monospace;
    transition:.2s;
}
.stack-tag:hover{ color:var(--text); border-color:rgba(255,255,255,.15); }
.stack-tag .sdot{ width:6px; height:6px; border-radius:50%; }
.dot-blue{ background:var(--accent); }
.dot-cyan{ background:var(--accent2); }
.dot-green{ background:var(--green); }
.dot-orange{ background:#f97316; }
.dot-purple{ background:#a78bfa; }

/* STAT ROW */
.stat-row{
    display:flex; gap:0;
    border:1px solid var(--border);
    border-radius:16px;
    overflow:hidden;
    max-width:480px;
    background:var(--surface);
    margin-bottom:24px;
}
.stat-item{
    flex:1; padding:18px 16px; text-align:center;
    border-right:1px solid var(--border);
}
.stat-item:last-child{ border-right:none; }
.stat-num{
    font-size:24px; font-weight:700;
    letter-spacing:-1px; line-height:1;
    margin-bottom:4px;
    background:linear-gradient(135deg,var(--accent),var(--accent2));
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
}
.stat-lbl{
    font-size:10px; font-weight:600;
    color:var(--muted); text-transform:uppercase; letter-spacing:.7px;
}

/* ── ESP32 STATUS WIDGET ── */
.esp-widget{
    display:inline-flex; align-items:center; gap:12px;
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:14px;
    padding:12px 18px;
    max-width:480px;
    width:100%;
    transition:.3s;
}
.esp-widget.online{
    border-color:rgba(34,197,94,.3);
    background:rgba(34,197,94,.06);
}
.esp-widget.offline{
    border-color:rgba(244,63,94,.25);
    background:rgba(244,63,94,.05);
}
.esp-led{
    width:10px; height:10px;
    border-radius:50%;
    flex-shrink:0;
    background:var(--muted);
    transition:.3s;
}
.esp-widget.online  .esp-led{
    background:#4ade80;
    box-shadow:0 0 0 3px rgba(74,222,128,.2);
    animation:ledPulse 1.5s infinite;
}
.esp-widget.offline .esp-led{
    background:#f43f5e;
    box-shadow:none;
    animation:none;
}
@keyframes ledPulse{
    0%,100%{ box-shadow:0 0 0 3px rgba(74,222,128,.2); }
    50%{ box-shadow:0 0 0 6px rgba(74,222,128,0); }
}
.esp-info{ flex:1; min-width:0; }
.esp-label{
    font-size:12px; font-weight:700;
    text-transform:uppercase; letter-spacing:.6px;
    color:var(--muted);
}
.esp-status-text{
    font-size:13px; font-weight:600;
    margin-top:1px;
    color:var(--muted);
    transition:.3s;
}
.esp-widget.online  .esp-status-text{ color:#4ade80; }
.esp-widget.offline .esp-status-text{ color:#f87171; }
.esp-time{
    font-family:'DM Mono',monospace;
    font-size:11px; color:var(--muted);
    white-space:nowrap;
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
    position:relative; overflow:hidden;
}
.portal::before{
    content:''; position:absolute;
    top:0; left:0; right:0; height:3px;
    background:linear-gradient(90deg,var(--accent),var(--accent2),var(--green));
}
.portal-head{ text-align:center; margin-bottom:28px; }
.portal-head h2{ font-size:22px; font-weight:700; letter-spacing:-.4px; margin-bottom:6px; }
.portal-head p{ font-size:13px; color:var(--muted); }

.or-divider{
    display:flex; align-items:center; gap:12px;
    margin:20px 0;
    font-size:12px; color:var(--muted); font-weight:600;
}
.or-divider::before,.or-divider::after{
    content:''; flex:1; height:1px; background:var(--border);
}

.portal-btn{
    width:100%; height:52px;
    border:none; border-radius:13px;
    font-family:'DM Sans',sans-serif;
    font-size:15px; font-weight:700;
    cursor:pointer; transition:.2s;
    text-decoration:none;
    display:flex; align-items:center; justify-content:center; gap:10px;
}
.portal-btn:hover{ transform:translateY(-2px); }
.btn-admin{
    background:linear-gradient(135deg,var(--accent),#5b8af9);
    color:#fff;
    box-shadow:0 6px 20px rgba(59,110,248,.3);
}
.btn-admin:hover{ box-shadow:0 8px 24px rgba(59,110,248,.45); }

.s-input{
    width:100%; height:48px;
    padding:0 16px;
    background:rgba(255,255,255,.07);
    border:1px solid var(--border);
    border-radius:13px;
    color:var(--text);
    font-family:'DM Sans',sans-serif;
    font-size:14px; outline:none; transition:.15s;
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

.portal-stats{
    display:grid; grid-template-columns:repeat(3,1fr);
    gap:10px; margin-top:22px;
    padding-top:22px;
    border-top:1px solid var(--border);
}
.pstat{ text-align:center; }
.pstat-val{
    font-size:22px; font-weight:700; letter-spacing:-1px;
    background:linear-gradient(135deg,var(--accent),var(--accent2));
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
}
.pstat-label{
    font-size:10px; font-weight:600;
    color:var(--muted); text-transform:uppercase; letter-spacing:.6px;
}

/* ── MARQUEE ── */
.marquee-section{
    position:relative; z-index:1;
    border-top:1px solid var(--border);
    border-bottom:1px solid var(--border);
    background:rgba(255,255,255,.02);
    overflow:hidden;
    padding:16px 0;
}
.marquee-track{ display:flex; width:max-content; }
.m-item{
    display:flex; align-items:center; gap:10px;
    padding:0 28px;
    font-family:'DM Mono',monospace;
    font-size:12px; font-weight:500;
    color:var(--muted); white-space:nowrap;
    border-right:1px solid var(--border);
    flex-shrink:0;
}
.m-item:last-child{ border-right:none; }
.m-dot{ width:5px; height:5px; border-radius:50%; flex-shrink:0; }
.m-dot-blue{ background:var(--accent); }
.m-dot-cyan{ background:var(--accent2); }
.m-dot-green{ background:var(--green); }

/* ── FOOTER ── */
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

/* ── MOBILE ── */
@media(max-width:1024px){
    .hero{ flex-direction:column; padding:40px 24px; gap:40px; min-height:unset; }
    .hero-right{ flex:none; width:100%; max-width:480px; margin:0 auto; }
    h1{ font-size:44px; }
}
@media(max-width:640px){
    nav{ padding:0 20px; }
    .nav-tag{ display:none; }
    h1{ font-size:34px; letter-spacing:-1px; }
    .hero-desc{ font-size:15px; }
    .portal{ padding:24px 20px; }
    footer{ flex-direction:column; align-items:center; text-align:center; padding:20px; }
    .blob-1{ width:300px;height:300px; }
    .blob-2{ width:250px;height:250px; }
    .stat-row{ max-width:100%; }
    .esp-widget{ max-width:100%; }
}
@media(max-width:380px){ h1{ font-size:28px; } }
</style>
</head>
<body>

<!-- BLOBS -->
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>

<!-- NAV -->
<nav>
    <a href="index.php" class="nav-logo">
        <div class="dot"></div>
        Smart Attendance
    </a>
    <div class="nav-actions">
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
            No more roll calls. No proxy attendance. Students tap their fingerprint
            on an ESP32-powered device and Smart Attendance handles everything —
            marking, tracking, reporting, and auto-absent at end of day.
        </p>

        <!-- TECH STACK -->
        <div class="stack-row">
            <span class="stack-tag"><span class="sdot dot-blue"></span>ESP32</span>
            <span class="stack-tag"><span class="sdot dot-cyan"></span>Fingerprint Sensor</span>
            <span class="stack-tag"><span class="sdot dot-green"></span>PHP</span>
            <span class="stack-tag"><span class="sdot dot-orange"></span>TiDB</span>
            <span class="stack-tag"><span class="sdot dot-purple"></span>Render</span>
        </div>

        <!-- STATS -->
        <div class="stat-row">
            <div class="stat-item">
                <div class="stat-num">100%</div>
                <div class="stat-lbl">Accuracy</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">24/7</div>
                <div class="stat-lbl">Monitoring</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">100+</div>
                <div class="stat-lbl">Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">0</div>
                <div class="stat-lbl">Manual Steps</div>
            </div>
        </div>

        <!-- ESP32 STATUS -->
        <div class="esp-widget" id="espWidget">
            <div class="esp-led" id="espLed"></div>
            <div class="esp-info">
                <div class="esp-label">ESP32 Device</div>
                <div class="esp-status-text" id="espStatusText">Checking...</div>
            </div>
            <div class="esp-time" id="espTime">—</div>
        </div>

    </div>

    <!-- PORTAL -->
    <div class="hero-right">
        <div class="portal">

            <div class="portal-head">
                <h2>System Portal</h2>
                <p>Admin panel or attendance view</p>
            </div>

            <a href="admin/admin_login.php" class="portal-btn btn-admin">
                Admin Login
            </a>

            <div class="or-divider">or</div>

            <form action="view_attendance.php" method="GET">
                <input class="s-input" type="text" name="student_id"
                       placeholder="Enter Student ID" required>
                <button type="submit" class="portal-btn btn-student">
                    View My Attendance
                </button>
            </form>

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

<!-- MARQUEE -->
<div class="marquee-section">
    <div class="marquee-track" id="marqueeTrack">
        <?php
        $items = [
            ["IoT Integration",       "blue"],
            ["Fingerprint Auth",       "cyan"],
            ["Real-Time Dashboard",    "green"],
            ["Auto Absent Marking",    "blue"],
            ["ESP32 Hardware",         "cyan"],
            ["Biometric Verification", "green"],
            ["Attendance Analytics",   "blue"],
            ["Excel Export",           "cyan"],
            ["Role-Based Access",      "green"],
            ["Mobile Responsive",      "blue"],
            ["Smart Attendance",       "cyan"],
            ["Final Year Project",     "green"],
        ];
        foreach($items as $item){
            echo '<div class="m-item"><span class="m-dot m-dot-'.$item[1].'"></span>'.$item[0].'</div>';
        }
        ?>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <span>Smart Attendance Monitoring System &copy; 2026 &mdash; Final Year Engineering Project</span>
    <a href="aboutus.php">About Us</a>
</footer>

<script>
/* ── MARQUEE ── */
(function(){
    var track = document.getElementById('marqueeTrack');
    if(!track) return;
    var orig = track.innerHTML;
    track.innerHTML = orig + orig + orig;
    var pos = 0, speed = 0.5, paused = false, singleWidth = 0;
    function getWidth(){
        var items = track.querySelectorAll('.m-item');
        var total = 0, count = items.length / 3;
        for(var i = 0; i < count; i++) total += items[i].offsetWidth;
        return total;
    }
    function step(){
        if(!paused){
            pos += speed;
            if(!singleWidth) singleWidth = getWidth();
            if(pos >= singleWidth) pos = 0;
            track.style.transform = 'translateX(-' + pos + 'px)';
        }
        requestAnimationFrame(step);
    }
    if(window.innerWidth < 640) speed = 0.4;
    track.parentElement.addEventListener('mouseenter', function(){ paused = true; });
    track.parentElement.addEventListener('mouseleave', function(){ paused = false; });
    requestAnimationFrame(step);
})();

/* ── ESP32 LIVE STATUS (polls every second) ── */
(function(){
    var widget     = document.getElementById('espWidget');
    var statusText = document.getElementById('espStatusText');
    var timeEl     = document.getElementById('espTime');

    function poll(){
        fetch('esp_status.php?action=status')
            .then(function(r){ return r.json(); })
            .then(function(d){
                if(d.online){
                    widget.className     = 'esp-widget online';
                    statusText.textContent = 'Online';
                    timeEl.textContent   = 'Last seen: ' + d.time_ago;
                } else {
                    widget.className     = 'esp-widget offline';
                    statusText.textContent = d.last_ping ? 'Offline' : 'Never Connected';
                    timeEl.textContent   = d.last_ping ? 'Last: ' + d.time_ago : '—';
                }
            })
            .catch(function(){
                widget.className     = 'esp-widget offline';
                statusText.textContent = 'Cannot reach server';
                timeEl.textContent   = '—';
            });
    }

    poll();                          // run immediately
    setInterval(poll, 1000);         // then every second
})();
</script>

</body>
</html>