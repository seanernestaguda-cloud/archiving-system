<?php
include('connection.php');
$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = '';
$params = [];
$types = '';

if ($search !== '') {
    $search_sql = "WHERE activity_logs.username LIKE ? OR activity_logs.action LIKE ? OR activity_logs.details LIKE ? OR activity_logs.id LIKE ? OR activity_logs.report_id LIKE ? OR users.user_type LIKE ? OR users.department LIKE ?";
    $search_param = '%' . $search . '%';
    $params = [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param];
    $types = 'sssssss';

    $query = "SELECT activity_logs.*, users.department, users.user_type AS user_type FROM activity_logs LEFT JOIN users ON activity_logs.username = users.username $search_sql ORDER BY activity_logs.timestamp DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $types .= 'ii';
    $stmt_logs = $conn->prepare($query);
    $stmt_logs->bind_param($types, ...$params);
    $stmt_logs->execute();
    $result = $stmt_logs->get_result();
    $stmt_logs->close();
} else {
    $query = "SELECT activity_logs.*, users.department, users.user_type AS user_type FROM activity_logs LEFT JOIN users ON activity_logs.username = users.username ORDER BY activity_logs.timestamp DESC LIMIT $per_page OFFSET $offset";
    $result = mysqli_query($conn, $query);
}

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['timestamp']) . '</td>';
        echo '<td>' . htmlspecialchars($row['username']) . '</td>';
        echo '<td>' . (isset($row['user_type']) && $row['user_type'] !== '' ? htmlspecialchars($row['user_type']) : '<span style="color:#888;">N/A</span>') . '</td>';
        echo '<td>' . (isset($row['department']) && $row['department'] !== '' ? htmlspecialchars($row['department']) : '<span style="color:#888;">N/A</span>') . '</td>';
        echo '<td class="' . (
            strtolower($row['action']) === 'delete' ? 'action-delete' : (strtolower($row['action']) === 'update' ? 'action-update' : (strtolower($row['action']) === 'create' ? 'action-create' : (strtolower($row['action']) === 'download' ? 'action-download' : (strtolower($row['action']) === 'restore' ? 'action-restore' : ''))))) . '">' .
            htmlspecialchars($row['action']) . '</td>';
        echo '<td>' . (!empty($row['id']) ? htmlspecialchars($row['id']) : (!empty($row['report_id']) ? htmlspecialchars($row['report_id']) : '')) . '</td>';
        echo '<td>' . htmlspecialchars($row['details']) . '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="7" style="text-align:center;">No records found.</td></tr>';
}
