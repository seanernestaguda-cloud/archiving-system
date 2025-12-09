<?php
session_start();
include('connection.php');
require '../phpspreadsheet/vendor/autoload.php';

if (!isset($_GET['month'], $_GET['year'])) {
    die('Month and year required.');
}

$month = intval($_GET['month']);
$year = intval($_GET['year']);

$query = "SELECT * FROM fire_incident_reports WHERE MONTH(incident_date) = ? AND YEAR(incident_date) = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $month, $year);
$stmt->execute();
$result = $stmt->get_result();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$headers = ['Report ID', 'Title', 'Location', 'Date', 'Establishment', 'Victims', 'Firefighters', 'Property Damage', 'Fire Types'];
$sheet->fromArray($headers, NULL, 'A1');

// Fill data
$rowNum = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->fromArray([
        $row['report_id'],
        $row['report_title'],
        $row['fire_location'],
        $row['incident_date'],
        $row['establishment'],
        $row['victims'],
        $row['firefighters'],
        $row['property_damage'],
        $row['fire_types']
    ], NULL, "A$rowNum");
    $rowNum++;
}

// Output to browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="fire_reports_'.$year.'_'.$month.'.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>