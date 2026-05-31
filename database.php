<?php

$conn = mysqli_init();

$conn->real_connect(
    "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
    "JmzF5kyqRvEwnxn.root",
    "nRavqZA6tYW3LGNf",
    "attendance_system",
    4000,
    NULL,
    MYSQLI_CLIENT_SSL
);

echo "Connected!";
?>