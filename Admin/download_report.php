<?php
require '../FPDF/vendor/autoload.php'; // Path to PhpSpreadsheet library

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

session_start();
include('connection.php');

if (isset($_GET['report_id'])) {
    $report_id = $_GET['report_id'];

    // Fetch the specific report from the database
    $query = "SELECT id, report_title, fire_location, incident_date, establishment, victims, property_damage, fire_types, fire_cause, uploader, department FROM fire_incident_reports WHERE id = '$report_id';";
    $result = mysqli_query($conn, $query);
    $report = mysqli_fetch_assoc($result);

    if ($report) {
        // Create a new spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Fire Incident Report');

        // Set headers
        $sheet->setCellValue('A1', 'Report ID');
        $sheet->setCellValue('B1', 'Report Title');
        $sheet->setCellValue('C1', 'Barangay');
        $sheet->setCellValue('D1', 'Date and Time');
        $sheet->setCellValue('E1', 'Establishment');
        $sheet->setCellValue('F1', 'Victims');
        $sheet->setCellValue('G1', 'Damage to Property');
        $sheet->setCellValue('H1', 'Fire Type');
        $sheet->setCellValue('I1', 'Cause of Fire');
        
        // Add the data to the sheet
        $sheet->setCellValue('A2', $report['id']);
        $sheet->setCellValue('B2', $report['report_title']);
        $sheet->setCellValue('C2', $report['fire_location']);
        $sheet->setCellValue('D2', $report['incident_date']);
        $sheet->setCellValue('E2', $report['establishment']);
        $sheet->setCellValue('F2', $report['victims']);
        $sheet->setCellValue('G2', $report['property_damage']);
        $sheet->setCellValue('H2', $report['fire_types']);
        $sheet->setCellValue('I2', $report['fire_cause']);
        
        // Set column widths
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);

        // Align text to center
        $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Create writer and save the file
        $writer = new Xlsx($spreadsheet);
        $filename = "fire_incident_report_" . $report['id'] . ".xlsx";

        // Set the headers to download the file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // Save and output the file
        $writer->save('php://output');
        exit;
    } else {
        echo "Report not found.";
    }

    mysqli_close($conn);
}
?>
