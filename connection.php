<?php
$host = 'localhost';
$db = 'bfp_archiving_system_db';  // Change to your database name
$user = 'root';         // Change to your database username
$pass = '';             // Change to your database password

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// else{
//   echo "connection successful!";
// }
?>
