<?php
require '../phpspreadsheet/vendor/autoload.php'; // Include PhpSpreadsheet library
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_POST['download_excel'])) {
    include('connection.php');

    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Fetch data from the database
    $query = "SELECT report_id, report_title, fire_location, incident_date, establishment, victims, property_damage, fire_types, fire_cause 
              FROM fire_incident_reports 
              WHERE incident_date BETWEEN ? AND ? 
              ORDER BY incident_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    // Create a new spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Add header row
    $header = ['Report ID', 'Report Title', 'Barangay', 'Time and Date', 'Establishment', 'Victims', 'Damage to Property', 'Fire Type', 'Cause of Fire'];
    $sheet->fromArray($header, null, 'A1');

    // Add data rows
    $rowIndex = 2;
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue("A{$rowIndex}", $row['report_id']);
        $sheet->setCellValue("B{$rowIndex}", $row['report_title']);
        $sheet->setCellValue("C{$rowIndex}", $row['fire_location']);
        $sheet->setCellValue("D{$rowIndex}", $row['incident_date']);
        $sheet->setCellValue("E{$rowIndex}", $row['establishment']);
        $sheet->setCellValue("F{$rowIndex}", count(explode(',', $row['victims'])));
        $sheet->setCellValue("G{$rowIndex}", $row['property_damage']);
        $sheet->setCellValue("H{$rowIndex}", $row['fire_types']);
        $sheet->setCellValue("I{$rowIndex}", $row['fire_cause']);
        $rowIndex++;
    }

    // Save as Excel file
    $writer = new Xlsx($spreadsheet);
    $filename = "Fire_Incident_Reports_{$start_date}_to_{$end_date}.csv";

    // Send to browser for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    $writer->save("php://output");

    // Close the connection
    $stmt->close();
    $conn->close();
    exit;
}
?>
