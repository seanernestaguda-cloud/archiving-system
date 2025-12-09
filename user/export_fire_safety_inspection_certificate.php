<?php
session_start();
include('connection.php');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Allow sorting by specific columns only
$allowed_sort = [
    'permit_id' => 'id',
    'permit_name' => 'permit_name',
    'inspection_date' => 'inspection_date',
    'inspection_establishment' => 'inspection_establishment'
];
$sort_by = isset($_GET['sort_by']) && isset($allowed_sort[$_GET['sort_by']]) ? $allowed_sort[$_GET['sort_by']] : 'id';

// Filtering by month
$where = [];
if (!empty($_GET['start_month'])) {
    $start = $_GET['start_month'] . '-01';
    $where[] = "inspection_date >= '" . mysqli_real_escape_string($conn, $start) . "'";
}
if (!empty($_GET['end_month'])) {
    $end = date('Y-m-t', strtotime($_GET['end_month'] . '-01'));
    $where[] = "inspection_date <= '" . mysqli_real_escape_string($conn, $end) . "'";
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$query = "SELECT * FROM fire_safety_inspection_certificate $where_sql ORDER BY $sort_by ASC;";

$result = mysqli_query($conn, $query);

if (!$result) {
    die('Query failed: ' . mysqli_error($conn));
}

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=fire_safety_inspection_certificates.csv');

// Output CSV column headers
$output = fopen('php://output', 'w');
fputcsv($output, [
    'Inspection ID',
    'Title',
    'Establishment Name',
    'Establishment Type',
    'Owner',
    'Purpose',
    'Address',
    'Date of Inspection',
    'Uploaded By',
    'Date Created',
    'Department',
    'Status'
]);

// Output each row
while ($row = mysqli_fetch_assoc($result)) {
    // Check completeness (same logic as in your main file)
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
        $row['fire_safety_inspection_checklist'],
        $row['fire_safety_inspection_certificate'],
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

    fputcsv($output, [
        $row['id'],
        $row['permit_name'],
        $row['inspection_establishment'],
        $row['establishment_type'],
        $row['owner'],
        $row['inspection_purpose'],
        $row['inspection_address'],
        date("Y-m-d", strtotime($row['inspection_date'])),
        $row['uploader'],
        $row['created_at'],
        $row['department'],
        $status
    ]);
}

fclose($output);
mysqli_close($conn);
exit;
?>