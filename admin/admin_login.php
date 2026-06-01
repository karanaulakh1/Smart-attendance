<?php
session_start();
include '../database.php';

$error = "";

if(isset($_POST['login'])){

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username=? AND password=?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $admin = $result->fetch_assoc();
        $_SESSION['admin']    = $admin['username'];
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['role']     = isset($admin['role']) ? $admin['role'] : 'admin';
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Smart Attendance</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>

:root{
    --bg:      #060b14;
    --surface: rgba(255,255,255,0.04);
    --border:  rgba(255,255,255,0.08);
    --accent:  #3b6ef8;
    --accent2: #6ee7f7;
    --text:    #f0f4ff;
    --muted:   #64748b;
}

*, *::before, *::after{ margin:0; padding:0; box-sizing:border-box; }

body{
    font-family:'DM Sans',sans-serif;
    min-height:100vh;
    background:var(--bg);
    color:var(--text);
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    position:relative;
    padding:20px;
}

/* ── BLOBS ── */
.blob{
    position:fixed;
    border-radius:50%;
    filter:blur(90px);
    opacity:.15;
    pointer-events:none;
    z-index:0;
}
.blob-1{
    width:500px; height:500px;
    background:var(--accent);
    top:-160px; left:-160px;
}
.blob-2{
    width:420px; height:420px;
    background:var(--accent2);
    bottom:-120px; right:-120px;
}

/* ── BACK LINK ── */
.back-link{
    position:fixed;
    top:24px; left:24px;
    display:inline-flex; align-items:center; gap:8px;
    color:var(--muted);
    text-decoration:none;
    font-size:13px; font-weight:600;
    padding:9px 16px;
    border-radius:10px;
    border:1px solid var(--border);
    background:var(--surface);
    backdrop-filter:blur(12px);
    transition:.2s;
    z-index:10;
}
.back-link:hover{ color:var(--text); background:rgba(255,255,255,.07); }
.back-link svg{ flex-shrink:0; }

/* ── CARD ── */
.card{
    position:relative; z-index:1;
    width:100%; max-width:420px;
    background:rgba(255,255,255,.04);
    border:1px solid var(--border);
    backdrop-filter:blur(24px);
    border-radius:24px;
    padding:40px 36px;
    box-shadow:0 24px 64px rgba(0,0,0,.5);
    overflow:hidden;
}
.card::before{
    content:'';
    position:absolute; top:0; left:0; right:0; height:3px;
    background:linear-gradient(90deg,var(--accent),var(--accent2));
}

/* ── HEADER ── */
.card-logo{
    width:56px; height:56px;
    border-radius:16px;
    background:linear-gradient(135deg,var(--accent),#5b8af9);
    display:flex; align-items:center; justify-content:center;
    font-size:24px;
    margin:0 auto 22px;
    box-shadow:0 8px 24px rgba(59,110,248,.35);
}
.card-title{
    text-align:center;
    font-size:24px; font-weight:700;
    letter-spacing:-.4px;
    margin-bottom:6px;
}
.card-sub{
    text-align:center;
    font-size:13px; color:var(--muted);
    margin-bottom:28px;
    line-height:1.5;
}

/* ── ALERT ── */
.alert-err{
    display:flex; align-items:center; gap:10px;
    background:rgba(244,63,94,.1);
    border:1px solid rgba(244,63,94,.22);
    color:#fb7185;
    padding:12px 16px;
    border-radius:12px;
    margin-bottom:20px;
    font-size:14px; font-weight:500;
    animation:slideIn .25s ease;
}
@keyframes slideIn{
    from{ opacity:0; transform:translateY(-6px); }
    to{   opacity:1; transform:translateY(0); }
}

/* ── FORM ── */
.field{ margin-bottom:14px; }
.field label{
    display:block;
    font-size:11px; font-weight:700;
    color:var(--muted); text-transform:uppercase; letter-spacing:.8px;
    margin-bottom:6px;
}
.f-input{
    width:100%;
    padding:13px 16px;
    background:rgba(255,255,255,.06);
    border:1px solid var(--border);
    border-radius:12px;
    color:var(--text);
    font-family:'DM Sans',sans-serif;
    font-size:15px;
    outline:none;
    transition:.15s;
}
.f-input::placeholder{ color:var(--muted); opacity:.7; }
.f-input:focus{
    border-color:var(--accent);
    background:rgba(59,110,248,.06);
}

/* ── SUBMIT ── */
.login-btn{
    width:100%; height:50px;
    margin-top:8px;
    border:none; border-radius:12px;
    background:linear-gradient(135deg,var(--accent),#5b8af9);
    color:#fff;
    font-family:'DM Sans',sans-serif;
    font-size:15px; font-weight:700;
    cursor:pointer; transition:.2s;
    letter-spacing:.2px;
    box-shadow:0 6px 20px rgba(59,110,248,.3);
}
.login-btn:hover{ transform:translateY(-2px); box-shadow:0 8px 26px rgba(59,110,248,.45); }
.login-btn:active{ transform:translateY(0); }

/* ── FOOTER STRIP ── */
.card-footer{
    margin-top:24px;
    padding-top:20px;
    border-top:1px solid var(--border);
    display:flex; justify-content:center; gap:8px; flex-wrap:wrap;
}
.tech-tag{
    background:rgba(255,255,255,.05);
    border:1px solid var(--border);
    padding:4px 12px; border-radius:50px;
    font-size:11px; font-weight:600;
    color:var(--muted);
    font-family:'DM Mono',monospace;
}

/* ── PAGE FOOTER ── */
.page-footer{
    position:relative; z-index:1;
    margin-top:28px;
    font-size:12px; color:var(--muted);
    text-align:center;
}
.page-footer a{
    color:var(--muted); text-decoration:none;
    border-bottom:1px solid transparent; transition:.15s;
}
.page-footer a:hover{ color:var(--text); border-color:var(--muted); }

/* ── MOBILE ── */
@media(max-width:480px){
    .card{ padding:30px 22px; }
    .card-title{ font-size:22px; }
    .back-link{ top:16px; left:16px; padding:7px 12px; font-size:12px; }
}

@media(max-width:360px){
    .card-title{ font-size:20px; }
    .f-input{ font-size:14px; }
}
</style>
</head>
<body>

<!-- BLOBS -->
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>

<!-- BACK LINK -->
<a href="../index.php" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    Back to Home
</a>

<!-- CARD -->
<div class="card">

    <div class="card-logo">🔐</div>

    <div class="card-title">Admin Login</div>
    <div class="card-sub">Smart Attendance Monitoring System</div>

    <?php if($error != ""){ ?>
    <div class="alert-err">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php } ?>

    <form method="POST" autocomplete="off">

        <div class="field">
            <label>Username</label>
            <input class="f-input" type="text" name="username"
                   placeholder="Enter your username" required
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
        </div>

        <div class="field">
            <label>Password</label>
            <input class="f-input" type="password" name="password"
                   placeholder="••••••••" required>
        </div>

        <button type="submit" name="login" class="login-btn">
            Login to Dashboard
        </button>

    </form>

    <div class="card-footer">
        <span class="tech-tag">ESP32</span>
        <span class="tech-tag">Fingerprint</span>
        <span class="tech-tag">PHP</span>
        <span class="tech-tag">MySQL</span>
    </div>

</div>

<!-- PAGE FOOTER -->
<div class="page-footer">
    Smart Attendance &copy; 2026 &nbsp;&mdash;&nbsp;
    <a href="../aboutus.php">About Us</a>
</div>

</body>
</html>