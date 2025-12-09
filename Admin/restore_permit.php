<?php
include('connection.php');
include('auth_check.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Fetch permit name
    $permit_name = '';
    $name_stmt = $conn->prepare("SELECT permit_name FROM fire_safety_inspection_certificate WHERE id = ?");
    $name_stmt->bind_param("i", $id);
    $name_stmt->execute();
    $name_stmt->bind_result($permit_name);
    $name_stmt->fetch();
    $name_stmt->close();

    $stmt = $conn->prepare("UPDATE fire_safety_inspection_certificate SET deleted_at = NULL WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Log the restore action
    $username = $_SESSION['username'];
    $action = 'restore';
    $details = "Restored: $permit_name";
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (username, action, id, details) VALUES (?, ?, ?, ?)");
    $log_stmt->bind_param("ssis", $username, $action, $id, $details);
    $log_stmt->execute();
    $log_stmt->close();
}

header("Location: recycle_bin.php?success=restore");
exit;