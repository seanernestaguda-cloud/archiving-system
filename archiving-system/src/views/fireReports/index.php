<?php
require_once '../../controllers/fireReportsController.php';

$controller = new FireReportsController();
$reports = $controller->getReports();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../public/css/style.css">
    <title>Fire Reports</title>
</head>
<body>
    <div class="container">
        <h1>Fire Reports</h1>
        <a href="create.php" class="btn">Add New Report</a>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reports)): ?>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($report->id); ?></td>
                            <td><?php echo htmlspecialchars($report->title); ?></td>
                            <td><?php echo htmlspecialchars($report->description); ?></td>
                            <td><?php echo htmlspecialchars($report->date); ?></td>
                            <td>
                                <a href="view.php?id=<?php echo htmlspecialchars($report->id); ?>" class="btn">View</a>
                                <a href="edit.php?id=<?php echo htmlspecialchars($report->id); ?>" class="btn">Edit</a>
                                <a href="delete.php?id=<?php echo htmlspecialchars($report->id); ?>" class="btn">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No reports found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>