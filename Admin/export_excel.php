<?php
session_start();
include('connection.php');
require '../phpspreadsheet/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Fetch all reports (you can add filters if needed)
$query = "SELECT report_id, report_title, CONCAT(street, ', ', purok, ', ', fire_location, ', ', municipality) AS fire_location, incident_date, establishment, victims, firefighters, property_damage, fire_types FROM fire_incident_reports";
$result = mysqli_query($conn, $query);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set header
$headers = ['Report ID', 'Report Title', 'Location', 'Time and Date', 'Establishment', 'Victims', 'Firefighters', 'Damage to Property', 'Cause of Fire'];
$sheet->fromArray($headers, NULL, 'A1');

// Fill data
$rowNum = 2;
while ($row = mysqli_fetch_assoc($result)) {
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
    ], NULL, 'A' . $rowNum++);
}

// Output to browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="fire_incident_reports.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
