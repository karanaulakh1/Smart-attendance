<?php

$host = "localhost";      // Server (always localhost in XAMPP)
$user = "root";           // Default username
$password = "";           // Default password (empty in XAMPP)
$dbname = "attendance_system";  // Your database name

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional success message (for testing)
// echo "Connected successfully";

?>