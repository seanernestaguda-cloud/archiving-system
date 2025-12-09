<?php
// Make sure the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $data = json_decode(file_get_contents('php://input'), true);

    // Database connection
    include('connection.php');

    if (!$conn) {
        echo json_encode(["success" => false, "message" => "Connection failed: " . mysqli_connect_error()]);
        exit;
    }

    // Prepare and bind the SQL statement
    $stmt = $conn->prepare("UPDATE fire_safety_inspection_certificate SET permit_name = ?, inspection_establishment = ?, owner = ?, inspection_address = ?, inspection_date = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $data['permit_name'], $data['inspection_establishment'], $data['owner'], $data['inspection_address'], $data['inspection_date'], $data['id']);

    // Execute the query
    if ($stmt->execute()) {
        // Return success as JSON response
        echo json_encode(["success" => true, "message" => "Record updated successfully"]);
    } else {
        // Return error as JSON response
        echo json_encode(["success" => false, "message" => "Error updating record: " . $conn->error]);
    }

    // Close the connection
    $stmt->close();
    $conn->close();
}
?>
