<?php


$host = getenv("zephyr.proxy.rlwy.net");     
$user = getenv("root");          
$password = getenv("ghqjoJkRJyQkpxFDFnxlImvcemmUveKg");         
$dbname = getenv("attendance_system");  
$port = getenv("36443");
// Create connection
$conn = new mysqli($host, $user, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional success message (for testing)
// echo "Connected successfully";

?>