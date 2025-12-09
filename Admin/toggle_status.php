<?php
// toggle_status.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: adminlogin.php");
    exit();
}

// Include database connection
include 'connection.php';

// Check if 'id' is set in the URL
if (isset($_GET['id'])) {
    $userId = $_GET['id'];

    // Get current status of the user
    $query = "SELECT status FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $currentStatus = $row['status'];

        // Toggle the status
        $newStatus = ($currentStatus === 'verified') ? 'not verified' : 'verified';

        // Update the status in the database
        $updateQuery = "UPDATE users SET status = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("si", $newStatus, $userId);
        $updateStmt->execute();

        $_SESSION['message'] = "User status updated to $newStatus.";
    } else {
        $_SESSION['message'] = "User not found.";
    }
} else {
    $_SESSION['message'] = "Invalid user ID.";
}

// Redirect back to manage users page
header("Location: manageuser.php");
exit();
?>