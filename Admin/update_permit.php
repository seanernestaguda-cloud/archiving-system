<?php
session_start();
include('connection.php');

// Check if the data is provided via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data and decode it into an array
    $inputData = json_decode(file_get_contents('php://input'), true);

    // Extract the data
    $permitId = $inputData['id'];
    $permitName = $inputData['permit_name'];
    $inspectionEstablishment = $inputData['inspection_establishment'];
    $owner = $inputData['owner'];
    $inspectionAddress = $inputData['inspection_address'];
    $inspectionDate = $inputData['inspection_date'];
    $inspectionPurpose = $inputData['inspection_purpose'];
    $inspectedBy = $inputData['inspected_by'];

    // Radio button values
    $fireAlarms = $inputData['fire_alarms'];
    $fireExtinguishers = $inputData['fire_extinguishers'];
    $emergencyExits = $inputData['emergency_exits'];
    $sprinklerSystems = $inputData['sprinkler_systems'];
    $fireDrills = $inputData['fire_drills'];
    $exitSigns = $inputData['exit_signs'];
    $electricalWiring = $inputData['electrical_wiring'];
    $emergencyEvacuations = $inputData['emergency_evacuations'];

    // Update the database
    $query = "UPDATE fire_safety_inspection_certificate SET 
              permit_name = '$permitName', 
              inspection_establishment = '$inspectionEstablishment', 
              owner = '$owner', 
              inspection_address = '$inspectionAddress', 
              inspection_date = '$inspectionDate', 
              inspection_purpose = '$inspectionPurpose',
              fire_alarms = '$fireAlarms',
              fire_extinguishers = '$fireExtinguishers',
              emergency_exits = '$emergencyExits',
              sprinkler_systems = '$sprinklerSystems',
              fire_drills = '$fireDrills',
              exit_signs = '$exitSigns',
              electrical_wiring = '$electricalWiring',
              emergency_evacuations = '$emergencyEvacuations',
              inspected_by = '$inspectedBy'
              WHERE id = $permitId";

    if (mysqli_query($conn, $query)) {
        // Fetch the updated permit
        $updatedQuery = "SELECT * FROM fire_safety_inspection_certificate WHERE id = $permitId";
        $result = mysqli_query($conn, $updatedQuery);
        $updatedPermit = mysqli_fetch_assoc($result);

        // Return the updated data as a JSON response
        echo json_encode([
            'success' => true,
            'message' => 'Permit updated successfully.',
            'updatedPermit' => $updatedPermit
        ]);        
    } else {
        // Return error response
        echo json_encode(['success' => false, 'message' => 'Failed to update permit.']);
    }

    mysqli_close($conn);
}
?>
