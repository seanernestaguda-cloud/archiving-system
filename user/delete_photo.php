<?php
session_start();
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $photoPath = $input['path'];
    $photoIndex = $input['index'];
    $reportId = $input['report_id'];

    if (file_exists($photoPath)) {
        unlink($photoPath); // Delete the file from the server
    }

    // Fetch the report's documentation photos
    $query = "SELECT documentation_photos FROM fire_incident_reports WHERE report_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $reportId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $report = mysqli_fetch_assoc($result);

    if ($report) {
        $photos = explode(',', $report['documentation_photos']);
        unset($photos[$photoIndex]); // Remove the deleted photo

        // Update the database with the updated photos list
        $updatedPhotos = implode(',', array_filter($photos));
        $updateQuery = "UPDATE fire_incident_reports SET documentation_photos = ? WHERE report_id = ?";
        $updateStmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($updateStmt, "si", $updatedPhotos, $reportId);

        if (mysqli_stmt_execute($updateStmt)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update database.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Report not found.']);
    }
}
