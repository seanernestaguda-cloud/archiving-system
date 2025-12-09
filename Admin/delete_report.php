<?php
include('connection.php');
session_start();

$report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;

if ($report_id > 0) {
    // Fetch the report title before deleting
    $title_stmt = $conn->prepare("SELECT report_title FROM fire_incident_reports WHERE report_id = ?");
    $title_stmt->bind_param("i", $report_id);
    $title_stmt->execute();
    $title_stmt->bind_result($report_title);
    $title_stmt->fetch();
    $title_stmt->close();

    $sql = "UPDATE fire_incident_reports SET deleted_at = NOW() WHERE report_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $report_id);
    if ($stmt->execute()) {
        // Log the deletion with report_title
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (username, action, report_id, details, timestamp) VALUES (?, 'delete', ?, ?, NOW())");
        $details = "Deleted Report: $report_title";
        $log_stmt->bind_param("sis", $_SESSION['username'], $report_id, $details);
        $log_stmt->execute();
        $log_stmt->close();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid report ID']);
}
$conn->close();
