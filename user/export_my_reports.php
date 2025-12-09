<?php
session_start();
include('connection.php');
require '../FPDF/phpspreadsheet/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Get filters from GET

$where_clauses = [];
$params = [];
$param_types = '';

// Only show reports uploaded by the current user
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    die('Unauthorized: No user session found.');
}
$username = $_SESSION['username'];
$where_clauses[] = "uploader = ?";
$params[] = $username;
$param_types .= 's';

if (!empty($_GET['start_month'])) {
    $start = $_GET['start_month'] . '-01 00:00:00';
    $where_clauses[] = "incident_date >= ?";
    $params[] = $start;
    $param_types .= 's';
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

$allowed_sort_columns = ['report_id', 'report_title', 'incident_date', 'fire_location'];
$sort_by = isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowed_sort_columns) ? $_GET['sort_by'] : 'report_id';
$order_by = 'ASC';

$query = "SELECT report_id, report_title, street, purok, fire_location, municipality, incident_date, establishment, victims, firefighters, property_damage, fire_types, uploader, created_at FROM fire_incident_reports $where_sql ORDER BY $sort_by $order_by";

// Fetch data
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

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header
$headers = ['Report ID', 'Report Title', 'Street', 'Purok', 'Barangay', 'Municipality', 'Incident Date', 'Establishment', 'Victims', 'Firefighters', 'Property Damage', 'Cause of Fire', 'Uploader', 'Date Created'];
$sheet->fromArray($headers, NULL, 'A1');

// Data
$rowNum = 2;
foreach ($reports as $row) {
    $sheet->fromArray(array_values($row), NULL, 'A' . $rowNum++);
}

// Output
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="fire_incident_reports.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
