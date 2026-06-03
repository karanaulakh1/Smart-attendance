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

/* MARK ATTENDANCE */
if(isset($_POST['mark_attendance'])){
    $student_id = $_POST['student_id'];
    $status     = $_POST['status'];
    $date       = date("Y-m-d");
    $time       = date("h:i:s");

    $check = $conn->query("SELECT * FROM `$att_table` WHERE student_id='$student_id' AND date='$date'");
    if($check->num_rows == 0){
        $conn->query("INSERT INTO `$att_table` (student_id, status, date, time) VALUES ('$student_id','$status','$date','$time')");
        $success = "Attendance marked successfully.";
    } else {
        $error = "Attendance already marked for this member today.";
    }
}

/* STATS */
$today          = date("Y-m-d");
$totalMembers   = $conn->query("SELECT COUNT(*) as c FROM `$member_table`")->fetch_assoc()['c'];
$presentCount   = $conn->query("SELECT COUNT(*) as c FROM `$att_table` WHERE date='$today' AND status='Present'")->fetch_assoc()['c'];
$absentCount    = $conn->query("SELECT COUNT(*) as c FROM `$att_table` WHERE date='$today' AND status='Absent'")->fetch_assoc()['c'];
$lateCount      = $conn->query("SELECT COUNT(*) as c FROM `$att_table` WHERE date='$today' AND status='Late'")->fetch_assoc()['c'];

/* FETCH MEMBERS */
$members_list = $conn->query("SELECT * FROM `$member_table` ORDER BY name ASC");

