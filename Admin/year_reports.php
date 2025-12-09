<?php
session_start();
include('connection.php');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the form is submitted and the years are set
$start_year = isset($_POST['start_year']) ? $_POST['start_year'] : '';
$end_year = isset($_POST['end_year']) ? $_POST['end_year'] : '';

// Only proceed if both years are provided
if ($start_year && $end_year) {
    // Use prepared statement to fetch fire incident reports for the selected year range
    $query = "SELECT report_id, report_title, fire_location, incident_date, establishment, victims, property_damage, fire_types, fire_cause, uploader, department 
              FROM fire_incident_reports 
              WHERE YEAR(incident_date) BETWEEN ? AND ?
              ORDER BY incident_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $start_year, $end_year);
    $stmt->execute();
    $result = $stmt->get_result();
    $reports = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    // Handle the case when no years are selected
    $reports = [];
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="reportstyle.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="icon" type="image/png" href="../REPORT.png">
    <title>Year-to-Year Reports</title>
    <style>
        /* Add necessary styling */
    </style>
</head>
<body>

<div class="dashboard">
 <aside class="sidebar">
        <nav>
            <ul>
                <li class = "archive-text"><h4>BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM</h4></li>
                <li><a href="admindashboard.php"><i class="fa-solid fa-gauge"></i> <span>Dashboard</span></a></li>
                <li class = "archive-text"><p>Archives</p></li>
                <li><a href="fire_types.php"><i class="fa-solid fa-fire-flame-curved"></i><span> Causes of Fire </span></a></li>
                <li><a href="barangay_list.php"><i class="fa-solid fa-building"></i><span> Barangay List </span></a></li>
                <li><a href="archives.php"><i class="fa-solid fa-fire"></i><span> Archives </span></a></li>
            
                <li class="report-dropdown">
                    <a href="#" class="report-dropdown-toggle">
                        <i class="fa-solid fa-box-archive"></i>
                        <span>Reports</span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                    <ul class="report-dropdown-content">
                        <li><a href="monthly_reports.php"><i class="fa-regular fa-calendar"></i> Monthly Reports</a></li>
                        <li><a href="barangay_reports.php"><i class="fa-solid fa-city"></i> Barangay Reports </a></li>
                        <li><a href="reports_per_barangay.php"><i class="fa-solid fa-chart-column"></i> Reports per Barangay</a></li>
                        <li><a href="monthly_reports_chart.php"><i class="fa-solid fa-chart-column"></i> Reports per Month </a></li>
                        <li><a href="year_to_year_comparison.php"><i class="fa-regular fa-calendar-days"></i> Year to Year Comparison </a></li>
                    </ul>
                </li>
                
                <li class="archive-text"><span>Maintenance</span></li>
                <li><a href="departments.php"><i class="fas fa-users"></i><span> Department List </span></a></li>
                <li><a href="manageuser.php"><i class="fas fa-users"></i><span> Manage Users </span></a></li>
                <li><a href="setting.php"><i class="fa-solid fa-gear"></i> <span>Settings</span></a></li>
            </ul>
        </nav>
    </aside>

    <div class="main-content">
        <header class="header">
            <button id="toggleSidebar" class="toggle-sidebar-btn">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h2>BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM</h2>
        </header>
        
        <div class="card"> 
            <div class="top-controls">
                <form action="export_reports.php" method="POST">
                    <input type="hidden" name="start_year" value="<?php echo htmlspecialchars($start_year); ?>">
                    <input type="hidden" name="end_year" value="<?php echo htmlspecialchars($end_year); ?>">
                    <button type="submit" name="download_excel" class="download-excel-btn">
                        <i class="fa-solid fa-download"></i>Download Reports
                    </button>
                </form>
            </div>
            <h3>Year-to-Year Fire Incident Reports</h3>
            <hr>
            <section class="yearly-report-section">
                <form action="yearly_reports.php" method="POST">
                    <div class="date">
                        <label for="start_year">Start Year:</label>
                        <input type="number" name="start_year" id="start_year" min="2000" max="<?php echo date('Y'); ?>" required>

                        <label for="end_year">End Year:</label>
                        <input type="number" name="end_year" id="end_year" min="2000" max="<?php echo date('Y'); ?>" required>

                        <button type="submit">Filter</button>
                    </div>   
                </form>

                <table class="archive-table">
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Report Title</th>
                            <th>Barangay</th>
                            <th>Time and Date</th>
                            <th>Establishment</th>
                            <th>Victims</th>
                            <th>Damage to Property</th>
                            <th>Fire Type</th>
                            <th>Cause of Fire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($reports) > 0): ?>
                            <?php foreach ($reports as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['report_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['report_title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['fire_location']); ?></td>
                                    <td><?php echo htmlspecialchars($row['incident_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['establishment']); ?></td>
                                    <td><?php echo count(explode(',', $row['victims'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['property_damage']); ?></td>
                                    <td><?php echo htmlspecialchars($row['fire_types']); ?></td>
                                    <td><?php echo htmlspecialchars($row['fire_cause']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">No reports found for the selected years.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</div>

<script src="../js/archivescript.js"></script>
<script src="../js/reportscript.js"></script>
</body>
</html>
