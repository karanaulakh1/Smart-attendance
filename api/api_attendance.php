<?php

include("../database.php");

date_default_timezone_set("Asia/Kolkata");

if(isset($_GET['fingerprint_id'])){

    $fingerprint_id = $_GET['fingerprint_id'];

    $current_time = date("H:i");

    $start_time = "09:15";

    $end_time = "10:10";

    // TIME CHECK

    if($current_time < $start_time || $current_time > $end_time){

        echo "Attendance Closed";

        exit();
    }

    // FIND STUDENT

    $student = mysqli_fetch_assoc(mysqli_query($conn,

    "SELECT * FROM students
    WHERE fingerprint_id='$fingerprint_id'"));

    if($student){

        $student_id = $student['student_id'];

        $course = $student['course'];

        $date = date("Y-m-d");

        $time = date("h:i A");

        // CHECK ALREADY MARKED

        $check = mysqli_query($conn,

        "SELECT * FROM attendance
        WHERE student_id='$student_id'
        AND date='$date'");

        if(mysqli_num_rows($check) > 0){

            echo "Already Marked";

            exit();
        }

        // INSERT ATTENDANCE

        mysqli_query($conn,

        "INSERT INTO attendance
        (student_id, course, status, date, time)

        VALUES
        ('$student_id','$course','Present','$date','$time')");

        echo "Attendance Marked";

    }else{

        echo "Student Not Found";
    }
}
?>