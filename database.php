<?php

date_default_timezone_set('Asia/Kolkata');

$host = "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com";
$user = "JmzF5kyqRvEwnxn.root";
$pass = "nRavqZA6tYW3LGNf";
$db   = "attendance_system";
$port = 4000;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = mysqli_init();

mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

$conn->real_connect(
    $host,
    $user,
    $pass,
    $db,
    $port,
    NULL,
    MYSQLI_CLIENT_SSL
);
?>