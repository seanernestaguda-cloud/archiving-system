<?php
session_start();
session_destroy(); // Destroy session to log out
header("Location: adminlogin.php"); // Redirect to login page
exit();
?>
