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

/* ABSENT */
$absent_today = $total_students - $present_today;

/* RATE */
$attendance_percentage = ($total_students > 0)
    ? round(($present_today / $total_students) * 100)
    : 0;

/* RECENT ATTENDANCE */
$recent = $conn->query("
SELECT attendance.student_id, attendance.status, attendance.date, attendance.time, students.name
FROM attendance
LEFT JOIN students
ON attendance.student_id = students.student_id
ORDER BY attendance.id DESC
LIMIT 8
");

/* WEEK DATA */
$week_data = [];

for($i=6;$i>=0;$i--){
    $date = date("Y-m-d", strtotime("-$i days"));

    $count = $conn->query("
    SELECT * FROM attendance
    WHERE date='$date' AND status='Present'
    ")->num_rows;

    $week_data[] = $count;
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Dashboard</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

/* GLOBAL */
*{box-sizing:border-box;}

body{
    margin:0;
    font-family:Poppins;
    background:#0f172a;
    color:white;
    overflow-x:hidden;
}

/* TOPBAR */
.topbar-mobile{
    display:none;
    justify-content:space-between;
    align-items:center;
    padding:15px 20px;
    background:#0f172a;
    position:sticky;
    top:0;
    z-index:5000;
}

.hamburger{
    font-size:26px;
    background:none;
    border:none;
    color:white;
}

/* SIDEBAR DESKTOP */
.sidebar{
    width:260px;
    height:100vh;
    position:fixed;
    top:0;
    left:0;
    background:#1e293b;
    padding:25px;
    z-index:1000;
}

.sidebar .logo{
    font-size:28px;
    font-weight:700;
    margin-bottom:40px;
}

.sidebar a{
    display:block;
    color:white;
    text-decoration:none;
    padding:14px;
    border-radius:10px;
    margin-bottom:8px;
}

.sidebar a:hover{
    background:#2563eb;
}

/* MAIN */
.main{
    margin-left:260px;
    padding:30px;
}

/* TOP BAR */
.top-bar{
    display:flex;
    justify-content:space-between;
    margin-bottom:25px;
}

/* STATS */
.stats{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:20px;
}

.stat-card{
    padding:22px;
    border-radius:18px;
}

.present-card{
    background:linear-gradient(135deg,#16a34a,#22c55e);
}

.absent-card{
    background:linear-gradient(135deg,#dc2626,#ef4444);
}

.total-card{
    background:linear-gradient(135deg,#2563eb,#60a5fa);
}

.rate-card{
    background:linear-gradient(135deg,#7c3aed,#a78bfa);
}

/* CHARTS */
.charts{
    display:grid;
    grid-template-columns:2fr 1fr;
    gap:20px;
    margin-top:25px;
}

.chart-box{
    background:#1e293b;
    padding:25px;
    border-radius:20px;
}

/* TABLE */
.table-box{
    background:#1e293b;
    padding:25px;
    border-radius:20px;
    margin-top:25px;
}

.table{
    width:100%;
    border-collapse:collapse;
}

.table th, .table td{
    padding:12px;
    border-bottom:1px solid rgba(255,255,255,0.1);
}

/* STATUS COLORS */
.status-present{
    background:#22c55e;
    padding:5px 10px;
    border-radius:20px;
    font-size:13px;
}

.status-absent{
    background:#ef4444;
    padding:5px 10px;
    border-radius:20px;
    font-size:13px;
}

.time-badge{
    background:#334155;
    padding:5px 10px;
    border-radius:20px;
    font-size:13px;
}

/* MOBILE */
@media(max-width:700px){

.topbar-mobile{
    display:flex;
}

.main{
    margin-left:0;
    padding:15px;
}

/* SMALL CARDS */
.stats{
    grid-template-columns:repeat(2,1fr);
    gap:12px;
}

.stat-card{
    padding:15px;
}

/* CHARTS */
.charts{
    grid-template-columns:1fr;
}

/* SIDEBAR SLIDE */
.overlay{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.6);
    display:none;
    z-index:4000;
}

.overlay.active{
    display:block;
}

.sidebar{
    left:-280px;
    position:fixed;
    top:0;
    width:260px;
    height:100vh;
    transition:0.25s ease;
    z-index:5000;
}

.sidebar.active{
    left:0;
}

}
/* ================= FIX RECENT ATTENDANCE ALIGNMENT ================= */

/* Center whole table content */
.table{
    text-align:center;
}

/* Center headers properly */
.table th{
    text-align:center;
    vertical-align:middle;
}

/* Center cells properly */
.table td{
    text-align:center;
    vertical-align:middle;
}

/* Fix badge alignment (Present / Absent / Time) */
.status-present,
.status-absent,
.time-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
}

/* Optional: removes slight left feel in first column */
.table td:first-child,
.table th:first-child{
    text-align:center;
}

/* ================================================================ */

</style>

</head>

<body>

<!-- TOP BAR -->
<div class="topbar-mobile">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <div>Smart Attendance</div>
</div>

<!-- OVERLAY -->
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">

<div class="logo">📘 Smart<br>Attendance</div>

<a href="admin_dashboard.php">🏠 Dashboard</a>
<a href="add_student.php">➕ Add Student</a>
<a href="manage_students.php">👨‍🎓 Manage Students</a>
<a href="attendance.php">🗓️ Attendance</a>

<?php if($admin_role=="superadmin"){ ?>
<a href="admin_management.php">👮 Admin Management</a>
<?php } ?>

<a href="logout.php">🚪 Logout</a>

</div>

<!-- MAIN -->
<div class="main">

<div class="top-bar">
    <h2>Dashboard</h2>
    <div>📅 <?php echo date("d M Y"); ?></div>
</div>

<!-- STATS -->
<div class="stats">

<div class="stat-card present-card">
<h2><?php echo $present_today; ?></h2>
<p>Present</p>
</div>

<div class="stat-card absent-card">
<h2><?php echo $absent_today; ?></h2>
<p>Absent</p>
</div>

<div class="stat-card total-card">
<h2><?php echo $total_students; ?></h2>
<p>Total</p>
</div>

<div class="stat-card rate-card">
<h2><?php echo $attendance_percentage; ?>%</h2>
<p>Rate</p>
</div>

</div>

<!-- CHARTS -->
<div class="charts">

<div class="chart-box">
<canvas id="barChart"></canvas>
</div>

<div class="chart-box">
<canvas id="pieChart"></canvas>
</div>

</div>

<!-- RECENT ATTENDANCE -->
<div class="table-box">

<h3>Recent Attendance</h3>

<table class="table">

<thead>
<tr>
<th>ID</th>
<th>Name</th>
<th>Status</th>
<th>Date</th>
<th>Time</th>
</tr>
</thead>

<tbody>

<?php while($row=$recent->fetch_assoc()){ ?>

<tr>
<td><?php echo $row['student_id']; ?></td>
<td><?php echo $row['name']; ?></td>

<td>
<?php if($row['status']=="Present"){ ?>
<span class="status-present">Present</span>
<?php } else { ?>
<span class="status-absent">Absent</span>
<?php } ?>
</td>

<td><?php echo $row['date']; ?></td>

<td>
<span class="time-badge"><?php echo $row['time']; ?></span>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

<script>

function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("active");
    document.getElementById("overlay").classList.toggle("active");
}

new Chart(document.getElementById("barChart"),{
type:"line",
data:{
labels:["5d","4d","3d","2d","y","t"],
datasets:[{
data:<?php echo json_encode($week_data); ?>,
borderColor:"#60a5fa",
fill:true
}]
}
});

new Chart(document.getElementById("pieChart"),{
type:"doughnut",
data:{
labels:["Present","Absent"],
datasets:[{
data:[<?php echo $present_today; ?>,<?php echo $absent_today; ?>],
backgroundColor:["#22c55e","#ef4444"]
}]
}
});

</script>

</body>