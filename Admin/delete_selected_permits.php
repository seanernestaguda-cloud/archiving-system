<?php

include('connection.php');
session_start(); // Needed to access $_SESSION['username']
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['permit_ids']) || !is_array($data['permit_ids'])) {
    echo json_encode(['success' => false, 'error' => 'No IDs provided']);
    exit;
}

$ids = array_map('intval', $data['permit_ids']); // Use intval if IDs are numeric
$idList = implode(',', $ids);

// Fetch permit names before deletion
$permit_names = [];
if (count($ids) > 0) {
    $sql_fetch_names = "SELECT id, permit_name FROM fire_safety_inspection_certificate WHERE id IN ($idList)";
    $result_names = mysqli_query($conn, $sql_fetch_names);
    while ($row = mysqli_fetch_assoc($result_names)) {
        $permit_names[$row['id']] = $row['permit_name'];
    }
}

$sql = "UPDATE fire_safety_inspection_certificate SET deleted_at = NOW() WHERE id IN ($idList)";
if (mysqli_query($conn, $sql)) {
    // Log activity for each deleted permit
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown';
    foreach ($ids as $deleted_id) {
        $permit_name = isset($permit_names[$deleted_id]) ? $permit_names[$deleted_id] : 'Unknown Permit';
        $details = "Deleted Fire Safety Inspection Report: " . $permit_name;
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (username, action, report_id, details) VALUES (?, 'delete', ?, ?)");
        $log_stmt->bind_param('sis', $username, $deleted_id, $details);
        $log_stmt->execute();
        $log_stmt->close();
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
mysqli_close($conn);
?>