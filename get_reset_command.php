<?php
/**
 * get_reset_command.php
 * ESP32 polls this every loop, same pattern as get_enroll.php
 * Returns "1" if admin has requested a sensor wipe, "0" otherwise
 */

include("database.php");

$result = mysqli_query($conn,
    "SELECT * FROM reset_requests WHERE status='pending' ORDER BY id DESC LIMIT 1"
);

if($result && mysqli_num_rows($result) > 0){
    echo "1";
} else {
    echo "0";
}
?>