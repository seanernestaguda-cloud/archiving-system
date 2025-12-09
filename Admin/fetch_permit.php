<?php
// Include your database connection file (adjust the path as necessary)
include('connection.php');

// Get the permit ID from the query string
$permitId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($permitId > 0) {
    // Prepare your SQL query to fetch the permit details by ID
    $query = "SELECT * FROM fire_safety_inspection_certificate WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $permitId);

    if ($stmt->execute()) {
        // Get the result
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Fetch the permit data
            $permit = $result->fetch_assoc();
            
            // Return the permit data as JSON
            echo json_encode($permit);
        } else {
            // If no permit found
            echo json_encode(['error' => 'Permit not found']);
        }

        // Close the statement
        $stmt->close();
    } else {
        // Error executing the query
        echo json_encode(['error' => 'Error executing query']);
    }
} else {
    // Invalid permit ID
    echo json_encode(['error' => 'Invalid permit ID']);
}

// Close the database connection
$conn->close();
?>
