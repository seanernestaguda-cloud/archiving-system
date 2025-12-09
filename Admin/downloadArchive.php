<?php
include('connection.php'); // Include database connection

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Ensure ID is an integer to prevent SQL injection

    // Fetch document_name based on the provided ID
    $query = "SELECT document_name FROM archives WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $documentName);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Check if a document was found
    if ($documentName) {
        $filePath = 'uploads/' . $documentName; // Construct file path

        // Check if the file exists
        if (file_exists($filePath)) {
            // Set headers for file download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));

            // Read the file and send it to the user
            readfile($filePath);
            exit();
        } else {
            echo "Error: File does not exist.";
        }
    } else {
        echo "Error: No record found for the provided ID.";
    }
} else {
    echo "Error: No ID provided.";
}
?>
