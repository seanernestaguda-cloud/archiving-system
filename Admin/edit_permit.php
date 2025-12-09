<?php
include('connection.php');
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['permit_id'])) {
    $permit_id = $_POST['permit_id'];
    $permit_name = $_POST['permit_name'];
    $inspection_establishment = $_POST['inspection_establishment'];
    $owner = $_POST['owner'];
    $inspection_address = $_POST['inspection_address'];
    $inspection_date = $_POST['inspection_date'];

    $query = "UPDATE fire_safety_inspection_certificate SET permit_name = ?, inspection_establishment = ?, owner = ?, inspection_address = ?, inspection_date = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssssi', $permit_name, $inspection_establishment, $owner, $inspection_address, $inspection_date, $permit_id);
    
    if ($stmt->execute()) {
        echo "Permit updated successfully";
    } else {
        echo "Error updating permit";
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>
