<?php
include 'database.php';

// Check if token received
if(isset($_GET['token'])){

    $token = $_GET['token'];

    // 1. Find token in database
    $sql = "SELECT * FROM tokens WHERE token='$token'";
    $result = $conn->query($sql);

    if($result->num_rows > 0){

        $row = $result->fetch_assoc();

        $student_id = $row['student_id'];
        $expiry_time = $row['expiry_time'];
        $used = $row['used'];

        $current_time = date("Y-m-d H:i:s");

        // 2. Check if already used
        if($used){
            echo "<h3 style='color:red;'>❌ Token already used</h3>";
            exit();
        }

        // 3. Check expiry
        if($current_time > $expiry_time){
            echo "<h3 style='color:red;'>❌ Token expired</h3>";
            exit();
        }

        // 4. Mark attendance
        $date = date("Y-m-d");
        $time = date("H:i:s");

        $insert = "INSERT INTO attendance (student_id, date, time, status)
                   VALUES ('$student_id', '$date', '$time', 'Present')";

        if($conn->query($insert) === TRUE){

            // 5. Mark token as used
            $update = "UPDATE tokens SET used=TRUE WHERE token='$token'";
            $conn->query($update);

            echo "<h2 style='color:green;'>✅ Attendance Marked Successfully</h2>";
            echo "<p>Student ID: $student_id</p>";

        } else {
            echo "Error: " . $conn->error;
        }

    } else {
        echo "<h3 style='color:red;'>❌ Invalid Token</h3>";
    }

} else {
    echo "<h3 style='color:red;'>❌ No Token Provided</h3>";
}
?>