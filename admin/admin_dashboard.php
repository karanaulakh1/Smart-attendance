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

/* PRESENT TODAY */
$present_today = $conn->query("
SELECT * FROM attendance
WHERE date='$today'
AND status='Present'
")->num_rows;

/* ABSENT TODAY */
$absent_today = $total_students - $present_today;

/* PERCENTAGE */
$attendance_percentage = 0;

if($total_students > 0){
    $attendance_percentage =
    round(($present_today / $total_students) * 100);
}

/* RECENT ATTENDANCE */
$recent = $conn->query("
SELECT attendance.*, students.name
FROM attendance
LEFT JOIN students
ON attendance.student_id = students.student_id
ORDER BY attendance.id DESC
LIMIT 8
");

/* WEEKLY DATA */
$week_data = [];

for($i=6; $i>=0; $i--){
    $date = date("Y-m-d", strtotime("-$i days"));

    $count = $conn->query("
    SELECT * FROM attendance
    WHERE date='$date'
    AND status='Present'
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
/* MOBILE TOPBAR */
.topbar-mobile{
    display:none;
    justify-content:space-between;
    align-items:center;
    padding:15px 20px;
    background:rgba(15,23,42,0.95);
    position:sticky;
    top:0;
    z-index:1000;
}

.hamburger{
    font-size:26px;
    background:none;
    border:none;
    color:white;
    cursor:pointer;
}

/* SIDEBAR ANIMATION */
.sidebar{
    transition:0.3s ease;
}

/* MOBILE */
@media(max-width:700px){

.topbar-mobile{
    display:flex;
}

.sidebar{
    position:fixed;
    left:-260px;
    top:0;
    height:100%;
    z-index:999;
}

.sidebar.active{
    left:0;
}

.main{
    margin-left:0;
}
}
/* GLOBAL FIX */
html, body{
    margin:0;
    padding:0;
    box-sizing:border-box;
    overflow-x:hidden;
    font-family:'Poppins',sans-serif;
    background: linear-gradient(135deg,#0f172a,#1e293b,#312e81);
    color:white;
}

/* SIDEBAR */
.sidebar{
    width:260px;
    min-height:100vh;
    position:fixed;
    top:0;
    left:0;
    background: linear-gradient(180deg,rgba(15,23,42,0.98),rgba(30,41,59,0.96));
    backdrop-filter:blur(20px);
    border-right:1px solid rgba(255,255,255,0.06);
    padding:28px 18px;
}

.logo{
    font-size:30px;
    font-weight:700;
    margin-bottom:50px;
    padding-left:8px;
}

.sidebar a{
    display:flex;
    gap:14px;
    text-decoration:none;
    color:#fff;
    font-size:18px;
    padding:16px 18px;
    border-radius:16px;
    margin-bottom:8px;
    transition:0.3s;
}

.sidebar a:hover{
    background: linear-gradient(135deg,#2563eb,#38bdf8);
    transform:translateX(4px);
}

.active{
    background: linear-gradient(135deg,#2563eb,#38bdf8);
}

/* MAIN */
.main{
    margin-left:260px;
    padding:35px;
}

/* TOP BAR */
.top-bar{
    display:flex;
    justify-content:space-between;
    margin-bottom:35px;
}

.page-title{
    font-size:38px;
    font-weight:700;
}

/* STATS */
.stats{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:25px;
}

.stat-card{
    background:rgba(30,41,59,0.8);
    padding:30px;
    border-radius:30px;
}

.stat-card h2{
    font-size:42px;
}

/* CHARTS */
.charts{
    display:grid;
    grid-template-columns:2fr 1fr;
    gap:25px;
    margin-top:35px;
}

.chart-box{
    background:rgba(30,41,59,0.8);
    padding:30px;
    border-radius:30px;
}

/* TABLE */
.table-box{
    background:rgba(30,41,59,0.8);
    padding:30px;
    border-radius:30px;
    margin-top:35px;
}

.table{
    width:100%;
    border-collapse:collapse;
}

.table th, .table td{
    padding:15px;
    border-bottom:1px solid rgba(255,255,255,0.1);
}

/* STATUS */
.present{
    background:#10b981;
    padding:6px 12px;
    border-radius:20px;
}

.absent{
    background:#ef4444;
    padding:6px 12px;
    border-radius:20px;
}

/* CANVAS FIX */
canvas{
    width:100% !important;
    max-height:320px !important;
}

/* MOBILE FIX */
@media(max-width:1100px){
    .stats{grid-template-columns:repeat(2,1fr);}
    .charts{grid-template-columns:1fr;}
}

@media(max-width:700px){
    .sidebar{
        width:100%;
        position:relative;
        min-height:auto;
    }

    .main{
        margin-left:0;
        padding:20px;
    }

    .stats{
        grid-template-columns:1fr;
    }

    .top-bar{
        flex-direction:column;
        gap:10px;
    }
}

</style>

</head>

<body>

<!-- HAMBURGER BUTTON -->
<div class="topbar-mobile">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <div class="mobile-title">Smart Attendance</div>
</div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">

<div class="logo">📘 Smart<br>Attendance</div>

<a href="admin_dashboard.php">🏠 Dashboard</a>
<a href="add_student.php">➕ Add Student</a>
<a href="manage_students.php">👨‍🎓 Manage Students</a>
<a href="attendance.php">🗓️ Attendance</a>
<a href="admin_management.php">👮 Admin Management</a>

<a href="javascript:void(0);" onclick="confirmLogout()">🚪 Logout</a>

</div>

<!-- MAIN -->
<div class="main">

<div class="top-bar">
    <div class="page-title">Dashboard Analytics</div>
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
<p>Present Today</p>
</div>

<div class="stat-card">
<h2><?php echo $absent_today; ?></h2>
<p>Absent Today</p>
</div>

<div class="stat-card">
<h2><?php echo $attendance_percentage; ?>%</h2>
<p>Attendance Rate</p>
</div>

</div>

<!-- CHARTS -->
<div class="charts">

<div class="chart-box">
<h3>Weekly Attendance</h3>
<canvas id="barChart"></canvas>
</div>

<div class="chart-box">
<h3>Today Overview</h3>
<canvas id="pieChart"></canvas>
</div>

</div>

<!-- RECENT -->
<div class="table-box">

<h3>Recent Attendance</h3>

<table class="table">
<thead>
<tr>
<th>#</th>
<th>Name</th>
<th>Status</th>
<th>Date</th>
<th>Time</th>
</tr>
</thead>

<tbody>

<?php
$i=1;
while($row=$recent->fetch_assoc()){
?>
<tr>
<td><?php echo $i++; ?></td>
<td><?php echo $row['name']; ?></td>
<td>
<?php if($row['status']=="Present"){ ?>
<span class="present">Present</span>
<?php } else { ?>
<span class="absent">Absent</span>
<?php } ?>
</td>
<td><?php echo $row['date']; ?></td>
<td><?php echo $row['time']; ?></td>
</tr>
<?php } ?>

</tbody>
</table>

</div>

<!-- ADMIN MANAGEMENT (NEW) -->
<div class="table-box">

<h3>Admin Management</h3>

<table class="table">

<thead>
<tr>
<th>#</th>
<th>Username</th>
<th>Email</th>
<th>Role</th>
</tr>
</thead>

<tbody>

<?php
$admin = $conn->query("SELECT * FROM admin");
$i=1;
while($a=$admin->fetch_assoc()){
?>

<tr>
<td><?php echo $i++; ?></td>
<td><?php echo $a['username']; ?></td>
<td><?php echo $a['email']; ?></td>
<td><?php echo $a['role']; ?></td>
</tr>

<?php } ?>

</tbody>
</table>

</div>

</div>

<!-- CHART JS -->
<script>
new Chart(document.getElementById('barChart'),{
type:'line',
data:{
labels:['5d','4d','3d','2d','yesterday','today'],
datasets:[{
data:<?php echo json_encode($week_data); ?>,
borderColor:'#60a5fa',
fill:true
}]
}
});

new Chart(document.getElementById('pieChart'),{
type:'doughnut',
data:{
labels:['Present','Absent'],
datasets:[{
data:[<?php echo $present_today; ?>,<?php echo $absent_today; ?>],
backgroundColor:['#22c55e','#ef4444']
}]
}
});
</script>

<script>
function confirmLogout(){
if(confirm("Logout?")){
window.location="logout.php";
}
}
</script>

</body>
</html>