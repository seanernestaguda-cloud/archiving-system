<?php
// Include database connection file
include('../config.php'); // Adjust the path as per your directory structure

// Start the session
session_start();

// Check if a file is uploaded
if (isset($_FILES['file'])) {
    $file = $_FILES['file'];

    // File details
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileType = $file['type'];

    // Allowed file types
    $allowedExtensions = ['xlsx', 'xls', 'pdf'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Validate file extension
    if (in_array($fileExtension, $allowedExtensions)) {
        if ($fileError === 0) {
            if ($fileSize <= 5 * 1024 * 1024) { // File size limit: 5MB

                // Generate a unique name for the file
                $uniqueFileName = uniqid('', true) . "." . $fileExtension;

                // Define the upload path
                $uploadDir = '../uploads/'; // Adjust this to your uploads directory
                $uploadPath = $uploadDir . $uniqueFileName;

                // Move the file to the upload directory
                if (move_uploaded_file($fileTmpName, $uploadPath)) {
                    // If the file is an Excel file, process it
                    if (in_array($fileExtension, ['xlsx', 'xls'])) {
                        require '../vendor/autoload.php'; // Assuming you're using PHPSpreadsheet
                        
                        try {
                            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($uploadPath);
                            $sheetData = $spreadsheet->getActiveSheet()->toArray();

                            // Iterate through the rows of the Excel sheet
                            foreach ($sheetData as $index => $row) {
                                // Skip the header row
                                if ($index === 0) continue;

                                $incidentName = $row[0];
                                $location = $row[1];
                                $incidentDate = $row[2];
                                $reportedBy = $row[3];
                                $status = $row[4];

                                // Insert the data into the database
                                $stmt = $con->prepare("INSERT INTO fire_reports (incident_name, location, incident_date, reported_by, status, file_name) VALUES (?, ?, ?, ?, ?, ?)");
                                $stmt->bind_param('ssssss', $incidentName, $location, $incidentDate, $reportedBy, $status, $uniqueFileName);
                                $stmt->execute();
                            }

                            echo json_encode(['status' => 'success', 'message' => 'File uploaded and data imported successfully.']);
                        } catch (Exception $e) {
                            echo json_encode(['status' => 'error', 'message' => 'Error processing the Excel file: ' . $e->getMessage()]);
                        }
                    } else {
                        // For PDF files, simply store the file name in the database
                        $stmt = $con->prepare("INSERT INTO fire_reports (file_name) VALUES (?)");
                        $stmt->bind_param('s', $uniqueFileName);
                        $stmt->execute();

                        echo json_encode(['status' => 'success', 'message' => 'PDF uploaded successfully.']);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error moving the uploaded file.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'File size exceeds the 5MB limit.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error uploading the file.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only Excel and PDF files are allowed.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded.']);
}

?>
