<?php
session_start();
include('connection.php');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_GET['id'])) {
    $permitId = intval($_GET['id']); // Use intval for safety
    $query = "DELETE FROM fire_safety_inspection_certificate WHERE id = $permitId";

    if (mysqli_query($conn, $query)) {
        // Log activity
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown';
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (username, action, report_id, details) VALUES (?, 'delete', ?, ?)");
        $details = "Deleted Fire Safety Inspection Report ID: " . $permitId;
        $log_stmt->bind_param('sis', $username, $permitId, $details);
        $log_stmt->execute();
        $log_stmt->close();

        $_SESSION['message'] = ['type' => 'success', 'text' => 'Permit deleted successfully!'];
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Error deleting permit: ' . mysqli_error($conn)];
    }

    mysqli_close($conn);

    header("Location: fire_safety_inspection_certificate.php");  // Redirect to the page after deletion
    exit();
}
?>