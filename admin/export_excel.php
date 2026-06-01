<?php

session_start();
include '../database.php';

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

$date = date("Y-m-d");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=attendance_'.$date.'.csv');

$output = fopen('php://output', 'w');

fputcsv($output, array(
    'ID',
    'Student ID',
    'Student Name',
    'Date',
    'Time',
    'Status',
    'Course'
));

$sql = "
SELECT attendance.*,
       students.name
FROM attendance
LEFT JOIN students
ON attendance.student_id = students.student_id
ORDER BY attendance.id DESC
";

$result = $conn->query($sql);

while($row = $result->fetch_assoc()){

    fputcsv($output, array(
        $row['id'],
        $row['student_id'],
        $row['name'],
        $row['date'],
        $row['time'],
        $row['status'],
        $row['course']
    ));
}

fclose($output);
exit();

?>
