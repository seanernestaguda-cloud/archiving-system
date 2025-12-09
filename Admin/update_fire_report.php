<?php
session_start();
include('connection.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode JSON payload
    $data = json_decode(file_get_contents('php://input'), true);

    // Check if the necessary fields are present in the POST data
    if (!isset($data['report_id'], $data['report_title'], $data['fire_location'], $data['incident_date'], $data['establishment'], $data['victims'], $data['property_damage'], $data['fire_types'], $data['fire_cause'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Extract variables
    $report_id = $data['report_id'];
    $report_title = $data['report_title'];
    $fire_location = $data['fire_location'];
    $incident_date = $data['incident_date'];
    $establishment = $data['establishment'];
    $victims = $data['victims'];
    $property_damage = $data['property_damage'];
    $fire_types = $data['fire_types'];
    $fire_cause = $data['fire_cause'];

    // Sanitize input (basic sanitization - adjust as necessary)
    $report_title = mysqli_real_escape_string($conn, $report_title);
    $fire_location = mysqli_real_escape_string($conn, $fire_location);
    $incident_date = mysqli_real_escape_string($conn, $incident_date);
    $establishment = mysqli_real_escape_string($conn, $establishment);
    $victims = mysqli_real_escape_string($conn, $victims);
    $property_damage = mysqli_real_escape_string($conn, $property_damage);
    $fire_types = mysqli_real_escape_string($conn, $fire_types);
    $fire_cause = mysqli_real_escape_string($conn, $fire_cause);

    // Update query
    $query = "UPDATE fire_incident_reports 
              SET report_title = ?, 
                  fire_location = ?, 
                  incident_date = ?, 
                  establishment = ?, 
                  victims = ?, 
                  property_damage = ?, 
                  fire_types = ?, 
                  fire_cause = ? 
              WHERE report_id = ?";

    // Prepare and bind parameters
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "ssssssssi", 
        $report_title, 
        $fire_location, 
        $incident_date, 
        $establishment, 
        $victims, 
        $property_damage, 
        $fire_types, 
        $fire_cause, 
        $report_id
    );

    // Check for errors in the query or parameters
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Prepared statement error: ' . $conn->error]);
        exit;
    }

    // Execute the statement
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed. Please try again later.']);
    }


    $stmt->close();
    $conn->close();
}
?>
