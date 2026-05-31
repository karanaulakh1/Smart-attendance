<?php
session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}



/* MARK ATTENDANCE */

if(isset($_POST['mark_attendance'])){

    $student_id = $_POST['student_id'];

    $status = $_POST['status'];

    $date = date("Y-m-d");

    $time = date("h:i:s");

    /* CHECK ALREADY MARKED */

    $check = $conn->query("
    SELECT * FROM attendance
    WHERE student_id='$student_id'
    AND date='$date'
    ");

    if($check->num_rows == 0){

        $conn->query("
        INSERT INTO attendance
        (
        student_id,
        status,
        date,
        time
        )

        VALUES
        (
        '$student_id',
        '$status',
        '$date',
        '$time'
        )
        ");

        $success = "Attendance Marked Successfully";

    }else{

        $error = "Attendance Already Marked Today";
    }
}

/* FETCH STUDENTS */

$students = $conn->query("
SELECT * FROM students
ORDER BY id DESC
");

/* TODAY ATTENDANCE */

$today = date("Y-m-d");

$attendance = $conn->query("
SELECT attendance.*, students.name
FROM attendance
LEFT JOIN students
ON attendance.student_id = students.student_id
WHERE attendance.date='$today'
ORDER BY attendance.id DESC
");

?>


<!DOCTYPE html>
<html>
<head>

<title>Attendance</title>

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{

    background:
    linear-gradient(
    135deg,
    #0f172a,
    #1e293b,
    #312e81
    );

    min-height:100vh;

    color:white;

    overflow-x:hidden;
}

/* SIDEBAR */

.sidebar{

    width:260px;

    min-height:100vh;

    position:fixed;

    top:0;
    left:0;

    background:
    linear-gradient(
    180deg,
    rgba(15,23,42,0.98),
    rgba(30,41,59,0.96)
    );

    backdrop-filter:blur(20px);

    border-right:
    1px solid rgba(255,255,255,0.06);

    padding:28px 18px;
}

/* LOGO */

.logo{

    font-size:30px;

    font-weight:700;

    color:white;

    line-height:1.3;

    margin-bottom:50px;

    padding-left:8px;
}

/* MENU */

.sidebar a{

    display:flex;

    align-items:center;

    gap:14px;

    text-decoration:none;

    color:#ffffff;

    font-size:18px;

    font-weight:500;

    padding:16px 18px;

    border-radius:16px;

    margin-bottom:8px;

    transition:0.3s;
}

.sidebar a:hover{

    background:
    linear-gradient(
    135deg,
    #2563eb,
    #38bdf8
    );

    transform:translateX(4px);
}

/* ACTIVE */

.active{

    background:
    linear-gradient(
    135deg,
    #2563eb,
    #38bdf8
    );

    color:white !important;

    box-shadow:
    0 8px 25px rgba(37,99,235,0.35);
}

/* MAIN */

.main{

    margin-left:260px;

    padding:40px;
}

/* TOP */

.top-bar{

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-bottom:35px;
}

.page-title{

    font-size:38px;

    font-weight:700;
}

.date-box{

    background:
    rgba(255,255,255,0.08);

    border:
    1px solid rgba(255,255,255,0.08);

    padding:14px 22px;

    border-radius:16px;

    backdrop-filter:blur(18px);
}

/* CARD */

.card{

    background:
    linear-gradient(
    145deg,
    rgba(30,41,59,0.75),
    rgba(15,23,42,0.95)
    );

    border:
    1px solid rgba(255,255,255,0.05);

    border-radius:30px;

    padding:30px;

    backdrop-filter:blur(18px);

    box-shadow:
    0 10px 40px rgba(0,0,0,0.35);

    margin-bottom:30px;
}

/* FORM */

.form-grid{

    display:grid;

    grid-template-columns:1fr 1fr 1fr auto;

    gap:20px;
}

select{

    width:100%;

    padding:16px;

    border:none;

    border-radius:16px;

    background:#0f172a;

    color:white;

    font-size:15px;

    outline:none;
}

button{

    padding:16px 28px;

    border:none;

    border-radius:16px;

    background:
    linear-gradient(
    135deg,
    #2563eb,
    #38bdf8
    );

    color:white;

    font-size:15px;

    font-weight:600;

    cursor:pointer;

    transition:0.3s;
}

button:hover{

    transform:translateY(-3px);
}

/* ALERTS */

.success{

    background:#10b981;

    padding:16px;

    border-radius:16px;

    margin-bottom:20px;
}

.error{

    background:#ef4444;

    padding:16px;

    border-radius:16px;

    margin-bottom:20px;
}

/* TABLE */

.table-box{

    overflow-x:auto;
}

table{

    width:100%;

    border-collapse:collapse;
}

th{

    text-align:left;

    padding:18px;

    color:#93c5fd;

    font-size:15px;
}

td{

    padding:18px;

    border-top:
    1px solid rgba(255,255,255,0.05);
}

/* STATUS */

.present{

    background:#10b981;

    padding:7px 16px;

    border-radius:20px;

    font-size:13px;
}

.absent{

    background:#ef4444;

    padding:7px 16px;

    border-radius:20px;

    font-size:13px;
}

.late{

    background:#f59e0b;

    padding:7px 16px;

    border-radius:20px;

    font-size:13px;
}

/* RESPONSIVE */

@media(max-width:1000px){

.form-grid{
    grid-template-columns:1fr;
}

}

@media(max-width:700px){

.sidebar{
    width:100%;
    min-height:auto;
    position:relative;
}

.main{
    margin-left:0;
}

.top-bar{
    flex-direction:column;
    align-items:flex-start;
    gap:15px;
}

}

</style>

</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

<div class="logo">
📘 Smart<br>Attendance
</div>

<a href="admin_dashboard.php">
🏠 Dashboard
</a>

<a href="add_student.php">
➕ Add Student
</a>

<a href="manage_students.php">
👨‍🎓 Manage Students
</a>

<a href="attendance.php" class="active">
🗓️ Attendance
</a>

<a href="admin_management.php">👮 Admin Management</a>

<a href="javascript:void(0);"
class="logout-btn"
onclick="confirmLogout()">

🚪 Logout

</a>

</div>

<!-- MAIN -->

<div class="main">

<!-- TOP -->

<div class="top-bar">

<div class="page-title">
Attendance Management
</div>

<div class="date-box">
📅 <?php echo date("d M Y"); ?>
</div>

</div>

<!-- ALERT -->

<?php if(isset($success)){ ?>

<div class="success">
✅ <?php echo $success; ?>
</div>

<?php } ?>

<?php if(isset($error)){ ?>

<div class="error">
❌ <?php echo $error; ?>
</div>

<?php } ?>

<!-- FORM -->

<div class="card">
    <form method="GET" action="export_excel.php">

<select name="course" required>

<option value="">Select Course</option>

<option value="IOT">IOT</option>

<option value="AI">AI</option>

</select>

<button type="submit">
Export Excel
</button>

</form>

<br><br>

<form method="POST">

<div class="form-grid">

<select name="student_id" required>

<option value="">
Select Student
</option>

<?php while($row = $students->fetch_assoc()){ ?>

<option value="<?php echo $row['student_id']; ?>">

<?php echo $row['name']; ?>
(<?php echo $row['student_id']; ?>)

</option>

<?php } ?>

</select>

<select name="status" required>

<option value="">
Select Status
</option>

<option value="Present">
Present
</option>

<option value="Absent">
Absent
</option>

<option value="Late">
Late
</option>

</select>

<button type="submit"
name="mark_attendance">

Mark Attendance

</button>

</div>

</form>

</div>

<!-- TABLE -->

<div class="card">

<div class="page-title"
style="font-size:28px; margin-bottom:25px;">

Today's Attendance

</div>

<div class="table-box">

<table>

<thead>

<tr>

<th>#</th>
<th>Student</th>
<th>Status</th>
<th>Date</th>
<th>Time</th>

</tr>

</thead>

<tbody>

<?php

$count = 1;

while($att = $attendance->fetch_assoc()){

?>

<tr>

<td>
<?php echo $count++; ?>
</td>

<td>
<?php echo $att['name']; ?>
</td>

<td>

<?php if($att['status']=="Present"){ ?>

<span class="present">
Present
</span>

<?php }elseif($att['status']=="Late"){ ?>

<span class="late">
Late
</span>

<?php }else{ ?>

<span class="absent">
Absent
</span>

<?php } ?>

</td>

<td>
<?php echo $att['date']; ?>
</td>

<td>
<?php echo $att['time']; ?>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>
<script>

function confirmLogout(){

    let confirmAction = confirm(
    "Are you sure you want to logout?"
    );

    if(confirmAction){

        window.location = "logout.php";

    }

}

</script>

</body>
</html>
</body>
</html>