<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: userlogin.php");
    exit;
}

require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');
require_once('../FPDF/vendor/autoload.php'); // Ensure FPDI autoload is also included


// Include namespaces for FPDI and TCPDF
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\Tcpdf\Fpdi as PdfWithFpdi;

include('connection.php');

// Get the report ID from the URL
$report_id = $_GET['report_id'];

// Fetch the report data
$query = "SELECT * FROM fire_incident_reports WHERE report_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $report_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$report = mysqli_fetch_assoc($result);

if (!$report) {
    die("Report not found.");
}


$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown';
$log_stmt = $conn->prepare("INSERT INTO activity_logs (username, action, report_id, details) VALUES (?, 'download', ?, ?)");
$details = "Downloaded: " . $report['report_title'];
$log_stmt->bind_param('sis', $username, $report_id, $details);
$log_stmt->execute();
$log_stmt->close();

// Fetch uploader's full name from users table
$uploader_username = isset($report['uploader']) ? $report['uploader'] : '';
$uploader_fullname = '';
if (!empty($uploader_username)) {
    $uploader_query = $conn->prepare("SELECT first_name, middle_name, last_name FROM users WHERE username = ? LIMIT 1");
    $uploader_query->bind_param('s', $uploader_username);
    $uploader_query->execute();
    $uploader_result = $uploader_query->get_result();
    if ($uploader_row = $uploader_result->fetch_assoc()) {
        $uploader_fullname = $uploader_row['first_name'] . ' ' . $uploader_row['middle_name'] . ' ' . $uploader_row['last_name'];
    }
    $uploader_query->close();
}



// Custom Header and Footer class
class CustomPDF extends PdfWithFpdi
{
    public function Header()
    {
        // Logo (optional, update path if you have a logo)
        $logoPath = '../images/logo.png'; // Change path if needed
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 15, 10, 20, 20);
        }
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 10, 'BUREAU OF FIRE PROTECTION', 0, 1, 'C');
        $this->SetFont('helvetica', '', 12);
        $this->Cell(0, 8, 'Fire Incident Report', 0, 1, 'C');
        $this->Ln(2);
    }
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 9);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

$pdf = new CustomPDF('P', 'mm', 'LEGAL');
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Bureau of Fire Protection');
$pdf->SetTitle($report['report_title']);
$pdf->SetMargins(15, 35, 15); // Top margin increased for header
$pdf->AddPage();

// Section: Report Title
$pdf->SetFont('helvetica', 'B', 15);
$pdf->Cell(0, 12, strtoupper($report['report_title']), 0, 1, 'C');
$pdf->Ln(2);

// Section: Basic Information Table
$pdf->SetFont('helvetica', '', 12);
$tbl = '<table border="1" cellpadding="4">
<tr><td width="35%"><b>Reported By</b></td><td width="65%">' . htmlspecialchars($report['caller_name']) . '</td></tr>
<tr><td><b>Responding Team</b></td><td>' . htmlspecialchars($report['responding_team']) . '</td></tr>
<tr><td><b>Establishment Name</b></td><td>' . htmlspecialchars($report['establishment']) . '</td></tr>
<tr><td><b>Location</b></td><td>' . htmlspecialchars($report['street']) . ', ' . htmlspecialchars($report['purok']) . ', ' . htmlspecialchars($report['fire_location']) . '</td></tr>
</table>';
$pdf->writeHTML($tbl, true, false, false, false, '');
$pdf->Ln(4);

// Section: Time and Date Table
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(0, 8, 'Time and Date', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);
$tbl2 = '<table border="1" cellpadding="4">
<tr><td width="35%"><b>Date and Time Reported</b></td><td width="65%">' . htmlspecialchars($report['incident_date']) . '</td></tr>
<tr><td><b>Time of Arrival</b></td><td>' . htmlspecialchars($report['arrival_time']) . '</td></tr>
<tr><td><b>Time of Fireout</b></td><td>' . htmlspecialchars($report['fireout_time']) . '</td></tr>
</table>';
$pdf->writeHTML($tbl2, true, false, false, false, '');
$pdf->Ln(4);

