<?php
/**
 * api/get_enroll.php
 * ESP32 polls this every loop to check if admin requested an enrollment.
 * Returns the fingerprint_id (slot number) to enroll, or "0" if none pending.
 *
 * Works across ALL groups (students, teachers, employees, custom).
 */

include("../database.php");

// Read pending enroll request (set by admin clicking "Enroll" button)
$result = mysqli_query($conn,
    "SELECT * FROM enroll_requests ORDER BY id DESC LIMIT 1"
);

if($result && mysqli_num_rows($result) > 0){
    $row = mysqli_fetch_assoc($result);
    echo $row['fingerprint_id'];
} else {
    echo "0";
}
?>