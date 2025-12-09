<?php
// Include Composer autoload
session_start();
require '../phpspreadsheet/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    // Check if file was uploaded
    if ($_FILES['file']['error'] == 0) {
        $filePath = $_FILES['file']['tmp_name'];

        try {
            // Load the spreadsheet
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();

            // Loop through each row in the Excel sheet
            for ($row = 2; $row <= $highestRow; $row++) {
                // Assuming the Report ID is in column A (adjust if needed)
                $report_id = $sheet->getCell('A' . $row)->getValue();
                $report_title = $sheet->getCell('B' . $row)->getValue();
                $fire_location = $sheet->getCell('C' . $row)->getValue();
                $incident_date = $sheet->getCell('D' . $row)->getValue();
                $establishment = $sheet->getCell('E' . $row)->getValue();
                $victims = $sheet->getCell('F' . $row)->getValue();
                $property_damage = $sheet->getCell('G' . $row)->getValue();
                $fire_types = $sheet->getCell('H' . $row)->getValue();
                $fire_cause = $sheet->getCell('I' . $row)->getValue();
                $uploader = $_SESSION['username']; // Or wherever you get the uploader name from
                $department = $sheet->getCell('J' . $row)->getValue();

                // Insert the data into the database
              include ('connection.php');
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }

                $query = "INSERT INTO fire_incident_reports (id, report_title, fire_location, incident_date, establishment, victims, property_damage, fire_types, fire_cause, uploader, department) 
                          VALUES ('$report_id', '$report_title', '$fire_location', '$incident_date', '$establishment', '$victims', '$property_damage', '$fire_types', '$fire_cause', '$uploader', '$department')";

                if ($conn->query($query) === TRUE) {
                    echo "Report ID $report_id inserted successfully.";
                } else {
                    echo "Error: " . $query . "<br>" . $conn->error;
                }

                $conn->close();
            }

        } catch (Exception $e) {
            echo 'Error loading file: ', $e->getMessage();
        }
    } else {
        echo "Error uploading file.";
    }
}
?>
