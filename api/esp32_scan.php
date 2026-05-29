<?php
$conn = new mysqli("ql208.infinityfree.com",
"if0_41971500",
"r8ZJW1AC2Bm8cr",
"if0_41971500_attendansce_system");

if ($conn->connect_error) {
    die("DB error");
}

$student_id = $_POST['student_id'];

$sql = "INSERT INTO esp32_scan (student_id) VALUES ('$student_id')";
$conn->query($sql);

echo "OK";
?>