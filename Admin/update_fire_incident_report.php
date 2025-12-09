<?php
session_start();
include('connection.php');  // Include your DB connection file

// Check if data is received from the client-side (JavaScript)
$data = json_decode(file_get_contents("php://input"), true);

// Extract data from the received JSON
$report_id = $data['report_id'];
$report_title = $data['report_title'];
$fire_location = $data['fire_location'];
$incident_date = $data['incident_date'];
$establishment = $data['establishment'];
$victims = $data['victims'];
$property_damage = $data['property_damage'];
$fire_types = $data['fire_types'];
$fire_cause = $data['fire_cause'];

// Prepare the update query
$updateQuery = "UPDATE fire_incident_reports SET 
                report_title = ?, fire_location = ?, incident_date = ?, 
                establishment = ?, victims = ?, property_damage = ?, 
                fire_types = ?, fire_cause = ?, uploader = ?, department = ? 
                WHERE report_id = ?";
$stmtUpdate = $conn->prepare($updateQuery);

// Bind the parameters
$stmtUpdate->bind_param(
    'sssssssssss',
    $report_title,
    $fire_location,
    $incident_date,
    $establishment,
    $victims,
    $property_damage,
    $fire_types,
    $fire_cause,
    $_SESSION['username'],
    $_SESSION['department'],
    $report_id
);

// Execute the update query
if ($stmtUpdate->execute()) {
    // Store success message in the session
    $_SESSION['message'] = "Report with ID $report_id has been successfully updated.";
    $_SESSION['message_type'] = "success";  // Optional: Store the message type (success/error)
    
    // Return a success response to JavaScript
    echo json_encode(['success' => true]);
} else {
    // Return failure response to JavaScript
    echo json_encode(['success' => false, 'message' => 'Error updating report.']);
}

// Close the connection
$stmtUpdate->close();
$conn->close();
?>
