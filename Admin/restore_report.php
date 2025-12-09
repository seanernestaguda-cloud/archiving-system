<?php
include('connection.php');
session_start();

$report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;

if ($report_id > 0) {
    // Restore by setting deleted_at to NULL
    $stmt = $conn->prepare("UPDATE fire_incident_reports SET deleted_at = NULL WHERE report_id = ?");
    $stmt->bind_param("i", $report_id);
    $success = $stmt->execute();
    $stmt->close();

    // Log restore action (optional, if you have activity_logs table)
    if ($success) {
        $title_stmt = $conn->prepare("SELECT report_title FROM fire_incident_reports WHERE report_id = ?");
        $title_stmt->bind_param("i", $report_id);
        $title_stmt->execute();
        $title_stmt->bind_result($report_title);
        $title_stmt->fetch();
        $title_stmt->close();

        $log_stmt = $conn->prepare("INSERT INTO activity_logs (username, action, report_id, details, timestamp) VALUES (?, ?, ?, ?, NOW())");
        $action = "restore";
        $details = "Restored: $report_title";
        $log_stmt->bind_param("ssis", $_SESSION['username'], $action, $report_id, $details);
        $log_stmt->execute();
        $log_stmt->close();
    }

    // Redirect to recycle_bin.php with success message
    header("Location: recycle_bin.php?success=restore");
    exit;
} else {
    // Redirect with error or handle as needed
    header("Location: recycle_bin.php?success=error");
    exit;
}
$conn->close();
?>