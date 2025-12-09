<?php

include('connection.php');
session_start(); // Needed for $_SESSION['username']
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['report_ids']) || !is_array($data['report_ids'])) {
    echo json_encode(['success' => false, 'error' => 'No IDs provided']);
    exit;
}

$ids = array_map('intval', $data['report_ids']); // Use intval if IDs are numeric
$idList = implode(',', $ids);

// Fetch all report titles before deleting
$titles = [];
if (count($ids) > 0) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("SELECT report_id, report_title FROM fire_incident_reports WHERE report_id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $titles[$row['report_id']] = $row['report_title'];
    }
    $stmt->close();
}

$sql = "UPDATE fire_incident_reports SET deleted_at = NOW() WHERE report_id IN ($idList)";
if (mysqli_query($conn, $sql)) {
    // Log activity for each deleted report
    foreach ($ids as $report_id) {
        $report_title = isset($titles[$report_id]) ? $titles[$report_id] : '';
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (username, action, report_id, details, timestamp) VALUES (?, ?, ?, ?, NOW())");
        $action = "delete";
        $details = "Delete Report: $report_title";
        $log_stmt->bind_param("ssis", $_SESSION['username'], $action, $report_id, $details);
        $log_stmt->execute();
        $log_stmt->close();
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
mysqli_close($conn);
?>