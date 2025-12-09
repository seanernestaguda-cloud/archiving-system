<?php
include('connection.php');
include('auth_check.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_ids'], $_POST['selected_types'])) {
    $ids = $_POST['selected_ids'];
    $types = $_POST['selected_types'];
    $username = $_SESSION['username'];

    foreach ($ids as $index => $id) {
        $type = $types[$index];

        // Fetch user_type for the current user
        $user_type = '';
        $type_stmt = $conn->prepare("SELECT user_type FROM users WHERE username = ? LIMIT 1");
        $type_stmt->bind_param('s', $username);
        $type_stmt->execute();
        $type_stmt->bind_result($user_type);
        $type_stmt->fetch();
        $type_stmt->close();

        if ($type === 'Fire Incident Report') {
            // Restore incident report
            $stmt = $conn->prepare("UPDATE fire_incident_reports SET deleted_at = NULL WHERE report_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // Fetch report title
            $stmt_title = $conn->prepare("SELECT report_title FROM fire_incident_reports WHERE report_id = ?");
            $stmt_title->bind_param("i", $id);
            $stmt_title->execute();
            $stmt_title->bind_result($report_title);
            $stmt_title->fetch();
            $stmt_title->close();

            // Log activity
            $details = "Restored Fire Incident Report: $report_title";
            $stmt_log = $conn->prepare("INSERT INTO activity_logs (username, user_type, action, report_id, details) VALUES (?, ?, 'restore', ?, ?)");
            $stmt_log->bind_param("ssis", $username, $user_type, $id, $details);
            $stmt_log->execute();
            $stmt_log->close();
        } elseif ($type === 'Fire Inspection Report') {
            // Restore inspection certificate
            $stmt = $conn->prepare("UPDATE fire_safety_inspection_certificate SET deleted_at = NULL WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // Fetch permit name
            $stmt_name = $conn->prepare("SELECT permit_name FROM fire_safety_inspection_certificate WHERE id = ?");
            $stmt_name->bind_param("i", $id);
            $stmt_name->execute();
            $stmt_name->bind_result($permit_name);
            $stmt_name->fetch();
            $stmt_name->close();

            // Log activity
            $details = "Restored Fire Inspection Report: $permit_name";
            $stmt_log = $conn->prepare("INSERT INTO activity_logs (username, user_type, action, id, details) VALUES (?, ?, 'restore', ?, ?)");
            $stmt_log->bind_param("ssis", $username, $user_type, $id, $details);
            $stmt_log->execute();
            $stmt_log->close();
        }
    }

    header("Location: recycle_bin.php?success=restore_selected");
    exit();
} else {
    header("Location: recycle_bin.php");
    exit();
}
