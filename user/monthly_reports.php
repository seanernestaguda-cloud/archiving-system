<?php
session_start();
include 'connection.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: userlogin.php");
    exit();
}

// Verify if the user exists in the database
$username = $_SESSION['username'];
$sql_verify_user = "SELECT COUNT(*) AS user_count FROM users WHERE username = '$username' AND status = 'verified'";
$result_verify_user = $conn->query($sql_verify_user);
$row_verify_user = $result_verify_user->fetch_assoc();

// If the user is deleted or doesn't exist, destroy the session and redirect to login
if ($row_verify_user['user_count'] == 0) {
    session_destroy();
    header("Location: userlogin.php");
    exit();
}


// Check if the form is submitted and the dates are set
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';

// Only proceed if both dates are provided
if ($start_date && $end_date) {
    // Use prepared statement to fetch fire incident reports for the selected date range
    $query = "SELECT report_id, report_title, fire_location, incident_date, establishment, victims, property_damage, fire_types, fire_cause, uploader, department 
              FROM fire_incident_reports 
              WHERE incident_date BETWEEN ? AND ?
              ORDER BY incident_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $reports = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    // Handle the case when no dates are selected
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
    <link rel="icon" type="image/png" href="../RAS.jpg">
    <title>Monthly Reports</title>
    <style>
.date input, .date button{
    padding: 10px;
    margin: 15px 0;
    border: 1px solid #003D73;
    border-radius: 5px;
    font-size: 14px;
    background-color: #f9f9f9;
    transition: border-color 0.3s;
}

.date button{
    background-color: #bd000a;
    border: 1px hidden;
    color: white;
}

.date button:hover{
    background-color: #810000;
    border: 1px hidden;
    color: white;
}

.card {
    position: flex;
    padding: 20px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.button-container {
    position: absolute;
    top: 25px; /* Distance from the top of the card */
    right: 20px; /* Distance from the right edge of the card */
}

.download-excel-btn{
        margin-right: 5px;
        background-color: #003D73; /* Green background */
        color: white;              /* White text */
        padding: 10px 20px;        /* Adjusted padding for better clickability */
        border: none;             /* No border */     /* Rounded corners */
        cursor: pointer;          /* Pointer cursor on hover */
        font-size: 14px;            /* Font size */
    }

.download-excel-btn i {
        margin-right: 10px;
        font-size: 15px;
    }

.download-excel-btn:hover {
        background-color: #011e38; /* Darker green on hover */
        transition: background-color 0.3s ease; /* Smooth transition */
    }
    </style>
</head>
<body>

<div class="dashboard">
<aside class="sidebar">
        <nav>
            <ul>
                <li class = "archive-text"><h4>BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM</h4></li>
                <li><a href="userdashboard.php"><i class="fa-solid fa-gauge"></i> <span>Dashboard</span></a></li>
                <li class = "archive-text"><p>Archives</p></li>
                <li><a href="fire_types.php"><i class="fa-solid fa-fire-flame-curved"></i><span> Fire Types </span></a></li>
                <li><a href="barangay_list.php"><i class="fa-solid fa-building"></i><span> Barangay List </span></a></li>
                <li><a href="fire_incident_report.php"><i class="fa-solid fa-fire"></i><span> Fire Incident</span></a></li>
                <li><a href="fire_safety_inspection_certificate.php"><i class="fa-solid fa-fire-extinguisher"></i><span> Fire Safety</span></a></li>
                
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
            </ul>
        </nav>
    </aside>

    <div class="main-content">
    <header class="header">
    <button id="toggleSidebar" class="toggle-sidebar-btn">
                    <i class="fa-solid fa-bars"></i> <!-- Sidebar toggle icon -->
                </button>
        <h2>BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM</h2>
        <div class="header-right">
        <div class="dropdown">
                    <a href="#" class="user-icon" onclick="toggleProfileDropdown(event)">
                        <i class="fas fa-user-circle" style="font-size: 40px;"></i>
                        <p><?php echo htmlspecialchars($_SESSION['username']); ?><i class="fa-solid fa-caret-down"></i></p>
                    </a>
                <div id="profileDropdown" class="dropdown-content">
                    <a href="myprofile.php"><i class="fa-solid fa-user"></i> View Profile</a>
                    <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

   <div class="card"> 
    <div class = "top-controls">
        <form action="export_reports.php" method="POST">
            <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            <button type="submit" name="download_excel" class="download-excel-btn"><i class="fa-solid fa-download"></i>Download Reports</button>
        </form>
    </div>
    <h2> Monthly Reports </h2>
    <hr>
   <section class="monthly-report-section">
        <form action="monthly_reports.php" method="POST">
            <div class="date">
                <label for="start_date">Start Date:</label>
                <input type="date" name="start_date" id="start_date" required>

                <label for="end_date">End Date:</label>
                <input type="date" name="end_date" id="end_date" required>

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
                        <td colspan="9">No reports found for the selected month and year.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>

<script src="../js/archivescript.js"></script>
<script src="../js/reportscript.js"></script>
<script>
     document.addEventListener('DOMContentLoaded', () => {
    const toggles = document.querySelectorAll('.report-dropdown-toggle');

    toggles.forEach(toggle => {
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            const dropdown = this.closest('.report-dropdown');
            dropdown.classList.toggle('show');

            // Close other dropdowns
            document.querySelectorAll('.report-dropdown').forEach(item => {
                if (item !== dropdown) {
                    item.classList.remove('show');
                }
            });
        });
    });

    // Close dropdown when clicking outside
    window.addEventListener('click', event => {
        if (!event.target.closest('.report-dropdown')) {
            document.querySelectorAll('.report-dropdown').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });
});

</script>
    
</body>
</html>
