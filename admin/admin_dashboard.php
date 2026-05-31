<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$admin_role = $_SESSION['role'];

date_default_timezone_set("Asia/Kolkata");

$today = date("Y-m-d");

/* TOTAL */
$total_students = $conn->query("SELECT * FROM students")->num_rows;

/* PRESENT */
$present_today = $conn->query("
    SELECT * FROM attendance
    WHERE date='$today' AND status='Present'
")->num_rows;

/* LATE */
$late_today = $conn->query("
    SELECT * FROM attendance
    WHERE date='$today' AND status='Late'
")->num_rows;

/* ABSENT */
$absent_today = $total_students - $present_today - $late_today;
if($absent_today < 0) $absent_today = 0;

/* RATE */
$attendance_percentage = ($total_students > 0)
    ? round(($present_today / $total_students) * 100)
    : 0;

/* RECENT ATTENDANCE */
$recent = $conn->query("
    SELECT attendance.student_id, attendance.status, attendance.date, attendance.time, students.name, students.department
    FROM attendance
    LEFT JOIN students ON attendance.student_id = students.student_id
    ORDER BY attendance.id DESC
    LIMIT 8
");

/* WEEK DATA */
$week_labels = [];
$week_present = [];
$week_absent  = [];

for($i = 6; $i >= 0; $i--){
    $d     = date("Y-m-d", strtotime("-$i days"));
    $label = date("D", strtotime("-$i days"));

    $p = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE date='$d' AND status='Present'")->fetch_assoc()['c'];
    $a = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE date='$d' AND status='Absent'")->fetch_assoc()['c'];

    $week_labels[]  = $label;
    $week_present[] = (int)$p;
    $week_absent[]  = (int)$a;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Dashboard — Smart Attendance</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    --purple:    #a78bfa;
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
    font-size:22px; cursor:pointer;
    padding:4px 6px; border-radius:8px; transition:.15s;
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
    line-height:1.4; padding:0 6px;
    margin-bottom:32px; letter-spacing:-.3px;
}
.nav-section{
    font-size:10px; font-weight:600;
    color:var(--muted); letter-spacing:1.2px;
    text-transform:uppercase;
    padding:0 8px; margin:20px 0 8px;
}
.sidebar a{
    display:flex; align-items:center; gap:10px;
    color:var(--text); text-decoration:none;
    padding:11px 12px; border-radius:10px;
    margin-bottom:3px;
    font-size:14px; font-weight:500;
    transition:.15s;
}
.sidebar a:hover{ background:var(--surface2); }
.sidebar a.active{ background:var(--accent); color:#fff; font-weight:600; }
.sidebar .spacer{ flex:1; }
.sidebar .logout{ color:#f87171; }
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
.page-title{ font-size:28px; font-weight:700; letter-spacing:-.5px; }
.date-pill{
    background:var(--surface2);
    border:1px solid var(--border);
    padding:9px 18px; border-radius:50px;
    font-size:13px; font-weight:500;
    color:var(--accent2); white-space:nowrap;
}

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
    padding:22px 24px;
    position:relative; overflow:hidden;
    transition:.2s;
}
.stat:hover{ transform:translateY(-3px); border-color:rgba(255,255,255,.13); }
.stat::before{
    content:''; position:absolute;
    top:0; left:0; right:0; height:3px;
}
.stat.s-present::before { background:linear-gradient(90deg,#16a34a,var(--green)); }
.stat.s-absent::before  { background:linear-gradient(90deg,#be123c,var(--red)); }
.stat.s-total::before   { background:linear-gradient(90deg,var(--accent),var(--accent2)); }
.stat.s-rate::before    { background:linear-gradient(90deg,var(--purple),#c4b5fd); }

.stat-val{
    font-size:38px; font-weight:700;
    letter-spacing:-1.5px; line-height:1;
    margin-bottom:6px;
}
.stat-label{
    font-size:11px; color:var(--muted);
    font-weight:600; text-transform:uppercase; letter-spacing:.8px;
}
.stat-sub{
    font-size:11px; color:var(--muted);
    margin-top:8px;
}
.stat-bar{
    margin-top:14px;
    height:4px;
    background:rgba(255,255,255,.07);
    border-radius:99px;
    overflow:hidden;
}
.stat-bar-fill{
    height:100%;
    border-radius:99px;
    background:linear-gradient(90deg,var(--green),#86efac);
    transition:width .6s ease;
}

/* ── CHARTS ROW ── */
.charts-row{
    display:grid;
    grid-template-columns:2fr 1fr;
    gap:20px;
    margin-bottom:24px;
}
.card{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:20px;
    padding:26px;
}
.card-head{
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:22px;
}
.card-head h2{
    font-size:14px; font-weight:700;
    text-transform:uppercase; letter-spacing:.5px;
    color:var(--muted);
}
.chart-wrap{ position:relative; }
.chart-wrap canvas{ max-height:240px; }
.donut-wrap{
    position:relative;
    display:flex; align-items:center; justify-content:center;
}
.donut-wrap canvas{ max-height:200px; }

/* chart legend */
.chart-legend{
    display:flex; gap:16px;
    margin-top:16px;
}
.legend-item{
    display:flex; align-items:center; gap:7px;
    font-size:12px; color:var(--muted); font-weight:500;
}
.legend-dot{
    width:8px; height:8px;
    border-radius:50%;
}

/* donut stats */
.donut-stats{
    display:flex; flex-direction:column; gap:10px;
    margin-top:18px;
}
.donut-row{
    display:flex; justify-content:space-between; align-items:center;
    font-size:13px;
}
.donut-label{ color:var(--muted); font-weight:500; }
.donut-val{ font-weight:700; font-family:'DM Mono',monospace; }

/* ── TABLE CARD ── */
.table-card{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:20px;
    padding:26px;
}
.table-wrap{
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
    margin-top:4px;
}
table{
    width:100%; min-width:520px;
    border-collapse:collapse;
}
thead th{
    padding:11px 14px;
    font-size:11px; font-weight:700;
    color:var(--muted);
    text-transform:uppercase; letter-spacing:.8px;
    text-align:left; white-space:nowrap;
    border-bottom:1px solid var(--border);
}
tbody td{
    padding:14px;
    font-size:14px;
    border-bottom:1px solid rgba(255,255,255,.03);
    vertical-align:middle;
}
tbody tr:last-child td{ border-bottom:none; }
tbody tr:hover td{ background:rgba(255,255,255,.025); }

.student-cell{ display:flex; flex-direction:column; gap:2px; }
.student-name{ font-weight:600; font-size:14px; }
.student-meta{ font-size:11px; color:var(--muted); }

.id-cell{
    font-family:'DM Mono',monospace;
    font-size:12px; color:var(--muted);
}
.time-cell{
    font-family:'DM Mono',monospace;
    font-size:12px; color:var(--muted);
}

.badge{
    display:inline-flex; align-items:center;
    padding:5px 12px; border-radius:50px;
    font-size:11px; font-weight:700; letter-spacing:.3px;
    text-transform:uppercase;
}
.b-present{ background:rgba(34,197,94,.12);  color:#4ade80; border:1px solid rgba(34,197,94,.22); }
.b-absent{  background:rgba(244,63,94,.10);  color:#fb7185; border:1px solid rgba(244,63,94,.18); }
.b-late{    background:rgba(245,158,11,.10); color:#fbbf24; border:1px solid rgba(245,158,11,.18); }

/* ── MOBILE ── */
@media(max-width:768px){
    .mob-bar{ display:flex; }
    .sidebar{ left:-280px; }
    .sidebar.on{ left:0; }
    .main{ margin-left:0; padding:16px; }

    .page-title{ font-size:22px; }

    .stats-row{
        grid-template-columns:1fr 1fr;
        gap:12px;
    }
    .stat{ padding:16px 18px; }
    .stat-val{ font-size:28px; }

    .charts-row{
        grid-template-columns:1fr;
    }

    thead th, tbody td{ padding:10px; font-size:13px; }
    .student-meta{ display:none; }
}

@media(max-width:420px){
    .stats-row{ grid-template-columns:1fr 1fr; }
    .stat-val{ font-size:24px; }
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
    <a href="admin_dashboard.php" class="active">🏠 Dashboard</a>
    <a href="add_student.php">➕ Add Student</a>
    <a href="manage_students.php">👨‍🎓 Manage Students</a>
    <a href="attendance.php">🗓️ Attendance</a>
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
        <div class="page-title">Dashboard</div>
        <div class="date-pill">📅 <?php echo date("l, d M Y"); ?></div>
    </div>

    <!-- STAT CARDS -->
    <div class="stats-row">

        <div class="stat s-present">
            <div class="stat-val"><?php echo $present_today; ?></div>
            <div class="stat-label">Present Today</div>
            <div class="stat-bar">
                <div class="stat-bar-fill" style="width:<?php echo $attendance_percentage; ?>%;background:linear-gradient(90deg,#16a34a,#22c55e);"></div>
            </div>
        </div>

        <div class="stat s-absent">
            <div class="stat-val"><?php echo $absent_today; ?></div>
            <div class="stat-label">Absent Today</div>
            <?php $absent_pct = $total_students > 0 ? round(($absent_today / $total_students)*100) : 0; ?>
            <div class="stat-bar">
                <div class="stat-bar-fill" style="width:<?php echo $absent_pct; ?>%;background:linear-gradient(90deg,#be123c,#f43f5e);"></div>
            </div>
        </div>

        <div class="stat s-total">
            <div class="stat-val"><?php echo $total_students; ?></div>
            <div class="stat-label">Total Students</div>
            <div class="stat-sub">Enrolled</div>
        </div>

        <div class="stat s-rate">
            <div class="stat-val"><?php echo $attendance_percentage; ?>%</div>
            <div class="stat-label">Attendance Rate</div>
            <div class="stat-bar">
                <div class="stat-bar-fill" style="width:<?php echo $attendance_percentage; ?>%;background:linear-gradient(90deg,var(--purple),#c4b5fd);"></div>
            </div>
        </div>

    </div>

    <!-- CHARTS -->
    <div class="charts-row">

        <!-- BAR CHART -->
        <div class="card">
            <div class="card-head">
                <h2>Weekly Overview</h2>
            </div>
            <div class="chart-wrap">
                <canvas id="barChart"></canvas>
            </div>
            <div class="chart-legend">
                <div class="legend-item">
                    <div class="legend-dot" style="background:#4ade80;"></div>
                    Present
                </div>
                <div class="legend-item">
                    <div class="legend-dot" style="background:#fb7185;"></div>
                    Absent
                </div>
            </div>
        </div>

        <!-- DONUT CHART -->
        <div class="card">
            <div class="card-head">
                <h2>Today's Split</h2>
            </div>
            <div class="donut-wrap">
                <canvas id="pieChart"></canvas>
            </div>
            <div class="donut-stats">
                <div class="donut-row">
                    <span class="donut-label">Present</span>
                    <span class="donut-val" style="color:#4ade80;"><?php echo $present_today; ?></span>
                </div>
                <div class="donut-row">
                    <span class="donut-label">Absent</span>
                    <span class="donut-val" style="color:#fb7185;"><?php echo $absent_today; ?></span>
                </div>
                <div class="donut-row">
                    <span class="donut-label">Total</span>
                    <span class="donut-val"><?php echo $total_students; ?></span>
                </div>
            </div>
        </div>

    </div>

    <!-- RECENT ATTENDANCE TABLE -->
    <div class="table-card">
        <div class="card-head">
            <h2>Recent Attendance</h2>
            <a href="attendance.php" style="font-size:13px;color:var(--accent);text-decoration:none;font-weight:600;">View all</a>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $recent->fetch_assoc()):
                    $s = $row['status'];
                    $bClass = $s=='Present' ? 'b-present' : ($s=='Late' ? 'b-late' : 'b-absent');
                ?>
                <tr>
                    <td class="id-cell"><?php echo htmlspecialchars($row['student_id']); ?></td>
                    <td>
                        <div class="student-cell">
                            <span class="student-name"><?php echo htmlspecialchars($row['name']); ?></span>
                            <span class="student-meta"><?php echo htmlspecialchars($row['department'] ?? ''); ?></span>
                        </div>
                    </td>
                    <td><span class="badge <?php echo $bClass; ?>"><?php echo $s; ?></span></td>
                    <td style="font-size:13px;color:var(--muted);"><?php echo $row['date']; ?></td>
                    <td class="time-cell"><?php echo $row['time']; ?></td>
                </tr>
                <?php endwhile; ?>
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

// Shared chart defaults
Chart.defaults.color = '#64748b';
Chart.defaults.font.family = "'DM Sans', sans-serif";

// Bar / Line chart
new Chart(document.getElementById("barChart"), {
    type: "bar",
    data: {
        labels: <?php echo json_encode($week_labels); ?>,
        datasets: [
            {
                label: "Present",
                data: <?php echo json_encode($week_present); ?>,
                backgroundColor: "rgba(34,197,94,.25)",
                borderColor: "#22c55e",
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            },
            {
                label: "Absent",
                data: <?php echo json_encode($week_absent); ?>,
                backgroundColor: "rgba(244,63,94,.18)",
                borderColor: "#f43f5e",
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0f1929',
                borderColor: 'rgba(255,255,255,.08)',
                borderWidth: 1,
                padding: 12,
                titleFont: { weight: '700' }
            }
        },
        scales: {
            x: {
                grid: { color: 'rgba(255,255,255,.04)' },
                border: { display: false }
            },
            y: {
                grid: { color: 'rgba(255,255,255,.04)' },
                border: { display: false },
                ticks: { precision: 0 }
            }
        }
    }
});

// Donut chart
new Chart(document.getElementById("pieChart"), {
    type: "doughnut",
    data: {
        labels: ["Present", "Absent"],
        datasets: [{
            data: [<?php echo $present_today; ?>, <?php echo $absent_today; ?>],
            backgroundColor: ["rgba(34,197,94,.8)", "rgba(244,63,94,.7)"],
            borderColor: ["#22c55e", "#f43f5e"],
            borderWidth: 2,
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        cutout: "72%",
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0f1929',
                borderColor: 'rgba(255,255,255,.08)',
                borderWidth: 1,
                padding: 12
            }
        }
    }
});
</script>

</body>
</html>