<?php

include 'database.php';

date_default_timezone_set("Asia/ata");

/* GET STUDENT ID */

if(!isset($_GET['student_id'])){

    die("Invalid Access");

}

$student_id = $_GET['student_id'];

/* FETCH STUDENT */

$query = mysqli_query($conn,"
SELECT * FROM students
WHERE student_id='$student_id'
");

$student = mysqli_fetch_assoc($query);

if(!$student){

    die("Student Not Found");

}

/* MARK ATTENDANCE */

$success = false;

if(isset($_POST['mark_attendance'])){

    $date = date("Y-m-d");

    $time = date("h:i A");

    /* CHECK ALREADY MARKED */

    $check = mysqli_query($conn,"
    SELECT * FROM attendance
    WHERE student_id='$student_id'
    AND date='$date'
    ");

    if(mysqli_num_rows($check) == 0){

        mysqli_query($conn,"
        
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
        'Present',
        '$date',
        '$time'
        )
        
        ");

        $success = true;

    }

}

?>

<!DOCTYPE html>
<html>

<head>

<title>Mark Attendance</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

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
    #1e1b4b,
    #111827
    );

    min-height:100vh;

    color:white;

}

/* MAIN CONTAINER */

.container{

    display:flex;

    justify-content:center;

    align-items:center;

    min-height:100vh;

    padding:20px;

}

/* CARD */

.card{

    width:100%;
    max-width:650px;

    background:
    rgba(255,255,255,0.08);

    border:
    1px solid rgba(255,255,255,0.1);

    backdrop-filter:blur(18px);

    border-radius:28px;

    padding:40px;

    box-shadow:
    0 10px 40px rgba(0,0,0,0.4);

}

/* TITLE */

.title{

    font-size:34px;

    font-weight:700;

    margin-bottom:30px;

    text-align:center;

}

/* STUDENT BOX */

.student-box{

    background:
    rgba(255,255,255,0.06);

    border-radius:22px;

    padding:30px;

    margin-bottom:30px;

}

.student-box h2{

    font-size:28px;

    margin-bottom:20px;

    color:#60a5fa;

}

/* INFO */

.info{

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:20px;

}

.info-item{

    background:
    rgba(255,255,255,0.05);

    padding:18px;

    border-radius:16px;

}

.label{

    color:#94a3b8;

    font-size:14px;

    margin-bottom:6px;

}

.value{

    font-size:18px;

    font-weight:600;

}

/* BUTTON */

.btn{

    width:100%;

    padding:18px;

    border:none;

    border-radius:18px;

    background:
    linear-gradient(
    135deg,
    #2563eb,
    #3b82f6
    );

    color:white;

    font-size:18px;

    font-weight:600;

    cursor:pointer;

    transition:0.3s;

}

.btn:hover{

    transform:translateY(-2px);

    box-shadow:
    0 10px 25px rgba(37,99,235,0.4);

}

/* SUCCESS */

.success{

    background:
    rgba(16,185,129,0.15);

    border:
    1px solid rgba(16,185,129,0.4);

    color:#6ee7b7;

    padding:20px;

    border-radius:18px;

    margin-bottom:25px;

    text-align:center;

    font-size:18px;

    font-weight:600;

}

/* DATE */

.date-time{

    text-align:center;

    margin-bottom:25px;

    color:#cbd5e1;

    font-size:15px;

}

/* MOBILE */

@media(max-width:700px){

.info{

    grid-template-columns:1fr;

}

.card{

    padding:25px;

}

.title{

    font-size:28px;

}

.student-box h2{

    font-size:24px;

}

}

</style>

</head>

<body>

<div class="container">

<div class="card">

<div class="title">

📅 Mark Attendance

</div>

<div class="date-time">

<?php echo date("d M Y | h:i A"); ?>

</div>

<?php if($success){ ?>

<div class="success">

✅ Attendance Marked Successfully

</div>

<?php } ?>

<div class="student-box">

<h2>

👨‍🎓 <?php echo $student['name']; ?>

</h2>

<div class="info">

<div class="info-item">

<div class="label">

Student ID

</div>

<div class="value">

<?php echo $student['student_id']; ?>

</div>

</div>

<div class="info-item">

<div class="label">

Department

</div>

<div class="value">

<?php echo $student['department']; ?>

</div>

</div>

<div class="info-item">

<div class="label">

Course

</div>

<div class="value">

<?php echo $student['course']; ?>

</div>

</div>

<div class="info-item">

<div class="label">

Year

</div>

<div class="value">

<?php echo $student['year']; ?>

</div>

</div>

</div>

</div>

<form method="POST">

<button
type="submit"
name="mark_attendance"
class="btn">

✅ MARK ATTENDANCE

</button>

</form>

</div>

</div>

</body>
</html>