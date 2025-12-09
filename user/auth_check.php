<?php
session_start();
include('connection.php');

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $user_check = $conn->prepare("SELECT * FROM users WHERE username = ? AND status = 'verified' LIMIT 1");
    $user_check->bind_param('s', $username);
    $user_check->execute();
    $result = $user_check->get_result();
    if ($result->num_rows === 0) {
        session_destroy();
        header("Location:userlogin.php?error=account_inactive");
        exit();
    }
    $user_check->close();
} else {
    header("Location:userlogin.php");
    exit();
}
?>