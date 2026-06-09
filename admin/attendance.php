<?php
session_start();
include '../database.php';
if(!isset($_SESSION['admin'])){ header("Location: admin_login.php"); exit(); }
$admin_role = $_SESSION['role'];
date_default_timezone_set("Asia/Kolkata");

/* LOAD GROUPS */
$groups_query = $conn->query("SELECT * FROM groups_registry ORDER BY id ASC");
$groups = [];
while($g = $groups_query->fetch_assoc()) $groups[] = $g;

/* ACTIVE GROUP */
$active_group = isset($_GET['group']) ? $_GET['group'] : 'students';
$active_info  = null;
foreach($groups as $g){ if($g['table_name']===$active_group){ $active_info=$g; break; } }
if(!$active_info){ $active_group='students'; foreach($groups as $g){ if($g['table_name']==='students'){ $active_info=$g; break; } } }

$member_table = $active_info['table_name'];
$att_table    = $active_info['attendance_table'];

$success = $error = "";
$today   = date("Y-m-d");

/* MARK ATTENDANCE (manual) */
if(isset($_POST['api_attendance'])){
    $student_id = $_POST['student_id'];
    $status     = $_POST['status'];
    $in_time    = !empty($_POST['in_time'])  ? date("h:i A", strtotime($_POST['in_time'])) : null;
    $out_time   = !empty($_POST['out_time']) ? date("h:i A", strtotime($_POST['out_time'])) : null;
    $working    = null;

    // calc working hours if both given
    if($in_time && $out_time){
        $in_obj  = DateTime::createFromFormat("h:i A", $in_time);
        $out_obj = DateTime::createFromFormat("h:i A", $out_time);
        if($in_obj && $out_obj && $out_obj > $in_obj){
            $diff    = $in_obj->diff($out_obj);
            $working = sprintf("%02d:%02d", $diff->h + ($diff->days*24), $diff->i);
        }
    }

    $check = $conn->query("SELECT * FROM `$att_table` WHERE student_id='$student_id' AND date='$today'");
    if($check->num_rows == 0){
        $in_val  = $in_time  ? "'$in_time'"  : "NULL";
        $out_val = $out_time ? "'$out_time'" : "NULL";
        $wrk_val = $working  ? "'$working'"  : "NULL";
        $conn->query("INSERT INTO `$att_table` (student_id,status,date,time,in_time,out_time,working_hours)
            VALUES ('$student_id','$status','$today',NOW(),$in_val,$out_val,$wrk_val)");
        $success = "Attendance marked.";
    } else {
        $error = "Already marked today. Use the override below.";
    }
}

/* OVERRIDE (update existing record) */
if(isset($_POST['override_attendance'])){
    $rec_id   = (int)$_POST['record_id'];
    $status   = $_POST['status'];
    $in_time  = !empty($_POST['in_time'])  ? date("h:i A", strtotime($_POST['in_time'])) : null;
    $out_time = !empty($_POST['out_time']) ? date("h:i A", strtotime($_POST['out_time'])) : null;
    $working  = null;

    if($in_time && $out_time){
        $in_obj  = DateTime::createFromFormat("h:i A", $in_time);
        $out_obj = DateTime::createFromFormat("h:i A", $out_time);
        if($in_obj && $out_obj && $out_obj > $in_obj){
            $diff    = $in_obj->diff($out_obj);
            $working = sprintf("%02d:%02d", $diff->h + ($diff->days*24), $diff->i);
        }
    }

    $in_val  = $in_time  ? "'$in_time'"  : "NULL";
    $out_val = $out_time ? "'$out_time'" : "NULL";
    $wrk_val = $working  ? "'$working'"  : "NULL";

    $conn->query("UPDATE `$att_table` SET
        status='$status', in_time=$in_val, out_time=$out_val, working_hours=$wrk_val
        WHERE id=$rec_id");
    $success = "Record updated.";
}

/* STATS */
$totalMembers = $conn->query("SELECT COUNT(*) as c FROM `$member_table`")->fetch_assoc()['c'];
$presentCount = $conn->query("SELECT COUNT(*) as c FROM `$att_table` WHERE date='$today' AND status='Present'")->fetch_assoc()['c'];
$absentCount  = $conn->query("SELECT COUNT(*) as c FROM `$att_table` WHERE date='$today' AND status='Absent'")->fetch_assoc()['c'];
$lateCount    = $conn->query("SELECT COUNT(*) as c FROM `$att_table` WHERE date='$today' AND status='Late'")->fetch_assoc()['c'];

/* AVG WORKING HOURS TODAY */
$avgRow = $conn->query("SELECT AVG(TIME_TO_SEC(working_hours)) as avg_sec FROM `$att_table`
    WHERE date='$today' AND working_hours IS NOT NULL AND working_hours != '00:00'")->fetch_assoc();
$avg_hours = "—";
if($avgRow && $avgRow['avg_sec']){
    $h = floor($avgRow['avg_sec']/3600);
    $m = floor(($avgRow['avg_sec']%3600)/60);
    $avg_hours = sprintf("%02d:%02d", $h, $m);
}

/* MEMBERS LIST */
$members_list = $conn->query("SELECT * FROM `$member_table` ORDER BY name ASC");

/* TODAY ATTENDANCE */
$attendance = $conn->query("
    SELECT a.*, m.name, m.department, m.course
    FROM `$att_table` a
    LEFT JOIN `$member_table` m ON a.student_id = m.student_id
    WHERE a.date='$today'
    ORDER BY a.id DESC
");

/* CALENDAR DATA — selected month */
$cal_month = isset($_GET['cal_month']) ? (int)$_GET['cal_month'] : (int)date("m");
$cal_year  = isset($_GET['cal_year'])  ? (int)$_GET['cal_year']  : (int)date("Y");
$cal_month = max(1, min(12, $cal_month));

$first_day   = mktime(0,0,0,$cal_month,1,$cal_year);
$days_in_month = date("t", $first_day);
$start_dow   = (int)date("w", $first_day); // 0=Sun

// Fetch all attendance for selected month/year for calendar
$cal_data = [];
$cal_query = $conn->query("
    SELECT a.date, a.student_id, a.status, a.in_time, a.out_time, a.working_hours, m.name
    FROM `$att_table` a
    LEFT JOIN `$member_table` m ON a.student_id = m.student_id
    WHERE MONTH(a.date)=$cal_month AND YEAR(a.date)=$cal_year
");
while($row = $cal_query->fetch_assoc()){
    $d = (int)date("j", strtotime($row['date']));
    $cal_data[$d][] = $row;
}

// Stat per day for calendar coloring
$cal_stats = [];
for($d = 1; $d <= $days_in_month; $d++){
    $dt = sprintf("%04d-%02d-%02d", $cal_year, $cal_month, $d);
    $p = $conn->query("SELECT COUNT(*) as c FROM `$att_table` WHERE date='$dt' AND status='Present'")->fetch_assoc()['c'];
    $a = $conn->query("SELECT COUNT(*) as c FROM `$att_table` WHERE date='$dt' AND status='Absent'")->fetch_assoc()['c'];
    $cal_stats[$d] = ['present'=>$p,'absent'=>$a,'total'=>$p+$a];
}

$month_names = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Attendance — Smart Attendance</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{ --bg:#080d18; --surface:#0f1929; --surface2:#162035; --border:rgba(255,255,255,0.07); --accent:#3b6ef8; --accent2:#6ee7f7; --green:#22c55e; --red:#f43f5e; --amber:#f59e0b; --purple:#a78bfa; --text:#e2e8f0; --muted:#64748b; --sidebar-w:240px; }
*{ margin:0; padding:0; box-sizing:border-box; }
body{ font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; overflow-x:hidden; }
.mob-bar{ display:none; align-items:center; justify-content:space-between; padding:14px 18px; background:var(--surface); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:800; }
.mob-bar .brand{ font-size:15px; font-weight:700; }
.hamburger{ background:none; border:none; color:var(--text); font-size:22px; cursor:pointer; padding:4px 6px; border-radius:8px; }
.overlay{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:900; }
.overlay.on{ display:block; }
.sidebar{ width:var(--sidebar-w); height:100vh; position:fixed; top:0; left:0; background:var(--surface); border-right:1px solid var(--border); padding:24px 16px; z-index:1000; transition:.25s ease; display:flex; flex-direction:column; overflow-y:auto; }
.sidebar .logo{ font-size:20px; font-weight:700; line-height:1.4; padding:0 6px; margin-bottom:32px; }
.nav-section{ font-size:10px; font-weight:600; color:var(--muted); letter-spacing:1.2px; text-transform:uppercase; padding:0 8px; margin:20px 0 8px; }
.sidebar a{ display:flex; align-items:center; gap:10px; color:var(--text); text-decoration:none; padding:11px 12px; border-radius:10px; margin-bottom:3px; font-size:14px; font-weight:500; transition:.15s; }
.sidebar a:hover{ background:var(--surface2); }
.sidebar a.active{ background:var(--accent); color:#fff; font-weight:600; }
.sidebar .spacer{ flex:1; }
.sidebar .logout{ color:#f87171; }
.sidebar .logout:hover{ background:rgba(244,63,94,.12); }
.main{ margin-left:var(--sidebar-w); padding:36px 40px; min-height:100vh; }
.top-bar{ display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px; margin-bottom:20px; }
.page-title{ font-size:28px; font-weight:700; letter-spacing:-.5px; }
.date-pill{ background:var(--surface2); border:1px solid var(--border); padding:9px 18px; border-radius:50px; font-size:13px; font-weight:500; color:var(--accent2); white-space:nowrap; }
/* GROUP TABS */
.group-tabs{ display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap; }
.gtab{ padding:8px 18px; border-radius:10px; border:1px solid var(--border); background:var(--surface); color:var(--muted); font-size:13px; font-weight:600; text-decoration:none; transition:.15s; }
.gtab:hover{ color:var(--text); background:var(--surface2); }
.gtab.active{ background:var(--accent); border-color:var(--accent); color:#fff; }
/* ALERT */
.alert{ display:flex; align-items:center; gap:10px; padding:14px 18px; border-radius:14px; margin-bottom:22px; font-size:14px; font-weight:500; animation:slideIn .3s ease; }
@keyframes slideIn{ from{ opacity:0; transform:translateY(-8px); } to{ opacity:1; transform:translateY(0); } }
.alert-ok { background:rgba(34,197,94,.10); border:1px solid rgba(34,197,94,.22); color:#4ade80; }
.alert-err{ background:rgba(244,63,94,.10); border:1px solid rgba(244,63,94,.2);  color:#fb7185; }
/* STATS */
.stats-row{ display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:24px; }
.stat{ background:var(--surface); border:1px solid var(--border); border-radius:18px; padding:18px 20px; position:relative; overflow:hidden; transition:.2s; }
.stat:hover{ transform:translateY(-2px); }
.stat::before{ content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.stat.s-present::before{ background:linear-gradient(90deg,#16a34a,var(--green)); }
.stat.s-absent::before { background:linear-gradient(90deg,#be123c,var(--red)); }
.stat.s-late::before   { background:linear-gradient(90deg,#d97706,var(--amber)); }
.stat.s-total::before  { background:linear-gradient(90deg,var(--accent),var(--accent2)); }
.stat.s-hours::before  { background:linear-gradient(90deg,var(--purple),#c4b5fd); }
.stat-val{ font-size:30px; font-weight:700; letter-spacing:-1px; line-height:1; margin-bottom:5px; }
.stat-label{ font-size:10px; color:var(--muted); font-weight:600; text-transform:uppercase; letter-spacing:.8px; }
/* CARD */
.card{ background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:26px; margin-bottom:22px; }
.card-head{ display:flex; align-items:center; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
.card-head h2{ font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); }
.count-pill{ margin-left:auto; background:var(--surface2); border:1px solid var(--border); padding:3px 10px; border-radius:50px; font-size:11px; font-weight:600; color:var(--muted); font-family:'DM Mono',monospace; }
/* VIEW TOGGLE */
.view-toggle{ display:flex; gap:6px; margin-left:auto; }
.vtab{ padding:6px 16px; border-radius:8px; font-size:12px; font-weight:700; border:1px solid var(--border); background:var(--surface2); color:var(--muted); cursor:pointer; transition:.15s; }
.vtab.active{ background:var(--accent); border-color:var(--accent); color:#fff; }
/* FORMS */
.form-row{ display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; }
.form-field{ display:flex; flex-direction:column; gap:5px; flex:1; min-width:140px; }
.form-field label{ font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.7px; }
.f-select,.f-input{ padding:11px 14px; background:var(--surface2); border:1px solid var(--border); border-radius:12px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:13px; outline:none; transition:.15s; width:100%; }
.f-select:focus,.f-input:focus{ border-color:var(--accent); }
.f-select{ appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2364748b' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; background-color:var(--surface2); padding-right:32px; cursor:pointer; }
.divider-line{ border:none; border-top:1px solid var(--border); margin:20px 0; }
.btn{ padding:11px 20px; border:none; border-radius:12px; font-family:'DM Sans',sans-serif; font-size:13px; font-weight:700; cursor:pointer; transition:.2s; white-space:nowrap; display:inline-flex; align-items:center; gap:7px; }
.btn:hover{ transform:translateY(-1px); }
.btn-primary{ background:linear-gradient(135deg,var(--accent),#5b8af9); color:#fff; box-shadow:0 4px 14px rgba(59,110,248,.3); }
.btn-green  { background:linear-gradient(135deg,#16a34a,var(--green)); color:#fff; }
.btn-amber  { background:linear-gradient(135deg,#d97706,var(--amber)); color:#fff; }
/* TABLE */
.table-wrap{ overflow-x:auto; -webkit-overflow-scrolling:touch; }
table{ width:100%; min-width:700px; border-collapse:collapse; }
thead th{ padding:10px 14px; font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.8px; text-align:left; white-space:nowrap; border-bottom:1px solid var(--border); }
tbody td{ padding:12px 14px; font-size:13px; border-bottom:1px solid rgba(255,255,255,.03); vertical-align:middle; }
tbody tr:last-child td{ border-bottom:none; }
tbody tr:hover td{ background:rgba(255,255,255,.02); }
.name-cell{ display:flex; flex-direction:column; gap:1px; }
.name-main{ font-weight:600; font-size:13px; }
.name-sub{ font-size:11px; color:var(--muted); }
.id-cell{ font-family:'DM Mono',monospace; font-size:11px; color:var(--muted); }
.time-mono{ font-family:'DM Mono',monospace; font-size:12px; color:var(--text); }
.time-null{ font-family:'DM Mono',monospace; font-size:12px; color:var(--muted); }
.hours-cell{ font-family:'DM Mono',monospace; font-size:12px; font-weight:700; }
.hours-good{ color:#4ade80; }
.hours-short{ color:#fbbf24; }
.hours-null{ color:var(--muted); }
.badge{ display:inline-flex; align-items:center; padding:4px 11px; border-radius:50px; font-size:11px; font-weight:700; letter-spacing:.3px; text-transform:uppercase; }
.b-present{ background:rgba(34,197,94,.12); color:#4ade80; border:1px solid rgba(34,197,94,.22); }
.b-absent{ background:rgba(244,63,94,.10); color:#fb7185; border:1px solid rgba(244,63,94,.18); }
.b-late{ background:rgba(245,158,11,.10); color:#fbbf24; border:1px solid rgba(245,158,11,.18); }
.edit-btn{ display:inline-flex; align-items:center; padding:4px 10px; border-radius:7px; font-size:11px; font-weight:600; background:rgba(59,110,248,.12); color:#93c5fd; border:1px solid rgba(59,110,248,.2); cursor:pointer; transition:.15s; }
.edit-btn:hover{ background:rgba(59,110,248,.25); }
.empty{ text-align:center; padding:40px; }
.empty p{ color:var(--muted); font-size:14px; }

/* ── CALENDAR ── */
.calendar-wrap{ display:none; }
.calendar-wrap.active{ display:block; }
.table-view{ display:block; }
.table-view.hidden{ display:none; }

.cal-nav{ display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:10px; }
.cal-nav-title{ font-size:18px; font-weight:700; letter-spacing:-.3px; }
.cal-nav-btns{ display:flex; gap:8px; }
.cal-nav-btn{ padding:7px 16px; border-radius:9px; border:1px solid var(--border); background:var(--surface2); color:var(--muted); font-size:13px; font-weight:600; text-decoration:none; transition:.15s; }
.cal-nav-btn:hover{ color:var(--text); background:rgba(255,255,255,.07); }

.cal-grid{ display:grid; grid-template-columns:repeat(7,1fr); gap:6px; }
.cal-header{ text-align:center; padding:8px 4px; font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.7px; }
.cal-day{ background:var(--surface2); border:1px solid var(--border); border-radius:12px; padding:10px 8px; min-height:80px; cursor:pointer; transition:.15s; position:relative; }
.cal-day:hover{ border-color:rgba(255,255,255,.15); background:rgba(255,255,255,.06); }
.cal-day.empty{ background:transparent; border-color:transparent; cursor:default; }
.cal-day.today{ border-color:var(--accent); }
.cal-day.has-present{ border-color:rgba(34,197,94,.3); }
.cal-day.has-absent{ border-color:rgba(244,63,94,.25); }
.cal-day.all-present{ background:rgba(34,197,94,.07); }
.cal-day-num{ font-size:13px; font-weight:700; margin-bottom:6px; }
.cal-day.today .cal-day-num{ color:var(--accent2); }
.cal-chips{ display:flex; flex-direction:column; gap:3px; }
.cal-chip{ font-size:10px; font-weight:600; padding:2px 6px; border-radius:4px; }
.chip-p{ background:rgba(34,197,94,.15); color:#4ade80; }
.chip-a{ background:rgba(244,63,94,.12); color:#fb7185; }
.cal-day-summary{ font-size:10px; color:var(--muted); margin-top:4px; }

/* OVERRIDE MODAL */
.modal{ position:fixed; inset:0; background:rgba(0,0,0,.75); display:flex; justify-content:center; align-items:center; z-index:9000; padding:16px; display:none; }
.modal.open{ display:flex; }
.modal-box{ width:520px; max-width:100%; background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:28px; animation:popIn .2s ease; }
@keyframes popIn{ from{ opacity:0; transform:scale(.96); } to{ opacity:1; transform:scale(1); } }
.modal-head{ display:flex; align-items:center; justify-content:space-between; margin-bottom:22px; padding-bottom:14px; border-bottom:1px solid var(--border); }
.modal-head h2{ font-size:16px; font-weight:700; }
.modal-close{ background:var(--surface2); border:1px solid var(--border); color:var(--muted); width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:14px; }
.modal-close:hover{ color:var(--text); }
.modal-grid{ display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px; }
.m-field{ display:flex; flex-direction:column; gap:5px; }
.m-field label{ font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.7px; }
.m-input{ padding:11px 13px; background:var(--surface2); border:1px solid var(--border); border-radius:11px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:13px; outline:none; transition:.15s; width:100%; }
.m-input:focus{ border-color:var(--accent); }
.m-select{ appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2364748b' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; background-color:var(--surface2); padding-right:32px; cursor:pointer; }
.save-btn{ width:100%; padding:12px; border:none; border-radius:12px; background:linear-gradient(135deg,var(--accent),#5b8af9); color:#fff; font-family:'DM Sans',sans-serif; font-size:14px; font-weight:700; cursor:pointer; transition:.2s; }
.save-btn:hover{ transform:translateY(-1px); }

/* MOBILE */
@media(max-width:768px){
    .mob-bar{ display:flex; }
    .sidebar{ left:-280px; }
    .sidebar.on{ left:0; }
    .main{ margin-left:0; padding:16px; }
    .stats-row{ grid-template-columns:repeat(3,1fr); gap:10px; }
    .stat{ padding:14px 12px; }
    .stat-val{ font-size:24px; }
    .page-title{ font-size:22px; }
    .form-row{ flex-direction:column; }
    .f-select,.f-input{ min-width:unset; width:100%; }
    .btn{ width:100%; justify-content:center; }
    thead th, tbody td{ padding:10px 8px; font-size:12px; }
    .cal-grid{ grid-template-columns:repeat(7,1fr); gap:3px; }
    .cal-day{ padding:6px 4px; min-height:55px; border-radius:8px; }
    .cal-day-num{ font-size:11px; }
    .cal-chip{ font-size:9px; }
    .modal-grid{ grid-template-columns:1fr; }
}
@media(max-width:480px){
    .stats-row{ grid-template-columns:repeat(2,1fr); }
    .cal-chips{ display:none; }
}
</style>
</head>
<body>

<div class="mob-bar">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <div class="brand">📘 Smart Attendance</div>
    <div class="date-pill" style="font-size:11px;padding:6px 12px;"><?php echo date("d M"); ?></div>
</div>
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="logo">📘 Smart<br>Attendance</div>
    <div class="nav-section">Menu</div>
    <a href="admin_dashboard.php">🏠 Dashboard</a>
    <a href="add_member.php">➕ Add Member</a>
    <a href="manage_members.php">👥 Manage Members</a>
    <a href="attendance.php" class="active">🗓️ Attendance</a>
    <?php if($admin_role=='superadmin'){ ?><a href="admin_management.php">👮 Admin Management</a><?php } ?>
    <div class="spacer"></div>
    <div class="nav-section">Account</div>
    <a href="javascript:void(0);" onclick="confirmLogout()" class="logout">🚪 Logout</a>
</div>

<div class="main">

    <div class="top-bar">
        <div class="page-title">Attendance</div>
        <div class="date-pill">📅 <?php echo date("l, d M Y"); ?></div>
    </div>

    <!-- GROUP TABS -->
    <div class="group-tabs">
        <?php foreach($groups as $g){
            $ac = $g['table_name']===$active_group ? 'active' : '';
            echo '<a href="attendance.php?group='.$g['table_name'].'" class="gtab '.$ac.'">'.htmlspecialchars($g['group_name']).'</a>';
        } ?>
    </div>

    <?php if($success){ echo '<div class="alert alert-ok">&#10003; '.$success.'</div>'; } ?>
    <?php if($error){   echo '<div class="alert alert-err">&#9888; '.$error.'</div>'; } ?>

    <!-- STATS -->
    <div class="stats-row">
        <div class="stat s-present"><div class="stat-val"><?php echo $presentCount; ?></div><div class="stat-label">Present</div></div>
        <div class="stat s-absent"><div class="stat-val"><?php echo $absentCount; ?></div><div class="stat-label">Absent</div></div>
        <div class="stat s-late"><div class="stat-val"><?php echo $lateCount; ?></div><div class="stat-label">Late</div></div>
        <div class="stat s-total"><div class="stat-val"><?php echo $totalMembers; ?></div><div class="stat-label">Total</div></div>
        <div class="stat s-hours"><div class="stat-val" style="font-size:22px;"><?php echo $avg_hours; ?></div><div class="stat-label">Avg Hours</div></div>
    </div>

    <!-- MARK / EXPORT CARD -->
    <div class="card">
        <!-- EXPORT -->
        <div class="card-head"><h2>Export Attendance</h2></div>
        <form method="GET" action="export_excel.php">
            <input type="hidden" name="group" value="<?php echo $active_group; ?>">
            <input type="hidden" name="att_table" value="<?php echo $att_table; ?>">
            <div class="form-row">
                <div class="form-field">
                    <label>Course / Dept</label>
                    <select class="f-select" name="course">
                        <option value="">All</option>
                        <?php
                        $depts = $conn->query("SELECT DISTINCT course FROM `$member_table` WHERE course != '' ORDER BY course");
                        while($d=$depts->fetch_assoc()) echo '<option value="'.htmlspecialchars($d['course']).'">'.htmlspecialchars($d['course']).'</option>';
                        ?>
                    </select>
                </div>
                <div class="form-field">
                    <label>From Date</label>
                    <input class="f-input" type="date" name="from_date" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="form-field">
                    <label>To Date</label>
                    <input class="f-input" type="date" name="to_date" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <button type="submit" class="btn btn-green" style="align-self:flex-end;">Export Excel</button>
            </div>
        </form>

        <hr class="divider-line">

        <!-- EXPORT SINGLE MEMBER -->
        <div class="card-head"><h2>Export Single Member</h2></div>
        <form method="GET" action="export_excel.php">
            <input type="hidden" name="group" value="<?php echo $active_group; ?>">
            <input type="hidden" name="att_table" value="<?php echo $att_table; ?>">
            <div class="form-row">
                <div class="form-field" style="flex:2;">
                    <label>Select Member</label>
                    <select class="f-select" name="student_id" required>
                        <option value="">Select Member</option>
                        <?php
                        $ml2 = $conn->query("SELECT * FROM `$member_table` ORDER BY name ASC");
                        while($row=$ml2->fetch_assoc()){
                            echo '<option value="'.htmlspecialchars($row['student_id']).'">'.htmlspecialchars($row['name']).' · '.$row['student_id'].'</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-field">
                    <label>From Date</label>
                    <input class="f-input" type="date" name="from_date" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="form-field">
                    <label>To Date</label>
                    <input class="f-input" type="date" name="to_date" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <button type="submit" class="btn btn-amber" style="align-self:flex-end;">Export Member</button>
            </div>
        </form>

        <hr class="divider-line">

        <!-- MARK -->
        <div class="card-head"><h2>Mark Attendance</h2><span class="count-pill"><?php echo htmlspecialchars($active_info['group_name']); ?></span></div>
        <form method="POST">
            <input type="hidden" name="group" value="<?php echo $active_group; ?>">
            <div class="form-row">
                <div class="form-field" style="flex:2;">
                    <label>Member</label>
                    <select class="f-select" name="student_id" required>
                        <option value="">Select Member</option>
                        <?php while($row=$members_list->fetch_assoc()){ echo '<option value="'.htmlspecialchars($row['student_id']).'">'.htmlspecialchars($row['name']).' · '.$row['student_id'].'</option>'; } ?>
                    </select>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <select class="f-select" name="status" required>
                        <option value="Present">Present</option>
                        <option value="Absent">Absent</option>
                        <option value="Late">Late</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>In Time</label>
                    <input class="f-input" type="time" name="in_time">
                </div>
                <div class="form-field">
                    <label>Out Time</label>
                    <input class="f-input" type="time" name="out_time">
                </div>
                <button type="submit" name="api_attendance" class="btn btn-primary" style="align-self:flex-end;">Mark</button>
            </div>
        </form>
    </div>

    <!-- TODAY'S ATTENDANCE CARD -->
    <div class="card">
        <div class="card-head">
            <h2>Today's Attendance — <?php echo htmlspecialchars($active_info['group_name']); ?></h2>
            <span class="count-pill"><?php echo $presentCount+$absentCount+$lateCount; ?> / <?php echo $totalMembers; ?></span>
            <div class="view-toggle">
                <button class="vtab active" id="btnTable" onclick="switchView('table')">Table</button>
                <button class="vtab" id="btnCal"   onclick="switchView('calendar')">Calendar</button>
            </div>
        </div>

        <!-- TABLE VIEW -->
        <div class="table-view" id="tableView">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Member</th>
                            <th>Status</th>
                            <th>In Time</th>
                            <th>Out Time</th>
                            <th>Working Hrs</th>
                            <th>Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $count = 1; $hasRows = false;
                    while($att = $attendance->fetch_assoc()):
                        $hasRows = true;
                        $s = $att['status'];
                        $bClass = $s=='Present' ? 'b-present' : ($s=='Late' ? 'b-late' : 'b-absent');
                        $wh = $att['working_hours'];
                        $whClass = 'hours-null';
                        if($wh && $wh !== '00:00'){
                            $hrs = (int)explode(':',$wh)[0];
                            $whClass = $hrs >= 7 ? 'hours-good' : 'hours-short';
                        }
                    ?>
                    <tr>
                        <td class="id-cell"><?php echo str_pad($count++,2,'0',STR_PAD_LEFT); ?></td>
                        <td>
                            <div class="name-cell">
                                <span class="name-main"><?php echo htmlspecialchars($att['name']); ?></span>
                                <span class="name-sub"><?php echo htmlspecialchars($att['department']??''); ?></span>
                            </div>
                        </td>
                        <td><span class="badge <?php echo $bClass; ?>"><?php echo $s; ?></span></td>
                        <td class="<?php echo $att['in_time'] ? 'time-mono' : 'time-null'; ?>"><?php echo $att['in_time'] ?? '—'; ?></td>
                        <td class="<?php echo $att['out_time'] ? 'time-mono' : 'time-null'; ?>"><?php echo $att['out_time'] ?? '—'; ?></td>
                        <td class="hours-cell <?php echo $whClass; ?>"><?php echo ($wh && $wh!=='00:00') ? $wh : '—'; ?></td>
                        <td>
                            <button class="edit-btn" onclick="openOverride(
                                <?php echo $att['id']; ?>,
                                '<?php echo $s; ?>',
                                '<?php echo $att['in_time'] ? date('H:i', strtotime($att['in_time'])) : ''; ?>',
                                '<?php echo $att['out_time'] ? date('H:i', strtotime($att['out_time'])) : ''; ?>'
                            )">Edit</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if(!$hasRows): ?>
                    <tr><td colspan="7"><div class="empty"><p>No attendance recorded today yet.</p></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CALENDAR VIEW -->
        <div class="calendar-wrap" id="calView">
            <div class="cal-nav">
                <div class="cal-nav-title"><?php echo $month_names[$cal_month].' '.$cal_year; ?></div>
                <div class="cal-nav-btns">
                    <?php
                    $pm = $cal_month-1; $py = $cal_year; if($pm<1){$pm=12;$py--;}
                    $nm = $cal_month+1; $ny = $cal_year; if($nm>12){$nm=1;$ny++;}
                    ?>
                    <a href="attendance.php?group=<?php echo $active_group; ?>&cal_month=<?php echo $pm; ?>&cal_year=<?php echo $py; ?>" class="cal-nav-btn">← Prev</a>
                    <a href="attendance.php?group=<?php echo $active_group; ?>&cal_month=<?php echo date('m'); ?>&cal_year=<?php echo date('Y'); ?>" class="cal-nav-btn">Today</a>
                    <a href="attendance.php?group=<?php echo $active_group; ?>&cal_month=<?php echo $nm; ?>&cal_year=<?php echo $ny; ?>" class="cal-nav-btn">Next →</a>
                </div>
            </div>

            <div class="cal-grid">
                <?php
                $dow_labels = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                foreach($dow_labels as $lbl) echo '<div class="cal-header">'.$lbl.'</div>';

                // Empty cells before first day
                for($i = 0; $i < $start_dow; $i++) echo '<div class="cal-day empty"></div>';

                $today_day  = (int)date("j");
                $today_mon  = (int)date("m");
                $today_year = (int)date("Y");

                for($d = 1; $d <= $days_in_month; $d++){
                    $stats    = $cal_stats[$d];
                    $is_today = ($d===$today_day && $cal_month===$today_mon && $cal_year===$today_year);
                    $cls = 'cal-day';
                    if($is_today) $cls .= ' today';
                    if($stats['present'] > 0) $cls .= ' has-present';
                    if($stats['absent']  > 0) $cls .= ' has-absent';
                    if($stats['present'] > 0 && $stats['absent'] === 0 && $stats['total'] > 0) $cls .= ' all-present';

                    echo '<div class="'.$cls.'">';
                    echo '<div class="cal-day-num">'.$d.'</div>';
                    echo '<div class="cal-chips">';
                    if($stats['present'] > 0) echo '<span class="cal-chip chip-p">'.$stats['present'].' P</span>';
                    if($stats['absent']  > 0) echo '<span class="cal-chip chip-a">'.$stats['absent'].' A</span>';
                    echo '</div>';
                    echo '</div>';
                }

                // Trailing empty cells
                $total_cells = $start_dow + $days_in_month;
                $trailing = (7 - ($total_cells % 7)) % 7;
                for($i = 0; $i < $trailing; $i++) echo '<div class="cal-day empty"></div>';
                ?>
            </div>

            <div style="display:flex;gap:16px;margin-top:14px;font-size:12px;color:var(--muted);">
                <span style="display:flex;align-items:center;gap:6px;"><span style="width:10px;height:10px;border-radius:3px;background:rgba(34,197,94,.3);display:inline-block;"></span> Has present</span>
                <span style="display:flex;align-items:center;gap:6px;"><span style="width:10px;height:10px;border-radius:3px;background:rgba(244,63,94,.25);display:inline-block;"></span> Has absent</span>
                <span style="display:flex;align-items:center;gap:6px;"><span style="width:10px;height:10px;border-radius:3px;border:1px solid var(--accent);display:inline-block;"></span> Today</span>
            </div>
        </div>

    </div><!-- end card -->

</div><!-- end main -->

<!-- OVERRIDE MODAL -->
<div class="modal" id="overrideModal">
    <div class="modal-box">
        <div class="modal-head">
            <h2>Edit Attendance Record</h2>
            <button class="modal-close" onclick="closeOverride()">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="record_id" id="overrideId">
            <input type="hidden" name="group" value="<?php echo $active_group; ?>">
            <div class="modal-grid">
                <div class="m-field">
                    <label>Status</label>
                    <select class="m-input m-select" name="status" id="overrideStatus">
                        <option value="Present">Present</option>
                        <option value="Absent">Absent</option>
                        <option value="Late">Late</option>
                    </select>
                </div>
                <div class="m-field" style="grid-column:1/-1;"></div>
                <div class="m-field">
                    <label>In Time</label>
                    <input class="m-input" type="time" name="in_time" id="overrideIn">
                </div>
                <div class="m-field">
                    <label>Out Time</label>
                    <input class="m-input" type="time" name="out_time" id="overrideOut">
                </div>
            </div>
            <p style="font-size:11px;color:var(--muted);margin-bottom:14px;">Working hours will be recalculated automatically from In/Out times.</p>
            <button type="submit" name="override_attendance" class="save-btn">Save Changes</button>
        </form>
    </div>
</div>

<script>
function toggleSidebar(){ document.getElementById('sidebar').classList.toggle('on'); document.getElementById('overlay').classList.toggle('on'); }
function confirmLogout(){ if(confirm("Logout?")){ window.location="logout.php"; } }

function switchView(view){
    var tableView = document.getElementById('tableView');
    var calView   = document.getElementById('calView');
    var btnTable  = document.getElementById('btnTable');
    var btnCal    = document.getElementById('btnCal');
    if(view === 'table'){
        tableView.classList.remove('hidden');
        calView.classList.remove('active');
        btnTable.classList.add('active');
        btnCal.classList.remove('active');
    } else {
        tableView.classList.add('hidden');
        calView.classList.add('active');
        btnTable.classList.remove('active');
        btnCal.classList.add('active');
    }
}

function openOverride(id, status, inTime, outTime){
    document.getElementById('overrideId').value     = id;
    document.getElementById('overrideStatus').value = status;
    document.getElementById('overrideIn').value     = inTime;
    document.getElementById('overrideOut').value    = outTime;
    document.getElementById('overrideModal').classList.add('open');
}
function closeOverride(){
    document.getElementById('overrideModal').classList.remove('open');
}
// Close modal on backdrop click
document.getElementById('overrideModal').addEventListener('click', function(e){
    if(e.target === this) closeOverride();
});
</script>
</body>
</html>