// Section: Injured/Casualties Table
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(0, 8, 'Injured / Casualties', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);
$tbl3 = '<table border="1" cellpadding="4">
<tr><td width="35%"><b>Casualties (Civilians)</b></td><td width="65%">' . htmlspecialchars($report['victims']) . '</td></tr>
<tr><td><b>Casualties (Firefighters)</b></td><td>' . htmlspecialchars($report['firefighters']) . '</td></tr>
</table>';
$pdf->writeHTML($tbl3, true, false, false, false, '');
$pdf->Ln(4);

// Section: Other Details Table
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(0, 8, 'Other Details', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);
// Show 'Under Investigation' if fire_types is empty
$fire_type_display = empty($report['fire_types']) ? 'Under Investigation' : htmlspecialchars($report['fire_types']);
$tbl4 = '<table border="1" cellpadding="4">
<tr><td width="35%"><b>Estimated Damage to Property</b></td><td width="65%">PHP ' . htmlspecialchars($report['property_damage']) . '</td></tr>
<tr><td><b>Alarm Status</b></td><td>' . htmlspecialchars($report['alarm_status']) . '</td></tr>
<tr><td><b>Type of Occupancy</b></td><td>' . htmlspecialchars($report['occupancy_type']) . '</td></tr>
<tr><td><b>Cause of Fire</b></td><td>' . $fire_type_display . '</td></tr>
</table>';
$pdf->writeHTML($tbl4, true, false, false, false, '');
$pdf->Ln(4);
// Move 'Prepared By' signature block higher to avoid page overflow
$pdf->SetY(230); // Set Y to 230mm from top (LEGAL page height is 356mm)
$pdf->SetX(140); // Adjust X for right alignment
$pdf->SetFont('helvetica', 'B', 12);

// Display uploader's full name above signature block
$pdf->Cell(60, 8, 'Prepared By:', 0, 2, 'L');
if (!empty($uploader_fullname)) {
    // Underline the full name
    $pdf->SetFont('helvetica', 'U', 12);
    $pdf->Cell(60, 8, $uploader_fullname, 0, 2, 'L');
    $pdf->SetFont('helvetica', '', 12);
} else {
    $pdf->Cell(60, 8, '_________________________', 0, 2, 'L');
}
$pdf->Cell(60, 8, 'Signature over printed name', 0, 2, 'L');

// Section: Documentation Photos
if (!empty($report['documentation_photos'])) {
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Photos of the Scene', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $photos = explode(',', $report['documentation_photos']);
    foreach ($photos as $photo) {
        if (file_exists($photo)) {
            $pdf->Image($photo, '', '', 80, 60, '', '', 'T', true);
            $pdf->Ln(70);
        }
    }
}


// Function to import PDF pages using FPDI
function importPdfPages($pdf, $filePath, $useCurrentPageForFirst = false)
{
    if (file_exists($filePath) && strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf') {
        $pageCount = $pdf->setSourceFile($filePath);
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            if ($pageNo == 1 && $useCurrentPageForFirst) {
                // Use the current page for the first imported page
                $pdf->useTemplate($templateId);
            } else {
                $pdf->AddPage();
                $pdf->useTemplate($templateId);
            }
        }
    } else {
        $pdf->Write(0, 'File not found or invalid format.', '', 0, '', false);
    }
}
// Import additional reports if available
$reports = [
    'Spot Investigation Report' => $report['narrative_report'] ?? '',
    'Progress Investigation Report' => $report['progress_report'] ?? '',
    'Final Investigation Report' => $report['final_investigation_report'] ?? ''
];
foreach ($reports as $title => $filePath) {
    if (!empty($filePath) && file_exists($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        // Disable header/footer for these pages
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        if ($ext === 'pdf') {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 12);
            importPdfPages($pdf, $filePath, true); // Use current page for first imported page
        } else {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 12);

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $pdf->Image($filePath, 15, 40, 120, 90, '', '', 'T', true);
                $pdf->Ln(95);
            } elseif (in_array($ext, ['txt', 'csv'])) {
                $content = file_get_contents($filePath);
                $pdf->MultiCell(0, 8, $content);
            } else {
                $pdf->Write(0, "Attached file: " . basename($filePath) . " (Cannot display this file type in PDF)", '', 0, '', false);
            }
        }
        // Re-enable header/footer for next pages
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
    } else {
        $pdf->AddPage();
        $pdf->Write(0, "No $title available or file not found.", '', 0, '', false);
    }
}
// Output the PDF
$pdf->Output("Fire_Incident_Report_{$report_id}.pdf", 'D');