/* TODAY ATTENDANCE */
$attendance = $conn->query("
    SELECT a.*, m.name, m.department, m.course
    FROM `$att_table` a
    LEFT JOIN `$member_table` m ON a.student_id = m.student_id
    WHERE a.date='$today'
    ORDER BY a.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Attendance — Smart Attendance</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{ --bg:#080d18; --surface:#0f1929; --surface2:#162035; --border:rgba(255,255,255,0.07); --accent:#3b6ef8; --accent2:#6ee7f7; --green:#22c55e; --red:#f43f5e; --amber:#f59e0b; --text:#e2e8f0; --muted:#64748b; --sidebar-w:240px; }
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
.gtab{ padding:8px 18px; border-radius:10px; border:1px solid var(--border); background:var(--surface); color:var(--muted); font-size:13px; font-weight:600; cursor:pointer; transition:.15s; text-decoration:none; }
.gtab:hover{ color:var(--text); background:var(--surface2); }
.gtab.active{ background:var(--accent); border-color:var(--accent); color:#fff; }
.alert{ display:flex; align-items:center; gap:10px; padding:14px 18px; border-radius:14px; margin-bottom:22px; font-size:14px; font-weight:500; animation:slideIn .3s ease; }
@keyframes slideIn{ from{ opacity:0; transform:translateY(-8px); } to{ opacity:1; transform:translateY(0); } }
.alert-ok { background:rgba(34,197,94,.10); border:1px solid rgba(34,197,94,.22); color:#4ade80; }
.alert-err{ background:rgba(244,63,94,.10); border:1px solid rgba(244,63,94,.2);  color:#fb7185; }
/* STATS */
.stats-row{ display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:28px; }
.stat{ background:var(--surface); border:1px solid var(--border); border-radius:18px; padding:22px 24px; position:relative; overflow:hidden; transition:.2s; }
.stat:hover{ transform:translateY(-3px); }
.stat::before{ content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.stat.s-present::before{ background:linear-gradient(90deg,#16a34a,var(--green)); }
.stat.s-absent::before { background:linear-gradient(90deg,#be123c,var(--red)); }
.stat.s-total::before  { background:linear-gradient(90deg,var(--accent),var(--accent2)); }
.stat.s-late::before   { background:linear-gradient(90deg,#d97706,var(--amber)); }
.stat-val{ font-size:36px; font-weight:700; letter-spacing:-1.5px; line-height:1; margin-bottom:6px; }
.stat-label{ font-size:11px; color:var(--muted); font-weight:600; text-transform:uppercase; letter-spacing:.8px; }
/* CARD */
.card{ background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:26px; margin-bottom:24px; }
.card-head{ display:flex; align-items:center; gap:10px; margin-bottom:20px; }
.card-head h2{ font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); }
.count-pill{ margin-left:auto; background:var(--surface2); border:1px solid var(--border); padding:3px 10px; border-radius:50px; font-size:11px; font-weight:600; color:var(--muted); font-family:'DM Mono',monospace; }
/* FORMS */
.form-row{ display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
.divider-line{ border:none; border-top:1px solid var(--border); margin:20px 0; }
.f-select{ flex:1; min-width:160px; padding:12px 14px; background:var(--surface2); border:1px solid var(--border); border-radius:12px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:14px; outline:none; transition:.15s; appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2364748b' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 14px center; background-color:var(--surface2); padding-right:36px; cursor:pointer; }
.f-select:focus{ border-color:var(--accent); }
.btn{ padding:12px 22px; border:none; border-radius:12px; font-family:'DM Sans',sans-serif; font-size:14px; font-weight:600; cursor:pointer; transition:.2s; white-space:nowrap; display:inline-flex; align-items:center; gap:7px; }
.btn:hover{ transform:translateY(-2px); }
.btn-primary{ background:linear-gradient(135deg,var(--accent),#5b8af9); color:#fff; box-shadow:0 4px 18px rgba(59,110,248,.35); }
.btn-green{ background:linear-gradient(135deg,#16a34a,var(--green)); color:#fff; }
.btn-full{ width:100%; justify-content:center; }
/* TABLE */
.table-wrap{ overflow-x:auto; -webkit-overflow-scrolling:touch; }
table{ width:100%; min-width:540px; border-collapse:collapse; }
thead th{ padding:11px 14px; font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.8px; text-align:left; white-space:nowrap; border-bottom:1px solid var(--border); }
tbody td{ padding:14px; font-size:14px; border-bottom:1px solid rgba(255,255,255,.03); vertical-align:middle; }
tbody tr:last-child td{ border-bottom:none; }
tbody tr:hover td{ background:rgba(255,255,255,.025); }
.student-cell{ display:flex; flex-direction:column; gap:2px; }
.student-name{ font-weight:600; }
.student-meta{ font-size:11px; color:var(--muted); }
.badge{ display:inline-flex; align-items:center; padding:5px 12px; border-radius:50px; font-size:11px; font-weight:700; letter-spacing:.3px; text-transform:uppercase; }
.b-present{ background:rgba(34,197,94,.12); color:#4ade80; border:1px solid rgba(34,197,94,.22); }
.b-absent{ background:rgba(244,63,94,.10); color:#fb7185; border:1px solid rgba(244,63,94,.18); }
.b-late{ background:rgba(245,158,11,.10); color:#fbbf24; border:1px solid rgba(245,158,11,.18); }
.empty{ text-align:center; padding:50px 20px; }
.empty p{ color:var(--muted); font-size:14px; }
@media(max-width:768px){ .mob-bar{ display:flex; } .sidebar{ left:-280px; } .sidebar.on{ left:0; } .main{ margin-left:0; padding:16px; } .stats-row{ grid-template-columns:1fr 1fr; gap:12px; } .stat{ padding:16px; } .stat-val{ font-size:28px; } .page-title{ font-size:22px; } .form-row{ flex-direction:column; } .f-select{ min-width:unset; width:100%; } .btn{ width:100%; justify-content:center; } thead th, tbody td{ padding:10px; font-size:13px; } }
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
    <a href="manage_memberss.php">👥 Manage Members</a>
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
        <?php foreach($groups as $g){ $ac=$g['table_name']===$active_group?'active':''; echo '<a href="attendance.php?group='.$g['table_name'].'" class="gtab '.$ac.'">'.htmlspecialchars($g['group_name']).'</a>'; } ?>
    </div>

    <?php if($success){ echo '<div class="alert alert-ok">&#10003; '.$success.'</div>'; } ?>
    <?php if($error){   echo '<div class="alert alert-err">&#9888; '.$error.'</div>'; } ?>

    <!-- STATS -->
    <div class="stats-row">
        <div class="stat s-present"><div class="stat-val"><?php echo $presentCount; ?></div><div class="stat-label">Present</div></div>
        <div class="stat s-absent"><div class="stat-val"><?php echo $absentCount; ?></div><div class="stat-label">Absent</div></div>
        <div class="stat s-late"><div class="stat-val"><?php echo $lateCount; ?></div><div class="stat-label">Late</div></div>
        <div class="stat s-total"><div class="stat-val"><?php echo $totalMembers; ?></div><div class="stat-label">Total</div></div>
    </div>

    <!-- ACTIONS CARD -->
    <div class="card">
        <!-- EXPORT -->
        <div class="card-head"><h2>Export Attendance</h2></div>
        <form method="GET" action="export_excel.php">
            <input type="hidden" name="group" value="<?php echo $active_group; ?>">
            <input type="hidden" name="att_table" value="<?php echo $att_table; ?>">
            <div class="form-row">
                <select class="f-select" name="course">
                    <option value="">All Courses / Departments</option>
                    <?php
                    $depts = $conn->query("SELECT DISTINCT course FROM `$member_table` WHERE course != '' ORDER BY course");
                    while($d=$depts->fetch_assoc()) echo '<option value="'.htmlspecialchars($d['course']).'">'.htmlspecialchars($d['course']).'</option>';
                    ?>
                </select>
                <button type="submit" class="btn btn-green">Export Excel</button>
            </div>
        </form>

        <hr class="divider-line">

        <!-- MARK -->
        <div class="card-head"><h2>Mark Attendance</h2><span class="count-pill"><?php echo $active_info['group_name']; ?></span></div>
        <form method="POST">
            <input type="hidden" name="group" value="<?php echo $active_group; ?>">
            <div class="form-row">
                <select class="f-select" name="student_id" required>
                    <option value="">Select Member</option>
                    <?php while($row=$members_list->fetch_assoc()){ echo '<option value="'.htmlspecialchars($row['student_id']).'">'.htmlspecialchars($row['name']).' &middot; '.$row['student_id'].'</option>'; } ?>
                </select>
                <select class="f-select" name="status" required style="flex:0 0 180px;">
                    <option value="">Status</option>
                    <option value="Present">Present</option>
                    <option value="Absent">Absent</option>
                    <option value="Late">Late</option>
                </select>
                <button type="submit" name="mark_attendance" class="btn btn-primary">Mark</button>
            </div>
        </form>
    </div>

    <!-- TABLE -->
    <div class="card">
        <div class="card-head">
            <h2>Today's Attendance — <?php echo htmlspecialchars($active_info['group_name']); ?></h2>
            <span class="count-pill"><?php echo $presentCount+$absentCount+$lateCount; ?> / <?php echo $totalMembers; ?></span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Member</th><th>Status</th><th>Time</th></tr></thead>
                <tbody>
                <?php
                $count=1; $hasRows=false;
                while($att=$attendance->fetch_assoc()):
                    $hasRows=true;
                    $s=$att['status'];
                    $bClass=$s=='Present'?'b-present':($s=='Late'?'b-late':'b-absent');
                ?>
                <tr>
                    <td style="color:var(--muted);font-family:'DM Mono',monospace;font-size:12px;"><?php echo str_pad($count++,2,'0',STR_PAD_LEFT); ?></td>
                    <td>
                        <div class="student-cell">
                            <span class="student-name"><?php echo htmlspecialchars($att['name']); ?></span>
                            <span class="student-meta"><?php echo htmlspecialchars($att['department']??''); ?><?php if(!empty($att['course'])) echo ' · '.htmlspecialchars($att['course']); ?></span>
                        </div>
                    </td>
                    <td><span class="badge <?php echo $bClass; ?>"><?php echo $s; ?></span></td>
                    <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--muted);"><?php echo $att['time']; ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if(!$hasRows): ?><tr><td colspan="4"><div class="empty"><p>No attendance recorded today yet.</p></div></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleSidebar(){ document.getElementById('sidebar').classList.toggle('on'); document.getElementById('overlay').classList.toggle('on'); }
function confirmLogout(){ if(confirm("Logout?")){ window.location="logout.php"; } }
</script>
</body>
</html>