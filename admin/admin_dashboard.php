<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

date_default_timezone_set("Asia/Kolkata");

$today = date("Y-m-d");

/* TOTAL STUDENTS */
$total_students = $conn->query("SELECT * FROM students")->num_rows;

/* PRESENT */
$present_today = $conn->query("
SELECT * FROM attendance
WHERE date='$today' AND status='Present'
")->num_rows;

/* ABSENT */
$absent_today = $total_students - $present_today;

/* PERCENTAGE */
$attendance_percentage = ($total_students > 0)
    ? round(($present_today / $total_students) * 100)
    : 0;

/* RECENT */
$recent = $conn->query("
SELECT attendance.*, students.name
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

/* ================= GLOBAL ================= */
*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:Poppins;
    background:#0f172a;
    color:white;
    overflow-x:hidden;
}

/* ================= TOP BAR ================= */
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

/* ================= SIDEBAR (DESKTOP) ================= */
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

/* ================= MAIN ================= */
.main{
    margin-left:260px;
    padding:30px;
}

/* TOP BAR CONTENT */
.top-bar{
    display:flex;
    justify-content:space-between;
    margin-bottom:25px;
}

/* ================= STATS ================= */
.stats{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:20px;
}

.stat-card{
    background:#1e293b;
    padding:25px;
    border-radius:20px;
}

.stat-card h2{
    font-size:36px;
}

/* ================= CHARTS ================= */
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

/* ================= TABLE ================= */
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

/* STATUS */
.present{
    background:#10b981;
    padding:5px 10px;
    border-radius:20px;
}

.absent{
    background:#ef4444;
    padding:5px 10px;
    border-radius:20px;
}

/* ================= OVERLAY ================= */
.overlay{
    display:none;
}

/* ================= MOBILE FIX ================= */
@media(max-width:700px){

.topbar-mobile{
    display:flex;
}

.main{
    margin-left:0;
    padding:15px;
}

/* SMALLER CARDS */
.stats{
    grid-template-columns:repeat(2,1fr);
    gap:12px;
}

.stat-card{
    padding:15px;
}

.stat-card h2{
    font-size:24px;
}

/* SINGLE COLUMN CHART */
.charts{
    grid-template-columns:1fr;
}

/* TABLE SMALL */
.table-box{
    padding:15px;
}

.table th, .table td{
    padding:8px;
    font-size:13px;
}

/* OVERLAY */
.overlay{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.6);
    z-index:4000;
    display:none;
}

.overlay.active{
    display:block;
}

/* ================= LEFT SLIDE SIDEBAR (FIXED UX) ================= */
.sidebar{
    position:fixed;
    left:-280px;
    top:0;
    height:100vh;
    width:260px;
    background:#1e293b;
    transition:0.25s ease;
    z-index:5000;
}

/* ACTIVE */
.sidebar.active{
    left:0;
}

}

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
<a href="admin_management.php">👮 Admin</a>

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

<div class="stat-card">
<h2><?php echo $total_students; ?></h2>
<p>Total Students</p>
</div>

<div class="stat-card">
<h2><?php echo $present_today; ?></h2>
<p>Present</p>
</div>

<div class="stat-card">
<h2><?php echo $absent_today; ?></h2>
<p>Absent</p>
</div>

<div class="stat-card">
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

<!-- TABLE -->
<div class="table-box">

<h3>Recent Attendance</h3>

<table class="table">

<?php while($row=$recent->fetch_assoc()){ ?>

<tr>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['status']; ?></td>
<td><?php echo $row['date']; ?></td>
</tr>

<?php } ?>

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
</html>