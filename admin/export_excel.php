<?php

include("../database.php");

header("Content-Type: application/vnd.ms-excel");

header("Content-Disposition: attachment; filename=attendance_report.xls");

$course = $_GET['course'];

echo "Attendance Report - ".$course."\n\n";

echo "Student ID\tName\tCourse\tPresent Days\n";

$query = mysqli_query($conn,

"SELECT students.student_id,
students.name,
students.course,
COUNT(attendance.id) AS total_present

FROM students

LEFT JOIN attendance
ON students.student_id = attendance.student_id

WHERE students.course='$course'

GROUP BY students.student_id

");

while($row = mysqli_fetch_assoc($query)){

    echo $row['student_id']."\t";

    echo $row['name']."\t";

    echo $row['course']."\t";

    echo $row['total_present']."\n";
}

?>