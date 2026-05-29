<?php

include("../database.php");

if(isset($_GET['student_id']) &&
isset($_GET['fingerprint_id'])){

    $student_id = $_GET['student_id'];

    $fingerprint_id = $_GET['fingerprint_id'];

    mysqli_query($conn,

    "UPDATE students

    SET fingerprint_id='$fingerprint_id',
    enroll_request='0'

    WHERE student_id='$student_id'");

    echo "Done";
}
?>