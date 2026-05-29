<?php

include 'database.php';

$student = null;
$attendance = [];
$total_present = 0;
$total_absent = 0;
$percentage = 0;

if(isset($_GET['student_id'])){

    $student_id = $_GET['student_id'];

    // STUDENT DATA
    $student_query = $conn->query("
    SELECT * FROM students
    WHERE student_id='$student_id'
    ");

    if($student_query->num_rows > 0){

        $student = $student_query->fetch_assoc();

        // ATTENDANCE
        $attendance_query = $conn->query("
        SELECT * FROM attendance
        WHERE student_id='$student_id'
        ORDER BY date DESC
        ");

        while($row = $attendance_query->fetch_assoc()){

            $attendance[] = $row;

            if($row['status'] == 'Present'){
                $total_present++;
            }else{
                $total_absent++;
            }
        }

        $total_classes = $total_present + $total_absent;

        if($total_classes > 0){
            $percentage = round(($total_present / $total_classes) * 100);
        }

    }

}

?>

<!DOCTYPE html>
<html>
<head>

<title>Student Attendance</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Poppins',sans-serif;
    background:#0f172a;
    color:white;
    min-height:100vh;
}

/* HEADER */

.header{
    padding:20px 50px;
    background:rgba(255,255,255,0.05);
    backdrop-filter:blur(10px);
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.logo{
    font-size:28px;
    font-weight:700;
}

.back-btn{
    background:#2563eb;
    color:white;
    padding:10px 20px;
    border-radius:12px;
    text-decoration:none;
    transition:0.3s;
}

.back-btn:hover{
    background:#1d4ed8;
}

/* MAIN */

.main{
    padding:40px;
}

/* TOP CARD */

.student-card{
    background:rgba(255,255,255,0.06);
    border:1px solid rgba(255,255,255,0.08);
    backdrop-filter:blur(15px);
    border-radius:25px;
    padding:35px;
    margin-bottom:35px;
}

.student-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
}

.student-name{
    font-size:38px;
    font-weight:700;
}

.student-id{
    color:#cbd5e1;
    margin-top:5px;
}

/* ATTENDANCE PERCENT */

.percent-box{
    width:140px;
    height:140px;
    border-radius:50%;
    background:conic-gradient(
        #10b981 <?php echo $percentage; ?>%,
        rgba(255,255,255,0.1) 0%
    );
    display:flex;
    justify-content:center;
    align-items:center;
}

.percent-inner{
    width:105px;
    height:105px;
    background:#0f172a;
    border-radius:50%;
    display:flex;
    justify-content:center;
    align-items:center;
    flex-direction:column;
}

.percent-inner h2{
    margin:0;
    font-size:30px;
}

/* STATS */

.stats{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:25px;
    margin-bottom:35px;
}

.stat-card{
    background:rgba(255,255,255,0.06);
    padding:30px;
    border-radius:22px;
    text-align:center;
    border:1px solid rgba(255,255,255,0.08);
}

.stat-card h2{
    font-size:38px;
    font-weight:700;
    margin-bottom:10px;
}

.stat-card p{
    color:#cbd5e1;
}

/* TABLE */

.table-box{
    background:rgba(255,255,255,0.06);
    border-radius:25px;
    padding:30px;
    border:1px solid rgba(255,255,255,0.08);
}

.table{
    color:white;
}

.table thead{
    background:rgba(255,255,255,0.08);
}

.table td,
.table th{
    padding:16px;
}

.present{
    background:#10b981;
    padding:6px 14px;
    border-radius:20px;
    font-size:13px;
}

.absent{
    background:#ef4444;
    padding:6px 14px;
    border-radius:20px;
    font-size:13px;
}

/* NO DATA */

.no-data{
    text-align:center;
    padding:80px 20px;
}

.no-data h2{
    margin-bottom:15px;
}

/* MOBILE */

@media(max-width:900px){

.stats{
    grid-template-columns:1fr;
}

.student-top{
    flex-direction:column;
    align-items:flex-start;
    gap:30px;
}

.main{
    padding:20px;
}

.header{
    padding:20px;
}

.student-name{
    font-size:28px;
}

}

</style>

</head>

<body>

<!-- HEADER -->

<div class="header">

<div class="logo">
📘 Smart Attendance
</div>

<a href="index.php" class="back-btn">
← Back
</a>

</div>

<!-- MAIN -->

<div class="main">

<?php if($student){ ?>

<!-- STUDENT CARD -->

<div class="student-card">

<div class="student-top">

<div>

<h1 class="student-name">
<?php echo $student['name']; ?>
</h1>

<div class="student-id">
Student ID: <?php echo $student['student_id']; ?>
</div>

</div>

<div class="percent-box">

<div class="percent-inner">

<h2><?php echo $percentage; ?>%</h2>

<small>Attendance</small>

</div>

</div>

</div>

</div>

<!-- STATS -->

<div class="stats">

<div class="stat-card">

<h2><?php echo $total_present; ?></h2>

<p>Present Days</p>

</div>

<div class="stat-card">

<h2><?php echo $total_absent; ?></h2>

<p>Absent Days</p>

</div>

<div class="stat-card">

<h2><?php echo count($attendance); ?></h2>

<p>Total Classes</p>

</div>

</div>

<!-- TABLE -->

<div class="table-box">

<h3 class="mb-4">
Attendance History
</h3>

<div class="table-responsive">

<table class="table">

<thead>

<tr>

<th>#</th>
<th>Date</th>
<th>Status</th>
<th>Time</th>

</tr>

</thead>

<tbody>

<?php
$count = 1;

foreach($attendance as $row){
?>

<tr>

<td><?php echo $count++; ?></td>

<td><?php echo $row['date']; ?></td>

<td>

<?php if($row['status'] == 'Present'){ ?>

<span class="present">
Present
</span>

<?php } else { ?>

<span class="absent">
Absent
</span>

<?php } ?>

</td>

<td><?php echo $row['time']; ?></td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

<?php } else { ?>

<div class="no-data">

<h2>Student Not Found</h2>

<p>
No attendance data available for this student ID.
</p>

</div>

<?php } ?>

</div>

</body>
</html>