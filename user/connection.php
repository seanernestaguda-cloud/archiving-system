<?php
// connection.php example
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bfp_archiving_system_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
