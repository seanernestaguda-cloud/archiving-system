<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: userlogin.php"); // Redirect to login if not logged in
    exit();
}

include('connection.php'); // Include database connection

// Check if the 'id' parameter is provided
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Prepare a DELETE query to remove the record
    $query = "DELETE FROM archives WHERE id = ?";
    
    if ($stmt = mysqli_prepare($conn, $query)) {
        // Bind the parameter and execute the statement
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            // If the query was successful, redirect to the archives page with a success message
            $_SESSION['message'] = "Archive deleted successfully!";
            header("Location: myarchives.php");
            exit();
        } else {
            // If there was an error with execution
            $_SESSION['error'] = "Error deleting archive. Please try again.";
            header("Location: myarchives.php");
            exit();
        }

        // Close the statement
        mysqli_stmt_close($stmt);
    } else {
        // If there was an error with the prepared statement
        $_SESSION['error'] = "Error preparing delete query.";
        header("Location: myarchives.php");
        exit();
    }
} else {
    // If the 'id' parameter is not set, redirect to archives page with an error
    $_SESSION['error'] = "Archive ID is missing.";
    header("Location: myarchives.php");
    exit();
}

// Close the database connection
mysqli_close($conn);
?>
