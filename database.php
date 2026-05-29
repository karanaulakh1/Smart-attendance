<?php


$host = getenv("mysql.railway.internal");     
$user = getenv("root");          
$password = getenv("ghqjoJkRJyQkpxFDFnxlImvcemmUveKg");         
$dbname = getenv("attendance_system");  
$port = getenv("3306");
// Create connection
$conn = new mysqli($host, $user, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional success message (for testing)
// echo "Connected successfully";

?>