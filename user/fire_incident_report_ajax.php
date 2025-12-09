<?php
include('connection.php');
include('auth_check.php');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


$search = isset($_GET['search']) ? $_GET['search'] : '';
$where_clauses = ["deleted_at IS NULL"];
$params = [];
$param_types = '';

// ...existing code for preparing and executing the query...

$stmt = $conn->prepare("SELECT 
    report_id, 
    report_title, 
    CONCAT(street, ', ', purok, ', ', fire_location, ', ', municipality) AS fire_location_combined, 
    incident_date, 
    establishment, 
    victims, 
    firefighters,
    property_damage, 
    fire_types, 
    uploader, 
    department, 
    created_at, 
    caller_name,
    responding_team,
    arrival_time,
    fireout_time,
    alarm_status,
    occupancy_type, documentation_photos, narrative_report, progress_report, final_investigation_report
FROM fire_incident_reports 
" . ($where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '') . "
ORDER BY report_id ASC
LIMIT 50");
if ($param_types) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$reports = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
mysqli_close($conn);

$rows_html = '';
$filtered_count = 0;
foreach ($reports as $row) {
    $victims_count = empty($row['victims']) ? 0 : substr_count($row['victims'], ',') + 1;
    $firefighters_count = empty($row['firefighters']) ? 0 : substr_count($row['firefighters'], ',') + 1;
    $casualties = $victims_count + $firefighters_count;
    $required_fields = [
        $row['report_title'],
        $row['caller_name'],
        $row['responding_team'],
        $row['fire_location_combined'],
        $row['incident_date'],
        $row['arrival_time'],
        $row['fireout_time'],
        $row['establishment'],
        $row['alarm_status'],
        $row['occupancy_type'],
        $row['property_damage'],
        $row['fire_types'],
        $row['uploader'],
        // $row['department'],
        $row['created_at'],
        $row['documentation_photos'],
        $row['narrative_report'],
        $row['progress_report'],
        $row['final_investigation_report']
    ];
    $is_complete = true;
    foreach ($required_fields as $field) {
        if (!isset($field) || trim($field) === '' || $field === ', , , ') {
            $is_complete = false;
            break;
        }
    }
    $status = $is_complete ? 'Complete' : 'In Progress';
    $fire_types_display = empty($row['fire_types']) ? 'Under Investigation' : $row['fire_types'];

    $show_row = true;
    if (!empty($search)) {
        $search_lower = strtolower($search);
        $match = false;
        $search_fields = array(
            $row['report_id'],
            $row['report_title'],
            $row['fire_location_combined'],
            $row['incident_date'],
            $row['establishment'],
            $row['victims'],
            $row['property_damage'],
            $row['fire_types'],
            $row['uploader'],
            // $row['department'],
            $row['created_at'],
            $status,
            $fire_types_display
        );
        foreach ($search_fields as $field) {
            if (strpos(strtolower((string) $field), $search_lower) !== false) {
                $match = true;
                break;
            }
        }
        if (!$match) {
            $show_row = false;
        }
    }
    if ($show_row) {
        $filtered_count++;
        $rows_html .= '<tr id="report-row' . htmlspecialchars($row['report_id']) . '">';
        $rows_html .= '<td class="select-checkbox-cell" style="display:none;"><input type="checkbox" class="select-item" value="' . htmlspecialchars($row['report_id']) . '"></td>';
        $rows_html .= '<td>' . htmlspecialchars($row['report_id']) . '</td>';
        $rows_html .= '<td>' . htmlspecialchars($row['report_title']) . '</td>';
        $rows_html .= '<td>' . htmlspecialchars($row['fire_location_combined']) . '</td>';
        $rows_html .= '<td>' . htmlspecialchars($row['incident_date']) . '</td>';
        $rows_html .= '<td>' . htmlspecialchars($row['establishment']) . '</td>';
        $rows_html .= '<td>' . $casualties . '</td>';
        $rows_html .= '<td>' . htmlspecialchars("₱" . $row['property_damage']) . '</td>';
        $rows_html .= '<td>' . htmlspecialchars($fire_types_display) . '</td>';
        $rows_html .= '<td>' . htmlspecialchars($row['uploader']) . '</td>';
        // $rows_html .= '<td>' . htmlspecialchars(string: $row['department']) . '</td>';
        $rows_html .= '<td>' . htmlspecialchars($row['created_at']) . '</td>';
        $rows_html .= '<td>' . ($status === 'Complete' ? '<span style="color:green;">Complete</span>' : '<span style="color:orange;">In Progress</span>') . '</td>';
        $rows_html .= '<td class="action-button-container">';
        $rows_html .= '<button class="view-btn" onclick="window.location.href=\'view_report.php?report_id=' . htmlspecialchars($row['report_id']) . '\'">';
        $rows_html .= '<i class="fa-solid fa-eye"></i>';
        $rows_html .= '</button>';
        // $rows_html .= '<button class="delete-btn" onclick="deleteReport(' . htmlspecialchars(json_encode($row['report_id'])) . ')">';
        // $rows_html .= '<i class="fa-solid fa-trash"></i>';
        // $rows_html .= '</button>';
        $rows_html .= '<button class="download-btn" onclick="window.location.href=\'generate_pdf.php?report_id=' . htmlspecialchars($row['report_id']) . '\'">';
        $rows_html .= '<i class="fa-solid fa-download"></i>';
        $rows_html .= '</button>';
        $rows_html .= '</td>';
        $rows_html .= '</tr>';
    }
}

if (isset($_GET['count']) && $_GET['count'] == '1') {
    header('Content-Type: application/json');
    echo json_encode([
        'html' => $rows_html,
        'count' => $filtered_count
    ]);
    exit;
} else {
    echo $rows_html;
}
?>