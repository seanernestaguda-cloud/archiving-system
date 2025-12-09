<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

include('connection.php');

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

$report_id = isset($data['report_id']) ? intval($data['report_id']) : 0;
$report_type = isset($data['report_type']) ? $data['report_type'] : '';

$allowed_types = [
    'narrative_report',
    'progress_report',
    'final_investigation_report'
];

if ($report_id <= 0 || !in_array($report_type, $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// Get the file path from the database
$stmt = mysqli_prepare($conn, "SELECT $report_type FROM fire_incident_reports WHERE report_id = ?");
mysqli_stmt_bind_param($stmt, "i", $report_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if (!$row || empty($row[$report_type])) {
    echo json_encode(['success' => false, 'error' => 'File not found']);
    exit;
}

$file_path = $row[$report_type];

// Remove the file from the server
if (file_exists($file_path)) {
    @unlink($file_path);
}

// Update the database to remove the file reference
$stmt = mysqli_prepare($conn, "UPDATE fire_incident_reports SET $report_type = NULL WHERE report_id = ?");
mysqli_stmt_bind_param($stmt, "i", $report_id);
if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database update failed']);
}

mysqli_close($conn);
?>