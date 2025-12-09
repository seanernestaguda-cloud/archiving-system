<?php
session_start();
// Connect to the database
include('connection.php');


// Get the report ID from the request (maybe via GET or POST)
$report_id = $_GET['report_id'];

// Query to get the report data
$query = "SELECT * FROM reports WHERE report_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if the report exists
if ($result->num_rows > 0) {
    $report = $result->fetch_assoc();

    // Format the report data
    $report = [
        'id' => $report['report_id'],
        'report_title' => $report['report_title'],
        'fire_location' => $report['fire_location'],
        'incident_date' => $report['incident_date'],
        'establishment' => $report['establishment'],
        'victims' => explode(", ", $report['victims']), // Split victims into an array
        'property_damage' => $report['property_damage'],
        'fire_types' => $report['fire_types'],
        'fire_cause' => $report['fire_cause']
    ];

    // Send the response as JSON
    echo json_encode($report);
} else {
    // If no report found, send an error message
    echo json_encode(['error' => 'Report not found']);
}

$stmt->close();
$conn->close();
?>
