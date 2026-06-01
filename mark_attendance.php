<?php

include("../database.php");

date_default_timezone_set("Asia/Kolkata");

if(isset($_GET['fingerprint_id'])){

    $fingerprint_id = $_GET['fingerprint_id'];

    $current_time = date("H:i");
    $date         = date("Y-m-d");
    $time         = date("h:i A");

    $start_time = "09:15";
    $end_time   = "10:10";

    // ── WINDOW CLOSED → RUN AUTO-ABSENT THEN EXIT ──────────────────────
    if($current_time > $end_time){

        // Only run if not already done today
        // We track this with a simple lock row in a "auto_absent_log" table.
        // If you don't have that table, create it once:
        // CREATE TABLE auto_absent_log (date DATE PRIMARY KEY);

        $already_ran = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM auto_absent_log WHERE date='$date'"
        ));

        if(!$already_ran){

            // Mark absent for every student with no record today
            $students = mysqli_query($conn, "SELECT student_id FROM students");

            while($row = mysqli_fetch_assoc($students)){

                $sid = $row['student_id'];

                $exists = mysqli_query($conn,
                    "SELECT id FROM attendance
                     WHERE student_id='$sid' AND date='$date'"
                );

                if(mysqli_num_rows($exists) == 0){
                    mysqli_query($conn,
                        "INSERT INTO attendance (student_id, status, date, time)
                         VALUES ('$sid', 'Absent', '$date', '$time')"
                    );
                }
            }

            // Mark as done for today
            mysqli_query($conn,
                "INSERT INTO auto_absent_log (date) VALUES ('$date')"
            );
        }

        echo "Attendance Closed";
        exit();
    }
    // ───────────────────────────────────────────────────────────────────


    // ── WINDOW NOT OPEN YET ─────────────────────────────────────────────
    if($current_time < $start_time){
        echo "Attendance Not Started";
        exit();
    }
    // ───────────────────────────────────────────────────────────────────


    // ── WINDOW OPEN → MARK PRESENT AS NORMAL ───────────────────────────

    $student = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM students WHERE fingerprint_id='$fingerprint_id'"
    ));

    if($student){

        $student_id = $student['student_id'];
        $course     = $student['course'];

        // Already marked today?
        $check = mysqli_query($conn,
            "SELECT id FROM attendance
             WHERE student_id='$student_id' AND date='$date'"
        );

        if(mysqli_num_rows($check) > 0){
            echo "Already Marked";
            exit();
        }

        // Determine status: Present if on time, Late if after a grace period
        $late_after = "09:30"; // adjust to your preference
        $status = ($current_time <= $late_after) ? "Present" : "Late";

        mysqli_query($conn,
            "INSERT INTO attendance (student_id, course, status, date, time)
             VALUES ('$student_id','$course','$status','$date','$time')"
        );

        echo ($status == "Late") ? "Marked Late" : "Attendance Marked";

    } else {
        echo "Student Not Found";
    }
    // ───────────────────────────────────────────────────────────────────
}
?>