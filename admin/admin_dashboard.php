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

/* DATA */
$total_students = $conn->query("SELECT * FROM students")->num_rows;

$present_today = $conn->query("
SELECT * FROM attendance
WHERE date='$today' AND status='Present'
")->num_rows;

$absent_today = $total_students - $present_today;

$attendance_percentage = ($total_students > 0)
    ? round(($present_today / $total_students) * 100)
    : 0;

/* RECENT */
$recent = $conn->query("
SELECT attendance.student_id, attendance.status, attendance.date, attendance.time, students.name
FROM attendance
LEFT JOIN students
ON attendance.student_id = students.student_id
ORDER BY attendance.id DESC
LIMIT 8
");

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

/* SIDEBAR */
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

/* MAIN */
.main{
    margin-left:260px;
    padding:30px;
}

/* TABLE FIX (IMPORTANT PART) */
.table-box{
    background:#1e293b;
    padding:25px;
    border-radius:20px;
    margin-top:25px;
}

/* 🔥 FIX ALIGNMENT */
.table{
    width:100%;
    border-collapse:collapse;
    text-align:center;   /* IMPORTANT */
}

.table th{
    padding:14px;
    background:#334155;
    text-align:center;
    vertical-align:middle;
}

.table td{
    padding:14px;
    text-align:center;   /* FIXED */
    vertical-align:middle; /* FIXED */
}

/* STATUS BADGES FIX */
.status-present,
.status-absent,
.time-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:80px;
    padding:6px 12px;
    border-radius:20px;
    font-size:13px;
}

/* COLORS */
.status-present{
    background:#22c55e;
}

.status-absent{
    background:#ef4444;
}

.time-badge{
    background:#334155;
}

/* ROW CLEAN LOOK */
.table tr{
    border-bottom:1px solid rgba(255,255,255,0.08);
}

/* REMOVE LEFT SHIFT ISSUE */
.table td:first-child,
.table th:first-child{
    padding-left:0;
}

/* MOBILE */
@media(max-width:700px){
.main{
    margin-left:0;
    padding:15px;
}
}

</style>

</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

<div class="logo">📘 Smart Attendance</div>

<a href="admin_dashboard.php">Dashboard</a>

</div>

<!-- MAIN -->
<div class="main">

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

</body>
</html>