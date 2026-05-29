<?php
include '../database.php';

// Check if student_id is received
if(isset($_GET['student_id'])){

    $student_id = $_GET['student_id'];

    // 1. Check if student exists
    $check = "SELECT * FROM students WHERE student_id='$student_id'";
    $result = $conn->query($check);

    if($result->num_rows > 0){

        // 2. Generate unique token
        $token = bin2hex(random_bytes(5)); // 10-character token

        // 3. Set expiry time (10 seconds)
        $expiry_time = date("Y-m-d H:i:s", time() + 10);

        // 4. Insert into tokens table
        $sql = "INSERT INTO tokens (student_id, token, expiry_time, used)
                VALUES ('$student_id', '$token', '$expiry_time', FALSE)";

        if($conn->query($sql) === TRUE){

            // 5. Send token to ESP32
            echo json_encode([
                "status" => "success",
                "token" => $token
            ]);

        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Database error"
            ]);
        }

    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Student not found"
        ]);
    }

} else {
    echo json_encode([
        "status" => "error",
        "message" => "No student_id received"
    ]);
}
?>