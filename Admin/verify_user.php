<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: adminlogin.php"); // Redirect to login if not logged in
    exit();
}

// Include database connection
include 'connection.php';

// Check if the user ID is provided
if (isset($_GET['id'])) {
    $userId = intval($_GET['id']); // Get the user ID and ensure it's an integer

    // Update the user status to 'verified'
    $query = "UPDATE users SET status = 'verified' WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        $_SESSION['message'] = "User verified successfully!";
    } else {
        $_SESSION['message'] = "Error verifying user.";
    }

    $stmt->close();
} else {
    $_SESSION['message'] = "Invalid user ID.";
}

header("Location: manageuser.php?status=verified");
exit();
?>
