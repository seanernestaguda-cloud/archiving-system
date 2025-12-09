<?php
// export_users_excel.php
include '../connection.php';

// Fetch users
$sql = "SELECT first_name, middle_name, last_name, username, user_type, department, status FROM users";
$result = $conn->query($sql);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=users.csv');

$output = fopen('php://output', 'w');
// Output header row
fputcsv($output, ['Name', 'Username', 'User Role', 'Department', 'Status']);

while ($row = $result->fetch_assoc()) {
    $fullName = $row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'];
    fputcsv($output, [
        $fullName,
        $row['username'],
        $row['user_type'],
        $row['department'],
        $row['status']
    ]);
}
fclose($output);
exit;
