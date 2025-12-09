<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assuming you have a database connection in $conn

    // Handle file upload
    if (isset($_FILES['documentation']) && $_FILES['documentation']['error'] == 0) {
        $fileTmpPath = $_FILES['documentation']['tmp_name'];
        $fileName = $_FILES['documentation']['name'];
        $fileSize = $_FILES['documentation']['size'];
        $fileType = $_FILES['documentation']['type'];

        // Specify the folder where you want to save the file
        $uploadFolder = 'uploads/';

        // Generate a unique name for the file
        $newFileName = uniqid() . "_" . $fileName;

        // Move the file to the target folder
        if (move_uploaded_file($fileTmpPath, $uploadFolder . $newFileName)) {
            // File uploaded successfully, save the file path in the database

            $filePath = $uploadFolder . $newFileName;

            // Prepare your SQL query to insert data into the database
            $stmt = $conn->prepare("INSERT INTO fire_incident_reports (report_title, fire_location, incident_date, establishment, victims, property_damage, actions_taken, documentation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $_POST['report_title'], $_POST['fire_location'], $_POST['incident_date'], $_POST['establishment'], $_POST['victims'], $_POST['property_damage'], $_POST['actions_taken'], $filePath);

            // Execute the query
            if ($stmt->execute()) {
                echo "Report submitted successfully!";
            } else {
                echo "Error submitting the report.";
            }
        } else {
            echo "Error uploading the file.";
        }
    }
}

?>