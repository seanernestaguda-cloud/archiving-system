<?php
session_start();
include('connection.php');

if (isset($_SESSION['username'])) {
    // Allow default admin without DB check
    if ($_SESSION['username'] === 'admin') {
        return;
    }
    $username = $_SESSION['username'];
    $user_check = $conn->prepare("SELECT * FROM users WHERE username = ? AND status = 'verified' LIMIT 1");
    $user_check->bind_param('s', $username); // 's' for string
    $user_check->execute();
    $result = $user_check->get_result();
    if ($result->num_rows === 0) {
        session_destroy();
        header("Location:adminlogin.php?error=account_inactive");
        exit();
    }
    $user_check->close();
} else {
    header("Location:adminlogin.php");
    exit();
}
