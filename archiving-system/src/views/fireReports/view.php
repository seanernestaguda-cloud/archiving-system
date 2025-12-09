<?php
require_once '../../controllers/fireReportsController.php';

$fireReportsController = new FireReportsController();

if (isset($_GET['id'])) {
    $reportId = $_GET['id'];
    $report = $fireReportsController->getReport($reportId);
} else {
    header("Location: index.php");
    exit();
}

if (!$report) {
    echo "<h2>Report not found</h2>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../public/css/style.css">
    <title>View Fire Report</title>
</head>
<body>
    <?php include '../layout.php'; ?>
    
    <div class="container">
        <h1><?php echo htmlspecialchars($report['title']); ?></h1>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($report['description']); ?></p>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($report['date']); ?></p>
        <a href="index.php">Back to Reports</a>
    </div>
    
    <script src="../../public/js/script.js"></script>
</body>
</html>