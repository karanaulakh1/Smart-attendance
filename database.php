<?php


$host = getenv("gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com");     
$user = getenv("JmzF5kyqRvEwnxn.root");          
$password = getenv("nRavqZA6tYW3LGNf");         
$dbname = getenv("attendance_system");  
$port = getenv("4000");
// Create connection
$conn = new mysqli($host, $user, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional success message (for testing)
// echo "Connected successfully";

?>