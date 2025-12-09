<?php
session_start();
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barangay_name = mysqli_real_escape_string($conn, $_POST['barangay_name']);

    if (!empty($barangay_name)) {
        $query = "INSERT INTO barangays (name) VALUES ('$barangay_name')";

        if (mysqli_query($conn, $query)) {
            $_SESSION['message'] = "Barangay added successfully!";
        } else {
            $_SESSION['message'] = "Error: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['message'] = "Barangay name cannot be empty.";
    }

    mysqli_close($conn);
    header("Location: barangay_list.php");
    exit;
}
?>
