<?php
session_start();
require_once '../../models/User.php';

$userModel = new User();
$userData = $userModel->getUserProfile($_SESSION['user_id']);

if (!$userData) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../public/css/style.css">
    <title>User Profile</title>
</head>
<body>
    <?php include '../layout.php'; ?>
    
    <div class="container">
        <h1>User Profile</h1>
        <div class="profile-info">
            <p><strong>Username:</strong> <?php echo htmlspecialchars($userData['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($userData['email']); ?></p>
            <p><strong>Joined on:</strong> <?php echo htmlspecialchars($userData['created_at']); ?></p>
        </div>
        <a href="edit.php" class="btn">Edit Profile</a>
        <a href="logout.php" class="btn">Logout</a>
    </div>
</body>
</html>