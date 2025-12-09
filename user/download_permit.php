<?php
// Start the session
session_start();
include('connection.php');

// Include Dompdf library
require '../FPDF/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("No permit ID provided.");
}

// Fetch permit details based on the ID
$permitId = intval($_GET['id']);
$query = "SELECT * FROM fire_safety_inspection_certificate WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $permitId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No permit found with the provided ID.");
}

$permit = $result->fetch_assoc();
$stmt->close();
mysqli_close($conn);

// Prepare HTML content for the PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <title>Fire Safety Inspection Certificate</title>
    <style>
        @font-face {
            font-family: "DejaVu Sans";
            src: url("fonts/DejaVuSans.ttf") format("truetype");
        }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
        }
        .title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
        }
        .section {
            margin-top: 20px;
        }
        .label {
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
        }
        fieldset {
            margin-top: 20px;
        }
        legend {
            font-size: 18px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="title">Fire Safety Inspection Certificate</div>
    <div class="section">
        <p><span class="label">Permit Name:</span> ' . htmlspecialchars($permit['permit_name']) . '</p>
        <p><span class="label">Establishment:</span> ' . htmlspecialchars($permit['inspection_establishment']) . '</p>
        <p><span class="label">Owner:</span> ' . htmlspecialchars($permit['owner']) . '</p>
        <p><span class="label">Address:</span> ' . htmlspecialchars($permit['inspection_address']) . '</p>
        <p><span class="label">Date of Inspection:</span> ' . date("Y-m-d", strtotime($permit['inspection_date'])) . '</p>
        <p><span class="label">Inspection Purpose:</span> ' . htmlspecialchars($permit['inspection_purpose']) . '</p>
    </div>
    <br />
    <fieldset>
        <legend>Fire Safety Measures</legend>
        <table>
            <thead>
                <tr>
                    <th>Measure</th>
                    <th>Yes</th>
                    <th>No</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Fire Alarms</td>
                    <td>' . ($permit['fire_alarms'] == 1 ? "&#10003;" : "") . '</td>
                    <td>' . ($permit['fire_alarms'] == 0 ? "&#10003;" : "") . '</td>
                </tr>
                <tr>
                    <td>Fire Extinguishers</td>
                    <td>' . ($permit['fire_extinguishers'] == 1 ? "&#10003;" : "") . '</td>
                    <td>' . ($permit['fire_extinguishers'] == 0 ? "&#10003;" : "") . '</td>
                </tr>
                <tr>
                    <td>Emergency Exits</td>
                    <td>' . ($permit['emergency_exits'] == 1 ? "&#10003;" : "") . '</td>
                    <td>' . ($permit['emergency_exits'] == 0 ? "&#10003;" : "") . '</td>
                </tr>
                <tr>
                    <td>Sprinkler Systems</td>
                    <td>' . ($permit['sprinkler_systems'] == 1 ? "&#10003;" : "") . '</td>
                    <td>' . ($permit['sprinkler_systems'] == 0 ? "&#10003;" : "") . '</td>
                </tr>
                <tr>
                    <td>Fire Drills</td>
                    <td>' . ($permit['fire_drills'] == 1 ? "&#10003;" : "") . '</td>
                    <td>' . ($permit['fire_drills'] == 0 ? "&#10003;" : "") . '</td>
                </tr>
                <tr>
                    <td>Exit Signs</td>
                    <td>' . ($permit['exit_signs'] == 1 ? "&#10003;" : "") . '</td>
                    <td>' . ($permit['exit_signs'] == 0 ? "&#10003;" : "") . '</td>
                </tr>
                <tr>
                    <td>Electrical Wiring (Safe)</td>
                    <td>' . ($permit['electrical_wiring'] == 1 ? "&#10003;" : "") . '</td>
                    <td>' . ($permit['electrical_wiring'] == 0 ? "&#10003;" : "") . '</td>
                </tr>
                <tr>
                    <td>Emergency Evacuations</td>
                    <td>' . ($permit['emergency_evacuations'] == 1 ? "&#10003;" : "") . '</td>
                    <td>' . ($permit['emergency_evacuations'] == 0 ? "&#10003;" : "") . '</td>
                </tr>
            </tbody>
        </table>
    </fieldset>
    <br />
    <p><span class="label">Inspection By:</span> ' . htmlspecialchars($permit['inspected_by']) . '</p>
</body>
</html>
';

// Initialize Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

// Specify the font directory
$fontDir = __DIR__ . '/fonts';
$options->set('fontDir', $fontDir);

// Initialize Dompdf with the options
$dompdf = new Dompdf($options);

// Load the HTML content
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the PDF
$dompdf->render();

// Output the generated PDF to the browser
$dompdf->stream('fire_safety_certificate.pdf', ['Attachment' => 0]);
