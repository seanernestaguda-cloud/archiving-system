<?php
include('connection.php');
include('auth_check.php');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$allowed_sort_columns = ['report_id', 'report_title', 'incident_date', 'fire_location'];
$sort_by = isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowed_sort_columns) ? $_GET['sort_by'] : 'report_id';
$order_by = 'ASC';

$where_clauses = ["deleted_at IS NULL"];
$params = [];
$param_types = '';

if (!empty($_GET['start_month'])) {
    $start = $_GET['start_month'] . '-01 00:00:00';
    $where_clauses[] = "incident_date >= ?";
    $params[] = $start;
    $param_types .= 's';
}
if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where_clauses[] = "(report_id LIKE ? OR report_title LIKE ? OR fire_location LIKE ? OR establishment LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $param_types .= 'ssss';
}
if (!empty($_GET['end_month'])) {
    $end_month = $_GET['end_month'];
    $last_day = date('t', strtotime($end_month . '-01'));
    $end = $end_month . '-' . $last_day . ' 23:59:59';
    $where_clauses[] = "incident_date <= ?";
    $params[] = $end;
    $param_types .= 's';
}
$where_sql = '';
if ($where_clauses) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

$query = "SELECT report_id, report_title, CONCAT(street, ', ', purok, ', ', fire_location, ', ', municipality) AS fire_location_combined, incident_date, establishment, victims, firefighters, property_damage, fire_types, uploader, created_at FROM fire_incident_reports $where_sql ORDER BY $sort_by $order_by";

if ($where_clauses) {
    $stmt = $conn->prepare($query);
    if ($param_types) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $reports = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = mysqli_query($conn, $query);
    $reports = mysqli_fetch_all($result, MYSQLI_ASSOC);
}
mysqli_close($conn);

// Output HTML table (no action buttons, no checkboxes)
echo '<table class="archive-table" style="width:100%;border-collapse:collapse;">';
echo '<thead><tr>';
echo '<th>Report ID</th>';
echo '<th>Report Title</th>';
echo '<th>Location</th>';
echo '<th>Time and Date of Fire</th>';
echo '<th>Establishment</th>';
echo '<th>Casualties</th>';
echo '<th>Damage to Property</th>';
echo '<th>Cause of Fire</th>';
echo '<th>Uploader</th>';
echo '<th>Date Created</th>';
echo '<th>Status</th>';
echo '</tr></thead><tbody>';
if (count($reports) > 0) {
    foreach ($reports as $row) {
        $victims_count = empty($row['victims']) ? 0 : substr_count($row['victims'], ',') + 1;
        $firefighters_count = empty($row['firefighters']) ? 0 : substr_count($row['firefighters'], ',') + 1;
        $casualties = $victims_count + $firefighters_count;
        $is_complete = true;
        $required_fields = [
            $row['report_title'],
            $row['fire_location_combined'],
            $row['incident_date'],
            $row['establishment'],
            $row['property_damage'],
            $row['fire_types'],
            $row['uploader'],
            $row['created_at']
        ];
        foreach ($required_fields as $field) {
            if (!isset($field) || trim($field) === '' || $field === ', , , ') {
                $is_complete = false;
                break;
            }
        }
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['report_id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['report_title']) . '</td>';
        echo '<td>' . htmlspecialchars($row['fire_location_combined']) . '</td>';
        echo '<td>' . htmlspecialchars($row['incident_date']) . '</td>';
        echo '<td>' . htmlspecialchars($row['establishment']) . '</td>';
        echo '<td>' . $casualties . '</td>';
        echo '<td>' . htmlspecialchars('₱' . $row['property_damage']) . '</td>';
        echo '<td>' . (empty($row['fire_types']) ? 'Under Investigation' : htmlspecialchars($row['fire_types'])) . '</td>';
        echo '<td>' . htmlspecialchars($row['uploader']) . '</td>';
        echo '<td>' . (!empty($row['created_at']) ? htmlspecialchars($row['created_at']) : 'N/A') . '</td>';
        echo '<td>' . ($is_complete ? '<span style="color:green;">Complete</span>' : '<span style="color:orange;">In Progress</span>') . '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="11" style="text-align:center;">No reports found.</td></tr>';
}
echo '</tbody></table>';
