<?php
session_start(); // Start the session

// Check if the admin is logged in
if (!isset($_SESSION['username'])) {
    header("Location: adminlogin.php"); // Redirect to login if not logged in
    exit();
}

// Include database connection
include 'connection.php';
include('auth_check.php');

// Get the user ID to delete from the query parameter
$user_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($user_id) {
    // Fetch user info (optional: for logging or confirmation)
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // If the deleted user is the same as the logged-in admin, destroy the session first
    if ($_SESSION['username'] === $user['username']) {
        // Destroy session if the current admin is being deleted
        session_unset();  // Unset all session variables
        session_destroy();  // Destroy the session
        setcookie(session_name(), '', time() - 3600); // Expire the session cookie
        header("Location: adminlogin.php");  // Redirect to login page after logout
        exit();
    }

    // Delete the user from the database
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Redirect back to manage users page with a status query param
    header("Location: manageuser.php?status=deleted");
    exit();
} else {
    // Redirect back to manage users page if no user ID is provided
    header("Location: manageuser.php");
    exit();
}
?>
