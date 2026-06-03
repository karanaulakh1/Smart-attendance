<?php

include("../database.php");

if(isset($_GET['student_id'])){

    $student_id = $_GET['student_id'];

    mysqli_query($conn,

    "UPDATE students

    SET enroll_request='1'

    WHERE student_id='$student_id'");

    echo "

    <script>

    alert('Enrollment Request Sent');

    window.location='manage_memberss.php';

    </script>

    ";
}
?>