<?php
include('connection.php'); // Ensure this is the correct path

// Assume you already have a session started and username set
session_start();
$username = $_SESSION['username']; // Get the logged-in username

// Prepare and execute the SQL query
$query = "SELECT * FROM users WHERE username = '$username'";
$result = mysqli_query($conn, $query);

// Check for errors in the query execution
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Fetch and display user information
$user = mysqli_fetch_assoc($result);
if ($user) {
    // Display user information
    echo "Username: " . $user['username'];
    echo "Name: " . $user['name'];
    echo "Email: " . $user['email'];
    // Add other fields as necessary
} else {
    echo "No user found.";
}

// Close the connection
mysqli_close($conn);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="profilestyle.css"> <!-- Link to your profile CSS -->
    <title>User Profile</title>
</head>
<body>
    <div class="profile-container">
        <h2>Profile of <?php echo htmlspecialchars($user['username']); ?></h2>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p> <!-- Example field -->
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p> <!-- Example field -->
        <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p> <!-- Example field -->
        <a href="admindashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>
