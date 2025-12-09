<?php
session_start();
include('connection.php');
$username = $_SESSION['username'];

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clauses[] = "deleted_at IS NULL";
$query_search = $search;
$where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Pagination for AJAX search
$per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;
$offset = ($page - 1) * $per_page;

// If searching, ignore pagination and get all records
if ($search !== '') {
    $query = "SELECT * FROM fire_safety_inspection_certificate $where_sql ORDER BY id ASC";
    $result = mysqli_query($conn, $query);
    $permits = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    $query = "SELECT * FROM fire_safety_inspection_certificate $where_sql ORDER BY id ASC LIMIT $per_page OFFSET $offset";
    $result = mysqli_query($conn, $query);
    $permits = mysqli_fetch_all($result, MYSQLI_ASSOC);
}


$rows_html = '';
$filtered_count = 0;
foreach ($permits as $row) {
    $required_fields = [
        $row['permit_name'],
        $row['inspection_establishment'],
        $row['owner'],
        $row['inspection_address'],
        $row['inspection_date'],
        $row['establishment_type'],
        $row['inspection_purpose'],
        $row['fire_alarms'],
        $row['fire_extinguishers'],
        $row['emergency_exits'],
        $row['sprinkler_systems'],
        $row['fire_drills'],
        $row['exit_signs'],
        $row['electrical_wiring'],
        $row['emergency_evacuations'],
        $row['inspected_by'],
        $row['contact_person'],
        $row['contact_number'],
        $row['number_of_occupants'],
        $row['nature_of_business'],
        $row['number_of_floors'],
        $row['floor_area'],
        $row['classification_of_hazards'],
        $row['building_construction'],
        $row['possible_problems'],
        $row['hazardous_materials'],
        $row['application_form'],
        $row['proof_of_ownership'],
        $row['building_plans'],
        $row['fire_safety_inspection_certificate'],
        $row['fire_safety_inspection_checklist'],
        $row['occupancy_permit'],
        $row['business_permit'],
    ];
    $is_complete = true;
    foreach ($required_fields as $field) {
        if (!isset($field) || trim($field) === '' || $field === ', , , ') {
            $is_complete = false;
            break;
        }
    }
    $status = $is_complete ? 'Complete' : 'In Progress';

    $search_fields = array(
        $row['id'],
        $row['permit_name'],
        $row['inspection_establishment'],
        $row['establishment_type'],
        $row['owner'],
        $row['inspection_purpose'],
        $row['inspection_address'],
        $row['inspection_date'],
        $row['uploader'],
        $row['created_at'],
        // $row['department'],
        $status
    );

    $show_row = true;
    if ($query_search !== '') {
        $search_lower = strtolower($query_search);
        $match = false;
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
        $rows_html .= '<tr id="permit-row' . htmlspecialchars($row['id']) . '">';
        $rows_html .= '<td class="select-checkbox-cell" style="display:none;"><input type="checkbox" class="select-item" value="' . htmlspecialchars($row['id']) . '"></td>';
        $rows_html .= '<td>' . htmlspecialchars($row['id']) . '</td>';
        $rows_html .= '<td>' . htmlspecialchars($row['permit_name']) . '</td>';
        $rows_html .= '<td>' . htmlspecialchars($row['inspection_establishment']) . '</td>';
        $rows_html .= '<td>' . htmlspecialchars($row['establishment_type']) . '</td>';
        $rows_html .= '<td>' . htmlspecialchars($row['owner']) . '</td>';
        $rows_html .= '<td>' . htmlspecialchars($row['inspection_purpose']) . '</td>';
        $rows_html .= '<td>' . htmlspecialchars($row['inspection_address']) . '</td>';
        $rows_html .= '<td>' . htmlspecialchars($row['inspection_date']) . '</td>';
        $rows_html .= '<td>' . htmlspecialchars($row['uploader']) . '</td>';
        $rows_html .= '<td>' . htmlspecialchars($row['created_at']) . '</td>';
        // $rows_html .= '<td>' . (isset($row['department']) && trim($row['department']) !== '' ? htmlspecialchars($row['department']) : 'N/A') . '</td>';
        $rows_html .= '<td>' . ($status === 'Complete' ? '<span style="color:green;">Complete</span>' : '<span style="color:orange;">In Progress</span>') . '</td>';
        $rows_html .= '<td class="action-button-container">';
        $rows_html .= '<button class="view-btn" onclick="window.location.href=\'view_permit.php?id=' . htmlspecialchars($row['id']) . '\'">';
        $rows_html .= '<i class="fa-solid fa-eye"></i>';
        $rows_html .= '</button>';
        $rows_html .= '<button class="delete-btn" onclick="deletePermit(' . htmlspecialchars(json_encode($row['id'])) . ')">';
        $rows_html .= '<i class="fa-solid fa-trash"></i>';
        $rows_html .= '</button>';
        $rows_html .= '<button class="download-btn" onclick="window.location.href=\'generate_permit.php?id=' . htmlspecialchars($row['id']) . '\'">';
        $rows_html .= '<i class="fa-solid fa-download"></i>';
        $rows_html .= '</button>';
        $rows_html .= '</td>';
        $rows_html .= '</tr>';
    }
}
mysqli_close($conn);

if (isset($_GET['count']) && $_GET['count'] == '1') {
    header('Content-Type: application/json');
    $html = $rows_html;
    if ($filtered_count === 0) {
        $html = '<tr><td colspan="13" style="text-align:center; color:black;">No reports found.</td></tr>';
    }
    echo json_encode([
        'html' => $html,
        'count' => $filtered_count
    ]);
    exit;
} else {
    if ($filtered_count === 0) {
        echo '<tr><td colspan="13" style="text-align:center; color:#888;">No reports found.</td></tr>';
    } else {
        echo $rows_html;
    }
}