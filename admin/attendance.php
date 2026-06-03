<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_role = $_SESSION['role'];

/* MARK ATTENDANCE */
if(isset($_POST['mark_attendance'])){
    $student_id = $_POST['student_id'];
    $status     = $_POST['status'];
    $date       = date("Y-m-d");
    $time       = date("h:i:s");

    $check = $conn->query("
        SELECT * FROM attendance
        WHERE student_id='$student_id' AND date='$date'
    ");

    if($check->num_rows == 0){
        $conn->query("
            INSERT INTO attendance (student_id, status, date, time)
            VALUES ('$student_id','$status','$date','$time')
        ");
        $success = "Attendance marked successfully.";
    } else {
        $error = "Attendance already marked for this student today.";
    }
}

/* FETCH STUDENTS */
$students = $conn->query("SELECT * FROM students ORDER BY name ASC");

/* STATS FOR TODAY */
$today = date("Y-m-d");

$totalStudents  = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
$presentCount   = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE date='$today' AND status='Present'")->fetch_assoc()['c'];
$absentCount    = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE date='$today' AND status='Absent'")->fetch_assoc()['c'];
$lateCount      = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE date='$today' AND status='Late'")->fetch_assoc()['c'];
$unmarkedCount  = $totalStudents - ($presentCount + $absentCount + $lateCount);

/* TODAY ATTENDANCE */
$attendance = $conn->query("
    SELECT attendance.*, students.name, students.department, students.course
    FROM attendance
    LEFT JOIN students ON attendance.student_id = students.student_id
    WHERE attendance.date='$today'
    ORDER BY attendance.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Attendance — Smart Attendance</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>

:root{
    --bg:        #080d18;
    --surface:   #0f1929;
    --surface2:  #162035;
    --border:    rgba(255,255,255,0.07);
    --accent:    #3b6ef8;
    --accent2:   #6ee7f7;
    --green:     #22c55e;
    --red:       #f43f5e;
    --amber:     #f59e0b;
    --text:      #e2e8f0;
    --muted:     #64748b;
    --sidebar-w: 240px;
}

*{ margin:0; padding:0; box-sizing:border-box; }

body{
    font-family:'DM Sans',sans-serif;
    background:var(--bg);
    color:var(--text);
    min-height:100vh;
    overflow-x:hidden;
}

/* ── MOBILE TOPBAR ── */
.mob-bar{
    display:none;
    align-items:center;
    justify-content:space-between;
    padding:14px 18px;
    background:var(--surface);
    border-bottom:1px solid var(--border);
    position:sticky; top:0; z-index:800;
}
.mob-bar .brand{ font-size:15px; font-weight:700; }
.hamburger{
    background:none; border:none; color:var(--text);
    font-size:22px; cursor:pointer; line-height:1;
    padding:4px 6px; border-radius:8px;
    transition:.15s;
}
.hamburger:hover{ background:var(--surface2); }

/* ── OVERLAY ── */
.overlay{
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.6); z-index:900;
}
.overlay.on{ display:block; }

/* ── SIDEBAR ── */
.sidebar{
    width:var(--sidebar-w);
    height:100vh;
    position:fixed; top:0; left:0;
    background:var(--surface);
    border-right:1px solid var(--border);
    padding:24px 16px;
    z-index:1000;
    transition:.25s ease;
    display:flex; flex-direction:column;
    overflow-y:auto;
}

.sidebar .logo{
    font-size:20px; font-weight:700;
    line-height:1.4;
    padding:0 6px;
    margin-bottom:32px;
    letter-spacing:-.3px;
}

.nav-section{
    font-size:10px; font-weight:600;
    color:var(--muted); letter-spacing:1.2px;
    text-transform:uppercase;
    padding:0 8px;
    margin:20px 0 8px;
}

.sidebar a{
    display:flex; align-items:center; gap:10px;
    color:var(--text); text-decoration:none;
    padding:11px 12px;
    border-radius:10px;
    margin-bottom:3px;
    font-size:14px; font-weight:500;
    transition:.15s;
}
.sidebar a:hover{ background:var(--surface2); }
.sidebar a.active{
    background:var(--accent);
    color:#fff;
    font-weight:600;
}
.sidebar .spacer{ flex:1; }
.sidebar .logout{
    color:#f87171;
    margin-top:8px;
}
.sidebar .logout:hover{ background:rgba(244,63,94,.12); }

/* ── MAIN ── */
.main{
    margin-left:var(--sidebar-w);
    padding:36px 40px;
    min-height:100vh;
}

/* ── TOP BAR ── */
.top-bar{
    display:flex; align-items:center;
    justify-content:space-between;
    flex-wrap:wrap; gap:14px;
    margin-bottom:32px;
}
.page-title{
    font-size:28px; font-weight:700;
    letter-spacing:-.5px;
}
.date-pill{
    background:var(--surface2);
    border:1px solid var(--border);
    padding:9px 18px;
    border-radius:50px;
    font-size:13px; font-weight:500;
    color:var(--accent2);
    white-space:nowrap;
}

/* ── ALERT ── */
.alert{
    display:flex; align-items:center; gap:10px;
    padding:14px 18px;
    border-radius:14px;
    margin-bottom:22px;
    font-size:14px; font-weight:500;
    animation:slideIn .3s ease;
}
@keyframes slideIn{
    from{ opacity:0; transform:translateY(-8px); }
    to{   opacity:1; transform:translateY(0);    }
}
.alert-ok { background:rgba(34,197,94,.15);  border:1px solid rgba(34,197,94,.3);  color:#4ade80; }
.alert-err{ background:rgba(244,63,94,.12);  border:1px solid rgba(244,63,94,.25); color:#fb7185; }

/* ── STAT CARDS ── */
.stats-row{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:16px;
    margin-bottom:28px;
}
.stat{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:18px;
    padding:20px 22px;
    position:relative;
    overflow:hidden;
    transition:.2s;
}
.stat:hover{ transform:translateY(-3px); border-color:rgba(255,255,255,.13); }
.stat::before{
    content:'';
    position:absolute; top:0; left:0; right:0; height:3px;
}
.stat.s-total::before  { background:linear-gradient(90deg,var(--accent),var(--accent2)); }
.stat.s-present::before{ background:linear-gradient(90deg,var(--green),#86efac); }
.stat.s-absent::before { background:linear-gradient(90deg,var(--red),#fda4af); }
.stat.s-late::before   { background:linear-gradient(90deg,var(--amber),#fde68a); }
.stat.s-unmarked::before{ background:linear-gradient(90deg,#6366f1,#a78bfa); }

.stat-icon{ font-size:28px; margin-bottom:10px; }
.stat-val{
    font-size:34px; font-weight:700;
    letter-spacing:-1px;
    line-height:1;
    margin-bottom:4px;
}
.stat-label{ font-size:12px; color:var(--muted); font-weight:500; text-transform:uppercase; letter-spacing:.8px; }

/* ── CARDS ── */
.card{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:20px;
    padding:26px;
    margin-bottom:24px;
}
.card-head{
    display:flex; align-items:center; gap:10px;
    margin-bottom:20px;
}
.card-head h2{
    font-size:16px; font-weight:700;
    letter-spacing:-.2px;
}
.card-head .pill{
    background:var(--surface2);
    border:1px solid var(--border);
    padding:3px 10px;
    border-radius:50px;
    font-size:11px; font-weight:600;
    color:var(--muted);
    font-family:'DM Mono',monospace;
}

/* ── FORM ROWS ── */
.form-row{
    display:flex; gap:12px; align-items:center; flex-wrap:wrap;
}
.form-row + .form-row{ margin-top:14px; }

.f-select{
    flex:1; min-width:160px;
    padding:12px 14px;
    background:var(--surface2);
    border:1px solid var(--border);
    border-radius:12px;
    color:var(--text);
    font-family:'DM Sans',sans-serif;
    font-size:14px;
    outline:none;
    cursor:pointer;
    transition:.15s;
    appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2364748b' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat:no-repeat;
    background-position:right 14px center;
    padding-right:36px;
}
.f-select:focus{ border-color:var(--accent); }

.divider{
    border:none;
    border-top:1px solid var(--border);
    margin:22px 0;
}

/* ── BUTTONS ── */
.btn{
    padding:12px 22px;
    border:none; border-radius:12px;
    font-family:'DM Sans',sans-serif;
    font-size:14px; font-weight:600;
    cursor:pointer;
    transition:.2s;
    white-space:nowrap;
    display:inline-flex; align-items:center; gap:7px;
}
.btn:hover{ transform:translateY(-2px); }
.btn:active{ transform:translateY(0); }

.btn-primary{
    background:linear-gradient(135deg,var(--accent),#5b8af9);
    color:#fff;
    box-shadow:0 4px 18px rgba(59,110,248,.35);
}
.btn-green{
    background:linear-gradient(135deg,#16a34a,var(--green));
    color:#fff;
    box-shadow:0 4px 18px rgba(34,197,94,.25);
}
.btn-full{ width:100%; justify-content:center; }

/* ── TABLE ── */
.table-wrap{
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
    margin-top:4px;
}
table{
    width:100%; min-width:540px;
    border-collapse:collapse;
}
thead th{
    padding:11px 14px;
    font-size:11px; font-weight:700;
    color:var(--muted);
    text-transform:uppercase;
    letter-spacing:.8px;
    text-align:left;
    white-space:nowrap;
    border-bottom:1px solid var(--border);
}
tbody td{
    padding:14px;
    font-size:14px;
    border-bottom:1px solid rgba(255,255,255,.03);
}
tbody tr:last-child td{ border-bottom:none; }
tbody tr:hover td{ background:rgba(255,255,255,.025); }

.student-cell{ display:flex; flex-direction:column; gap:2px; }
.student-name{ font-weight:600; font-size:14px; }
.student-meta{ font-size:11px; color:var(--muted); }

/* ── BADGE ── */
.badge{
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 13px;
    border-radius:50px;
    font-size:12px; font-weight:700;
    letter-spacing:.3px;
}
.b-present{ background:rgba(34,197,94,.15);  color:#4ade80; border:1px solid rgba(34,197,94,.25); }
.b-absent{  background:rgba(244,63,94,.12);  color:#fb7185; border:1px solid rgba(244,63,94,.2); }
.b-late{    background:rgba(245,158,11,.12); color:#fbbf24; border:1px solid rgba(245,158,11,.2); }

.time-cell{
    font-family:'DM Mono',monospace;
    font-size:13px;
    color:var(--muted);
}

/* ── EMPTY ── */
.empty{
    text-align:center;
    padding:50px 20px;
}
.empty-icon{ font-size:44px; margin-bottom:12px; opacity:.5; }
.empty p{ color:var(--muted); font-size:14px; }

/* ── MOBILE ── */
@media(max-width:768px){
    .mob-bar{ display:flex; }
    .sidebar{ left:-280px; }
    .sidebar.on{ left:0; }
    .main{ margin-left:0; padding:16px; }

    .stats-row{
        grid-template-columns:1fr 1fr;
        gap:12px;
    }
    .stat{ padding:16px; }
    .stat-val{ font-size:26px; }

    .page-title{ font-size:22px; }

    .form-row{ flex-direction:column; }
    .f-select{ min-width:unset; width:100%; }
    .btn{ width:100%; justify-content:center; }

    thead th,tbody td{ padding:10px 10px; font-size:13px; }
}

@media(max-width:420px){
    .stats-row{ grid-template-columns:1fr 1fr; }
    .stat-icon{ font-size:22px; }
    .stat-val{ font-size:22px; }
    .stat-label{ font-size:10px; }
}

</style>
</head>
<body>

<!-- MOBILE BAR -->
<div class="mob-bar">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <div class="brand">📘 Smart Attendance</div>
    <div class="date-pill" style="font-size:11px;padding:6px 12px;">
        <?php echo date("d M"); ?>
    </div>
</div>

<!-- OVERLAY -->
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="logo">📘 Smart<br>Attendance</div>

    <div class="nav-section">Menu</div>
    <a href="admin_dashboard.php">🏠 Dashboard</a>
    <a href="add_member.php">➕ Add Student</a>
    <a href="manage_members.php">👨‍🎓 Manage Students</a>
    <a href="attendance.php" class="active">🗓️ Attendance</a>
    <?php if($admin_role == "superadmin"){ ?>
    <a href="admin_management.php">👮 Admin Management</a>
    <?php } ?>

    <div class="spacer"></div>
    <div class="nav-section">Account</div>
    <a href="javascript:void(0);" onclick="confirmLogout()" class="logout">🚪 Logout</a>
</div>

<!-- MAIN -->
<div class="main">

    <!-- TOP BAR -->
    <div class="top-bar">
        <div class="page-title">Attendance</div>
        <div class="date-pill">📅 <?php echo date("l, d M Y"); ?></div>
    </div>

    <!-- ALERTS -->
    <?php if(isset($success)){ ?>
    <div class="alert alert-ok">✅ <?php echo $success; ?></div>
    <?php } ?>
    <?php if(isset($error)){ ?>
    <div class="alert alert-err">⚠️ <?php echo $error; ?></div>
    <?php } ?>

    <!-- STAT CARDS -->
    <div class="stats-row">
        <div class="stat s-total">
            <div class="stat-icon">👥</div>
            <div class="stat-val"><?php echo $totalStudents; ?></div>
            <div class="stat-label">Total Students</div>
        </div>
        <div class="stat s-present">
            <div class="stat-icon">✅</div>
            <div class="stat-val"><?php echo $presentCount; ?></div>
            <div class="stat-label">Present</div>
        </div>
        <div class="stat s-absent">
            <div class="stat-icon">❌</div>
            <div class="stat-val"><?php echo $absentCount; ?></div>
            <div class="stat-label">Absent</div>
        </div>
        <div class="stat s-late">
            <div class="stat-icon">⏰</div>
            <div class="stat-val"><?php echo $lateCount; ?></div>
            <div class="stat-label">Late</div>
        </div>
    </div>

    <!-- ACTIONS CARD -->
    <div class="card">

        <!-- EXPORT -->
        <div class="card-head">
            <span>📥</span>
            <h2>Export Attendance</h2>
        </div>
        <form method="GET" action="export_excel.php">
            <div class="form-row">
                <select class="f-select" name="course" required>
                    <option value="">Select Course</option>
                    <option value="IOT">IOT</option>
                    <option value="AI">AI</option>
                </select>
                <button type="submit" class="btn btn-green">📊 Export Excel</button>
            </div>
        </form>

        <hr class="divider">

        <!-- MARK -->
        <div class="card-head">
            <span>✏️</span>
            <h2>Mark Attendance</h2>
            <span class="pill"><?php echo date("d M Y"); ?></span>
        </div>
        <form method="POST">
            <div class="form-row">
                <select class="f-select" name="student_id" required>
                    <option value="">Select Student</option>
                    <?php while($row = $students->fetch_assoc()){ ?>
                    <option value="<?php echo $row['student_id']; ?>">
                        <?php echo htmlspecialchars($row['name']); ?> · <?php echo $row['student_id']; ?>
                    </option>
                    <?php } ?>
                </select>
                <select class="f-select" name="status" required style="flex:0 0 180px;">
                    <option value="">Status</option>
                    <option value="Present">✅ Present</option>
                    <option value="Absent">❌ Absent</option>
                    <option value="Late">⏰ Late</option>
                </select>
                <button type="submit" name="mark_attendance" class="btn btn-primary">Mark</button>
            </div>
        </form>

    </div>

    <!-- TODAY TABLE -->
    <div class="card">
        <div class="card-head">
            <span>📋</span>
            <h2>Today's Attendance</h2>
            <span class="pill"><?php echo $presentCount + $absentCount + $lateCount; ?> / <?php echo $totalStudents; ?></span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Status</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $count = 1; $hasRows = false;
                while($att = $attendance->fetch_assoc()){
                    $hasRows = true;
                    $s = $att['status'];
                    $bClass = $s=='Present' ? 'b-present' : ($s=='Late' ? 'b-late' : 'b-absent');
                    $dot    = $s=='Present' ? '●' : ($s=='Late' ? '●' : '●');
                ?>
                <tr>
                    <td style="color:var(--muted);font-family:'DM Mono',monospace;font-size:12px;">
                        <?php echo str_pad($count++, 2, '0', STR_PAD_LEFT); ?>
                    </td>
                    <td>
                        <div class="student-cell">
                            <span class="student-name"><?php echo htmlspecialchars($att['name']); ?></span>
                            <span class="student-meta">
                                <?php echo htmlspecialchars($att['department'] ?? ''); ?>
                                <?php if(!empty($att['course'])){ echo ' · ' . htmlspecialchars($att['course']); } ?>
                            </span>
                        </div>
                    </td>
                    <td><span class="badge <?php echo $bClass; ?>"><?php echo $s; ?></span></td>
                    <td class="time-cell"><?php echo $att['time']; ?></td>
                </tr>
                <?php } ?>
                <?php if(!$hasRows){ ?>
                <tr>
                    <td colspan="4">
                        <div class="empty">
                            <div class="empty-icon">📭</div>
                            <p>No attendance recorded today yet.</p>
                        </div>
                    </td>
                </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('on');
    document.getElementById('overlay').classList.toggle('on');
}
function confirmLogout(){
    if(confirm("Are you sure you want to logout?")){
        window.location = "logout.php";
    }
}
</script>
</body>
</html>