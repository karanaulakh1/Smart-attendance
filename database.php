<?php

$conn = new mysqli(
    "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
    "JmzF5kyqRvEwnxn.root",
    "nRavqZA6tYW3LGNf",
    "attendance_system",
    4000
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected!";
?>