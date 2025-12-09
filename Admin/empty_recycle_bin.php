<?php
include('connection.php');

// Delete all soft-deleted fire incident reports
$conn->query("DELETE FROM fire_incident_reports WHERE deleted_at IS NOT NULL");

// Delete all soft-deleted fire safety permits
$conn->query("DELETE FROM fire_safety_inspection_certificate WHERE deleted_at IS NOT NULL");

// Optionally, log the action or handle errors here

// Redirect back with success parameter
header("Location: recycle_bin.php?success=empty");
exit;