<?php
/**
 * confirm_reset_done.php
 * ESP32 calls this AFTER successfully wiping the sensor
 * Marks the pending reset request as completed
 */

include("database.php");

mysqli_query($conn,
    "UPDATE reset_requests SET status='completed', completed_at=NOW() WHERE status='pending'"
);

echo "OK";
?>