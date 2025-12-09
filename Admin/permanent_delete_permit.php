<?php
include('connection.php');
include('auth_check.php');


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Permanently delete the permit
    $stmt = $conn->prepare("DELETE FROM fire_safety_inspection_certificate WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: recycle_bin.php?success=delete");
        exit;
    } else {
        $stmt->close();
        header("Location: recycle_bin.php?error=Failed+to+delete+permit");
        exit;
    }
} else {
    header("Location: recycle_bin.php?error=Invalid+request");
    exit;
}