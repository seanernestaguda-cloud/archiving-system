<?php
include('connection.php');

// Query to get all archive records
$query = "SELECT * FROM archives ORDER BY time_and_date DESC";
$result = mysqli_query($conn, $query);

// Fetch all records into an array
$archives = [];
while ($row = mysqli_fetch_assoc($result)) {
    $archives[] = $row;
}

// Return the records as JSON
echo json_encode($archives);

// Close the database connection
mysqli_close($conn);
?>
