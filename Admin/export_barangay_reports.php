<?php
include('connection.php');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$selected_barangay = isset($_POST['barangay']) ? $_POST['barangay'] : '';

if ($selected_barangay) {
    $query = "SELECT report_id, report_title, CONCAT(street, ', ', purok, ', ', fire_location) AS fire_location_combined, 
                     incident_date, establishment, victims, firefighters, property_damage, fire_types 
              FROM fire_incident_reports 
              WHERE fire_location = ?
              ORDER BY incident_date ASC";
    
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param('s', $selected_barangay);
        $stmt->execute();
        $result = $stmt->get_result();
        $reports = mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
        die("Query preparation failed: " . $conn->error);
    }
} else {
    die("No barangay selected.");
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=reports_per_barangay.xls");

// Generate Excel content
echo "Report ID\tReport Title\tLocation\tTime and Date\tEstablishment\tVictims\tFirefighters\tDamage to Property\tFire Type\n";
foreach ($reports as $row) {
    // Format the date
    $formatted_date = date('Y-m-d H:i:sa', strtotime($row['incident_date'])); // Adjust format as needed
    echo "{$row['report_id']}\t{$row['report_title']}\t{$row['fire_location_combined']}\t$formatted_date\t{$row['establishment']}\t";
    echo count(explode(',', $row['victims'])) . "\t{$row['firefighters']}\t{$row['property_damage']}\t{$row['fire_types']}\n";
}

mysqli_close($conn);
exit;
?>
