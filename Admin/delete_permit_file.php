<?php
session_start();
include('connection.php');

if (!isset($_SESSION['username'])) {
    header("Location: adminlogin.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['field'])) {
    $id = intval($_GET['id']);
    $field = $_GET['field'];

    // List of allowed fields for security
    $allowed_fields = [
        'application_form',
        'proof_of_ownership',
        'building_plans',
        'fire_safety_inspection_checklist',
        'fire_safety_inspection_certificate',
        'occupancy_permit',
        'business_permit'
    ];

    if (!in_array($field, $allowed_fields)) {
        die("Invalid field.");
    }

    // Get the file path
    $stmt = $conn->prepare("SELECT $field FROM fire_safety_inspection_certificate WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($file_path);
    $stmt->fetch();
    $stmt->close();

    // Delete the file from the server
    if ($file_path && file_exists($file_path)) {
        unlink($file_path);
    }

    // Set the field to NULL in the database
    $sql = "UPDATE fire_safety_inspection_certificate SET $field = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Redirect back to the permit view page
    header("Location: view_permit.php?id=$id");
    exit();
} else {
    echo "Invalid request.";
}
?>