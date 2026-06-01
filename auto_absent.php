<?php
include("database.php");

date_default_timezone_set("Asia/Kolkata");

$today = date("Y-m-d");
$time  = "23:59:00";

// Check if already ran today
$check = mysqli_query($conn,
    "SELECT * FROM auto_absent_log WHERE date='$today'"
);

if(mysqli_num_rows($check) > 0){
    echo "Already ran today.";
    exit();
}

// Insert Absent for students with no record today
$result = mysqli_query($conn, "
    INSERT INTO attendance (student_id, course, status, date, time)
    SELECT s.student_id, s.course, 'Absent', '$today', '$time'
    FROM students s
    WHERE s.student_id NOT IN (
        SELECT a.student_id 
        FROM attendance a 
        WHERE a.date = '$today'
    )
");

// Lock so it won't run again today
mysqli_query($conn,
    "INSERT INTO auto_absent_log (date) VALUES ('$today')"
);

$count = mysqli_affected_rows($conn);
echo "Done. $count students marked absent for $today.";
?>