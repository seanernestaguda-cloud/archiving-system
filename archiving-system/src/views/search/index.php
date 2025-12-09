<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Fire Reports</title>
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body>
    <?php include '../layout.php'; ?>

    <div class="container">
        <h1>Search Fire Reports</h1>
        <form action="" method="GET">
            <input type="text" name="query" placeholder="Enter search criteria..." required>
            <button type="submit">Search</button>
        </form>

        <?php if (isset($_GET['query'])): ?>
            <h2>Search Results for "<?php echo htmlspecialchars($_GET['query']); ?>"</h2>
            <ul>
                <?php
                // Assuming $searchController is an instance of SearchController
                $results = $searchController->searchReports($_GET['query']);
                if (count($results) > 0) {
                    foreach ($results as $report) {
                        echo '<li><a href="../fireReports/view.php?id=' . $report->id . '">' . htmlspecialchars($report->title) . '</a></li>';
                    }
                } else {
                    echo '<li>No results found.</li>';
                }
                ?>
            </ul>
        <?php endif; ?>
    </div>

    <script src="../../public/js/script.js"></script>
</body>
</html>