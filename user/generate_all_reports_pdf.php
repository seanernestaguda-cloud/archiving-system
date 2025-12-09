<?php
require_once('../vendor/tecnickcom/tcpdf/tcpdf.php'); // Adjust path if necessary
include('connection.php');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if month is selected
if (!isset($_GET['incident_month']) || empty($_GET['incident_month'])) {
    die("No month selected!");
}

$selected_month = $_GET['incident_month'];

// Fetch reports for the selected month
$query = "SELECT report_id, report_title, CONCAT(street, ', ', purok, ', ', fire_location) AS fire_location_combined, 
                 incident_date, establishment, victims, property_damage, fire_types, fire_cause, uploader, department 
          FROM fire_incident_reports 
          WHERE DATE_FORMAT(incident_date, '%Y-%m') = ?
          ORDER BY incident_date ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $selected_month);
$stmt->execute();
$result = $stmt->get_result();
$reports = mysqli_fetch_all($result, MYSQLI_ASSOC);

if (empty($reports)) {
    die("No reports found for the selected month.");
}

// Create a temporary folder
$zip_folder = __DIR__ . "/../Admin/uploads/temp_reports";

if (!file_exists($zip_folder)) {
    mkdir($zip_folder, 0777, true);
}

$pdf_files = [];

foreach ($reports as $row) {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('BFP Archiving System');
    $pdf->SetTitle('Fire Incident Report #' . $row['report_id']);
    $pdf->SetSubject('Fire Incident Report');
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 10);
    $pdf->AddPage();

    // Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Fire Incident Report #' . $row['report_id'], 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('helvetica', '', 12);
    
    // Report details
    $pdf->MultiCell(0, 8, "📌 Title: " . $row['report_title'], 0, 'L');
    $pdf->MultiCell(0, 8, "📍 Location: " . $row['fire_location_combined'], 0, 'L');
    $pdf->MultiCell(0, 8, "📅 Date & Time: " . $row['incident_date'], 0, 'L');
    $pdf->MultiCell(0, 8, "🏢 Establishment: " . $row['establishment'], 0, 'L');
    $pdf->MultiCell(0, 8, "🔥 Fire Type: " . $row['fire_types'], 0, 'L');
    $pdf->MultiCell(0, 8, "⚠️ Cause of Fire: " . $row['fire_cause'], 0, 'L');
    $pdf->MultiCell(0, 8, "💰 Property Damage: " . $row['property_damage'], 0, 'L');
    $pdf->MultiCell(0, 8, "👥 Reported by: " . $row['uploader'] . " (Dept: " . $row['department'] . ")", 0, 'L');

    if (!empty($row['victims'])) {
        $pdf->MultiCell(0, 8, "🚑 Victims: " . $row['victims'], 0, 'L');
    } else {
        $pdf->MultiCell(0, 8, "🚑 Victims: None", 0, 'L');
    }
    
    $pdf->Ln(5);

    // Save each file
    $file_name = "Fire_Incident_Report_" . $row['report_id'] . ".pdf";
    $file_path = $zip_folder . "/Fire_Incident_Report_" . $row['report_id'] . ".pdf";
    $pdf->Output($file_path, 'F'); // Save the file

    $pdf_files[] = $file_path;
}

// Create ZIP archive
$zip_file = "../Admin/uploads/Fire_Reports_" . $selected_month . ".zip";
$zip = new ZipArchive();
if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    foreach ($pdf_files as $pdf) {
        $zip->addFile($pdf, basename($pdf)); // Add files to ZIP
    }
    $zip->close();
} else {
    die("Failed to create ZIP file.");
}

// Delete temporary PDF files after zipping
foreach ($pdf_files as $pdf) {
    unlink($pdf);
}
rmdir($zip_folder); // Remove temp folder

// Force download ZIP file
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($zip_file) . '"');
header('Content-Length: ' . filesize($zip_file));
readfile($zip_file);

// Delete ZIP after download
unlink($zip_file);
exit;
?>
