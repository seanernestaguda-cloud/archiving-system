<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: adminlogin.php");
    exit;
}

require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');
require_once('../FPDF/vendor/autoload.php'); // For FPDI

use setasign\Fpdi\Tcpdf\Fpdi as PdfWithFpdi;

include('connection.php');

// Get permit ID
$permit_id = $_GET['id'] ?? null;
if (!$permit_id) {
    die("No permit ID provided.");
}

// Fetch permit data
$stmt = $conn->prepare("SELECT * FROM fire_safety_inspection_certificate WHERE id = ?");
$stmt->bind_param("i", $permit_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
if (!$row) {
    die("Permit not found.");
}


$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown';
$log_stmt = $conn->prepare("INSERT INTO activity_logs (username, action, report_id, details) VALUES (?, 'download', ?, ?)");
$details = "Download: " . $row['permit_name'];
$log_stmt->bind_param('sis', $username, $permit_id, $details);
$log_stmt->execute();
$log_stmt->close();

// Fetch uploader's full name from users table
$uploader_username = isset($row['uploader']) ? $row['uploader'] : '';
$uploader_fullname = '';
if (!empty($uploader_username)) {
    $uploader_stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM users WHERE username = ? LIMIT 1");
    $uploader_stmt->bind_param('s', $uploader_username);
    $uploader_stmt->execute();
    $uploader_result = $uploader_stmt->get_result();
    if ($uploader_row = $uploader_result->fetch_assoc()) {
        $uploader_fullname = $uploader_row['first_name'] . ' ' . $uploader_row['middle_name'] . ' ' . $uploader_row['last_name'];
    }
    $uploader_stmt->close();
}



// Custom Header and Footer class
class CustomPDF extends PdfWithFpdi
{
    public function Header()
    {
        // Only show header on the first page
        if ($this->PageNo() == 1) {
            $logoPath = '../images/logo.png'; // Update path if you have a logo
            if (file_exists($logoPath)) {
                $this->Image($logoPath, 15, 10, 20, 20);
            }
            $this->SetFont('helvetica', 'B', 16);
            $this->Cell(0, 10, 'BUREAU OF FIRE PROTECTION', 0, 1, 'C');
            $this->SetFont('helvetica', '', 12);
            $this->Cell(0, 8, 'Fire Safety Inspection Certificate', 0, 1, 'C');
            $this->Ln(2);
        }
    }
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 9);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

$pdf = new CustomPDF('P', 'mm', 'LEGAL', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Bureau of Fire Protection');
$pdf->SetTitle($row['permit_name']);
$pdf->SetMargins(15, 35, 15); // Top margin for header
$pdf->AddPage();

// Section: Permit Title
$pdf->SetFont('helvetica', 'B', 15);
$pdf->Cell(0, 12, strtoupper($row['permit_name']), 0, 1, 'C');
$pdf->Ln(2);

// Section: Main Info Table
$pdf->SetFont('helvetica', '', 12);
$tbl = '<table border="1" cellpadding="4">
<tr><td width="40%"><b>Establishment Name</b></td><td width="60%">' . htmlspecialchars($row['inspection_establishment']) . '</td></tr>
<tr><td><b>Owner</b></td><td>' . htmlspecialchars($row['owner']) . '</td></tr>
<tr><td><b>Contact Person</b></td><td>' . htmlspecialchars($row['contact_person']) . '</td></tr>
<tr><td><b>Contact Number</b></td><td>' . htmlspecialchars($row['contact_number']) . '</td></tr>
<tr><td><b>Address</b></td><td>' . htmlspecialchars($row['inspection_address']) . '</td></tr>
<tr><td><b>Date of Inspection</b></td><td>' . htmlspecialchars($row['inspection_date']) . '</td></tr>
<tr><td><b>Establishment Type</b></td><td>' . htmlspecialchars($row['establishment_type']) . '</td></tr>
<tr><td><b>Purpose of Inspection</b></td><td>' . htmlspecialchars($row['inspection_purpose']) . '</td></tr>
<tr><td><b>Number of Occupants</b></td><td>' . htmlspecialchars($row['number_of_occupants']) . '</td></tr>
<tr><td><b>Nature of Business</b></td><td>' . htmlspecialchars($row['nature_of_business']) . '</td></tr>
<tr><td><b>Number of Floors</b></td><td>' . htmlspecialchars($row['number_of_floors']) . '</td></tr>
<tr><td><b>Floor Area</b></td><td>' . htmlspecialchars($row['floor_area']) . '</td></tr>
<tr><td><b>Classification of Hazards</b></td><td>' . htmlspecialchars($row['classification_of_hazards']) . '</td></tr>
<tr><td><b>Building Construction</b></td><td>' . htmlspecialchars($row['building_construction']) . '</td></tr>
<tr><td><b>Possible Problems during Fire</b></td><td>' . htmlspecialchars($row['possible_problems']) . '</td></tr>
<tr><td><b>Hazardous/Flammable Materials</b></td><td>' . htmlspecialchars($row['hazardous_materials']) . '</td></tr>
<tr><td><b>Inspected By</b></td><td>' . htmlspecialchars($row['inspected_by']) . '</td></tr>
</table>';
$pdf->writeHTML($tbl, true, false, false, false, '');
$pdf->Ln(4);
// Add 'Prepared By' signature block to bottom right of first page
if ($pdf->PageNo() == 1) {
    $rightMargin = 15; // matches your SetMargins right value
    $blockWidth = 100;  // width of the signature block
    $pdf->SetY(-90);   // vertical position from bottom
    $pdf->SetX($pdf->getPageWidth() - $blockWidth - $rightMargin);
    // Prepared By label
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell($blockWidth, 8, 'Prepared By:', 0, 1, 'L');
    // Full name underlined
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetX($pdf->getPageWidth() - $blockWidth - $rightMargin);
    $pdf->Cell($blockWidth, 8, !empty($uploader_fullname) ? $uploader_fullname : '_________________________', 0, 1, 'C');
    $pdf->SetX($pdf->getPageWidth() - $blockWidth - $rightMargin);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell($blockWidth, 0, '', 'T', 1, 'L'); // underline
    // Signature over printed name in bold
    $pdf->SetX($pdf->getPageWidth() - $blockWidth - $rightMargin);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell($blockWidth, 5, 'Signature over printed name', 0, 1, 'C');
}

$attachments = [
    'Application Form' => $row['application_form'],
    'Proof of Ownership' => $row['proof_of_ownership'],
    'Building Plans' => $row['building_plans'],
    'Fire Safety Inspection Checklist' => $row['fire_safety_inspection_checklist'],
    'Fire Safety Inspection Certificate (FSIC)' => $row['fire_safety_inspection_certificate'],
    'Occupancy Permit' => $row['occupancy_permit'],
    'Business Permit' => $row['business_permit'],
];
function importAllPdfPages($pdf, $filePath)
{
    try {
        if (file_exists($filePath) && strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf') {
            $pageCount = $pdf->setSourceFile($filePath);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $pdf->AddPage();
                $templateId = $pdf->importPage($pageNo);
                $pdf->useTemplate($templateId);
            }
            return true;
        }
    } catch (Exception $e) {
        return false;
    }
    return false;
}

foreach ($attachments as $title => $filePath) {
    if (!empty($filePath) && file_exists($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, $title, 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Image($filePath, '', '', 120, 90, '', '', 'T', true);
            $pdf->Ln(95);
        } elseif ($ext === 'pdf') {
            if (!importAllPdfPages($pdf, $filePath)) {
                $pdf->AddPage();
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 10, $title, 0, 1, 'L');
                $pdf->SetFont('helvetica', '', 12);
                $pdf->Write(0, "Cannot display PDF attachment: " . basename($filePath) . ". This PDF uses a compression method not supported by the free FPDI parser.", '', 0, '', false);
            }
        } else {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, $title, 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Write(0, "Attached file: " . basename($filePath) . " (Cannot display this file type in PDF)", '', 0, '', false);
        }
    }
}
// Output PDF
$pdf->Output("Fire_Safety_Inspection_Certificate_{$permit_id}.pdf", 'D');
?>