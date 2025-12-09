<?php
include('connection.php');
include('auth_check.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'])) {
    $report_id = intval($_POST['report_id']);

    // Permanently delete the report
    $stmt = $conn->prepare("DELETE FROM fire_incident_reports WHERE report_id = ?");
    $stmt->bind_param("i", $report_id);

 if ($stmt->execute()) {
    $stmt->close();
    header("Location: recycle_bin.php?success=delete");
    exit;
} else {
    $stmt->close();
    header("Location: recycle_bin.php?error=Failed+to+delete+report");
    exit;
}
} else {
    header("Location: recycle_bin.php?error=Invalid+request");
    exit;
}