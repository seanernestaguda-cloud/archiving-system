<?php
session_start();
include('connection.php');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch the list of barangays for the dropdown
$barangays = [];
$barangay_query = "SELECT DISTINCT fire_location FROM fire_incident_reports ORDER BY fire_location ASC";
$result = $conn->query($barangay_query);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $barangays[] = $row['fire_location'];
    }
}

// Check if the form is submitted and the barangay is set
$selected_barangay = isset($_POST['barangay']) ? $_POST['barangay'] : '';

// Fetch reports based on the selected barangay
if ($selected_barangay) {
    $query = "SELECT report_id, report_title,CONCAT(street, ', ', purok, ', ', fire_location) AS fire_location_combined,  incident_date, establishment, victims, firefighters, property_damage, fire_types, uploader, department 
              FROM fire_incident_reports 
              WHERE fire_location = ?
              ORDER BY report_id ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $selected_barangay);
    $stmt->execute();
    $result = $stmt->get_result();
    $reports = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
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
    <title>Reports Per Barangay</title>
    <style>
        .filter-form select, .filter-form button {
            padding: 10px;
            margin: 15px 0;
            border: 1px solid #003D73;
            border-radius: 5px;
            font-size: 14px;
            background-color: #f9f9f9;
            transition: border-color 0.3s;
        }

        .filter-form button {
            background-color: #bd000a;
            border: none;
            color: white;
        }

        .filter-form button:hover {
            background-color: #810000;
            transition: background-color 0.3s;
        }

        .download-excel-btn{
        margin-right: 5px;
        background-color: #003D73; /* Green background */
        color: white;              /* White text */
        padding: 10px 20px;        /* Adjusted padding for better clickability */
        border: none;             /* No border */     /* Rounded corners */
        cursor: pointer;          /* Pointer cursor on hover */
        font-size: 14px;          /* Font size */
        border-radius: 3px;
    }

    .download-excel-btn i {
            margin-right: 10px;
            font-size: 15px;
        }

    .download-excel-btn:hover {
            background-color: #011e38; /* Darker green on hover */
            transition: background-color 0.3s ease; /* Smooth transition */
        }
        @media print {
    .sidebar, .header, .top-controls, .action-button-container {
        display: none; /* Hide unnecessary elements */
    }
    
    .main-content {
        margin: 0;
        padding: 20px;
    }
    
    .archive-table {
        width: 100%;
        border-collapse: collapse;
    }

    .archive-table th, .archive-table td {
        border: 1px solid black;
        padding: 8px;
        text-align: left;
    }
}

    </style>
</head>
<body>

<div class="dashboard">
<aside class="sidebar">
        <nav>
            <ul>
                <li class = "archive-text"><h4><?php 
include('connection.php');
$sql = "SELECT * FROM settings LIMIT 1";
$result = $conn->query($sql);
$settings = $result ? $result->fetch_assoc() : [];
echo htmlspecialchars($settings['system_name'] ?? 'BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM'); ?></h4></li>
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
            <h2><?php echo htmlspecialchars($settings['system_name'] ?? 'BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM'); ?></h2>
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
            <button class="create-new-button" onclick="printTable()">
                <i class="fa-solid fa-print"></i> Print Reports
            </button>
            </div>
            <h3>Reports Per Barangay</h3>
            <hr>
            <section class="report-section">
            <form action="barangay_reports.php" method="POST" class="filter-form" id="barangayForm">
                <label for="barangay">Select Barangay:</label>
                <select name="barangay" id="barangay" required onchange="document.getElementById('barangayForm').submit();">
                    <option value="">-- Select Barangay --</option>
                    <?php foreach ($barangays as $barangay): ?>
                        <option value="<?php echo htmlspecialchars($barangay); ?>" <?php echo $selected_barangay === $barangay ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($barangay); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
                            <th>Cause of Fire</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($reports) > 0): ?>
                            <?php foreach ($reports as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['report_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['report_title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['fire_location_combined']); ?></td>
                                    <td><?php echo htmlspecialchars($row['incident_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['establishment']); ?></td>
                                    <td>
                                    <?php
                                    $victims_count = empty($row['victims']) ? 0 : substr_count($row['victims'], ',') + 1;
                                    $firefighters_count = empty($row['firefighters']) ? 0 : substr_count($row['firefighters'], ',') + 1;
                                    echo $victims_count + $firefighters_count;
                                    ?>
                                </td>
                                    <td><?php echo htmlspecialchars($row['property_damage']); ?></td>
                                    <td><?php echo htmlspecialchars($row['fire_types']); ?></td>
                                    <td class="action-button-container">
                                    <button class="view-btn" onclick="window.location.href='view_report.php?report_id=<?php echo $row['report_id']; ?>'">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                        <button class="delete-btn" onclick="deleteReport(<?php echo $row['report_id']; ?>)">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        <button class="download-btn" onclick="window.location.href='generate_pdf.php?report_id=<?php echo $row['report_id']; ?>'">
                                            <i class="fa-solid fa-download"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">No reports found for the selected barangay.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</div>

<script src="../js/archivescript.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggles = document.querySelectorAll('.report-dropdown-toggle');

        toggles.forEach(toggle => {
            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                const dropdown = this.closest('.report-dropdown');
                dropdown.classList.toggle('show');

                document.querySelectorAll('.report-dropdown').forEach(item => {
                    if (item !== dropdown) {
                        item.classList.remove('show');
                    }
                });
            });
        });

        window.addEventListener('click', event => {
            if (!event.target.closest('.report-dropdown')) {
                document.querySelectorAll('.report-dropdown').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });
    });

function printTable() {
    var table = document.querySelector(".archive-table").cloneNode(true); // Clone the table

    // Remove the "Action" column
    var headers = table.querySelectorAll("thead th");
    var rows = table.querySelectorAll("tbody tr");

    headers.forEach((header, index) => {
        if (header.textContent.trim() === "Action") {
            header.remove(); // Remove header

            // Remove corresponding column from each row
            rows.forEach(row => {
                row.children[index].remove();
            });
        }
    });

    var printWindow = window.open('', '', 'width=900,height=700'); // Open a new window
    printWindow.document.write(`
        <html>
        <head>
            <title>Print Reports</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid black; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <h2>Reports Per Barangay</h2>
            ${table.outerHTML}
            <script>
                window.onload = function() {
                    window.print();
                    window.onafterprint = function() { window.close(); };
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

</script>
</body>
</html>
