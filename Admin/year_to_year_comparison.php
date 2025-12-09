<?php
// session_start();

include 'connection.php';
include('auth_check.php');

// // Check if the user is logged in
// if (!isset($_SESSION['username'])) {
//     header("Location: adminlogin.php");
//     exit();
// }

$username = $_SESSION['username'];
$sql_user = "SELECT avatar FROM users WHERE username = ? LIMIT 1";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("s", $username);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$avatar = '../avatars/default_avatar.png';
if ($result_user && $row_user = $result_user->fetch_assoc()) {
    if (!empty($row_user['avatar']) && file_exists('../avatars/' . $row_user['avatar'])) {
        $avatar = '../avatars/' . $row_user['avatar'];
    }
}
$stmt_user->close();


// Fetch unique years from the incident_date column

// Fetch all available years into an array

// Fetch all available years into an array (ascending order for earliest year first)
$sql_years = "SELECT DISTINCT YEAR(incident_date) AS year FROM fire_incident_reports WHERE deleted_at IS NULL ORDER BY year ASC";
$result_years = $conn->query($sql_years);
$years = [];
if ($result_years) {
    while ($row = $result_years->fetch_assoc()) {
        $years[] = $row['year'];
    }
}

// Set default years to the two earliest years if not filtered
if (isset($_GET['year1']) && is_numeric($_GET['year1'])) {
    $year1 = intval($_GET['year1']);
} else {
    $year1 = isset($years[0]) ? $years[0] : null;
}
if (isset($_GET['year2']) && is_numeric($_GET['year2'])) {
    $year2 = intval($_GET['year2']);
} else {
    $year2 = isset($years[1]) ? $years[1] : (isset($years[0]) ? $years[0] : null);
}

// For dropdowns, fetch years again in ascending order
$sql_years_asc = "SELECT DISTINCT YEAR(incident_date) AS year FROM fire_incident_reports WHERE deleted_at IS NULL ORDER BY year ASC";
$result_years_asc = $conn->query($sql_years_asc);

$table_data = [];
if ($year1 && $year2) {
    $sql_table_data = "
        SELECT 
            MONTHNAME(incident_date) AS month,
            SUM(CASE WHEN YEAR(incident_date) = ? THEN 1 ELSE 0 END) AS year1_total_incidents,
            SUM(CASE WHEN YEAR(incident_date) = ? THEN 1 ELSE 0 END) AS year2_total_incidents
        FROM fire_incident_reports
        WHERE YEAR(incident_date) IN (?, ?) AND deleted_at IS NULL
        GROUP BY MONTH(incident_date)
        ORDER BY MONTH(incident_date);
    ";
    $stmt = $conn->prepare($sql_table_data);
    $stmt->bind_param('iiii', $year1, $year2, $year1, $year2);
    $stmt->execute();
    $result_table_data = $stmt->get_result();
    while ($row = $result_table_data->fetch_assoc()) {
        $table_data[] = [
            'month' => $row['month'],
            'year1_total_incidents' => $row['year1_total_incidents'],
            'year2_total_incidents' => $row['year2_total_incidents']
        ];
    }
    $stmt->close();
}


$total_year1_incidents = 0;
$total_year2_incidents = 0;

foreach ($table_data as $row) {
    // Sum up incidents for both years
    $total_year1_incidents += $row['year1_total_incidents'];
    $total_year2_incidents += $row['year2_total_incidents'];
}

// Add a row with total incidents
$table_data[] = [
    'month' => 'Total',
    'year1_total_incidents' => $total_year1_incidents,
    'year2_total_incidents' => $total_year2_incidents
];


$sql_settings = "SELECT system_name FROM settings LIMIT 1";
$result_settings = $conn->query($sql_settings);
$system_name = 'BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM';
if ($result_settings && $row_settings = $result_settings->fetch_assoc()) {
    $system_name = $row_settings['system_name'];
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="../js/libs/Chart.min.js"></script>
    <link rel="stylesheet" href="reportstyle.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="icon" type="image/png" href="../REPORT.png">
    <title>Year-to-Year Comparison</title>
    <style>
        .show {
            display: block;
        }

        .date select,
        .date button {
            padding: 10px;
            margin: 15px 0;
            border: 1px solid #003D73;
            border-radius: 5px;
            font-size: 14px;
            background-color: #f9f9f9;
            transition: border-color 0.3s;
        }

        .date button {
            background-color: #bd000a;
            border: 1px hidden;
            color: white;
        }

        .date button:hover {
            background-color: #810000;
            border: 1px hidden;
            color: white;
        }

        .section-separator.full-bleed {
            height: 1px;
            background: linear-gradient(90deg, rgba(0, 0, 0, 0.08), rgba(0, 0, 0, 0.18), rgba(0, 0, 0, 0.08));
            border: none;
            margin: 12px 0 20px;
            width: calc(100% + 40px);
            /* expand across left+right padding (2 * 20px) */
            margin-left: -20px;
            /* shift left by container padding */
            box-sizing: border-box;
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <aside class="sidebar">
            <nav>
                <ul>
                    <li class="archive-text">
                        <h4><?php echo htmlspecialchars($system_name); ?></h4>
                    </li>
                    <li><a href="admindashboard.php"><i class="fa-solid fa-gauge"></i> <span>Dashboard</span></a></li>
                    <li class="archive-text">
                        <p>Archives</p>
                    </li>
                    <li><a href="fire_types.php"><i class="fa-solid fa-fire-flame-curved"></i><span> Causes of Fire </span></a></li>
                    <li><a href="barangay_list.php"><i class="fa-solid fa-map-location-dot"></i><span> Barangay List </span></a></li>
                    <li><a href="myarchives.php"><i class="fa-solid fa-box-archive"></i><span> My Archives</span></a></li>
                    <li><a href="archives.php"><i class="fa-solid fa-fire"></i><span> Archives </span></a></li>

                    <li class="report-dropdown">
                        <a href="#" class="report-dropdown-toggle">
                            <i class="fa-solid fa-chart-column"></i>
                            <span>Reports</span>
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                        <ul class="report-dropdown-content">
                            <li><a href="reports_per_barangay.php"><i class="fa-solid fa-chart-column"></i> Reports per Barangay</a></li>
                            <li><a href="monthly_reports_chart.php"><i class="fa-solid fa-chart-column"></i> Reports per Month </a></li>
                            <li><a href="year_to_year_comparison.php"><i class="fa-regular fa-calendar-days"></i> Year to Year Comparison </a></li>
                        </ul>
                    </li>

                    <li class="archive-text"><span>Maintenance</span></li>
                    <li><a href="activity_logs.php"><i class="fa-solid fa-file-invoice"></i><span> Activity Logs </span></a></li>
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
                <h2><?php echo htmlspecialchars($system_name); ?></h2>
                <div class="header-right">
                    <div class="dropdown">
                        <a href="#" class="user-icon" onclick="toggleProfileDropdown(event)">
                            <!-- Add avatar image here -->
                            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" style="width:40px;height:40px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:0px;">
                            <p><?php echo htmlspecialchars($_SESSION['username']); ?><i class="fa-solid fa-caret-down"></i></p>
                        </a>
                        <div id="profileDropdown" class="dropdown-content">
                            <a href="myprofile.php"><i class="fa-solid fa-user"></i> View Profile</a>
                            <a href="#" id="logoutLink"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="card">
                <h3>Year-to-Year Comparison Table</h3>
                <hr class="section-separator full-bleed">
                <div class="top-controls">
                    <button onclick="printTable()" class="create-new-button"><i class="fa-solid fa-print"></i>Print Reports</button>
                </div>
                <hr class="section-separator full-bleed">
                <form method="GET" id="yearFilterForm">


                    <div class="date">
                        <label for="year1">Select First Year:</label>
                        <select name="year1" id="year1">
                            <?php if ($result_years_asc) : ?>
                                <?php while ($row = $result_years_asc->fetch_assoc()): ?>
                                    <option value="<?php echo $row['year']; ?>" <?php echo ($year1 == $row['year']) ? 'selected' : ''; ?>>
                                        <?php echo $row['year']; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                        <label for="year2">Select Second Year:</label>
                        <select name="year2" id="year2">
                            <?php if (!empty($years)) : ?>
                                <?php foreach (array_reverse($years) as $y): ?>
                                    <option value="<?php echo $y; ?>" <?php echo ($year2 == $y) ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <button type="submit">Filter</button>
                </form>
            </div>

            <table class="archive-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th><?php echo htmlspecialchars($year1); ?> Total Incidents</th>
                        <th><?php echo htmlspecialchars($year2); ?> Total Incidents</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($table_data as $row): ?>
                        <tr>
                            <td class="total" style="font-weight: bold" ;><?php echo htmlspecialchars($row['month']); ?></td>
                            <td><?php echo htmlspecialchars($row['year1_total_incidents']); ?></td>
                            <td><?php echo htmlspecialchars($row['year2_total_incidents']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>


            </tbody>
            </table>
        </div>
    </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggles = document.querySelectorAll('.report-dropdown-toggle');

            toggles.forEach(toggle => {
                toggle.addEventListener('click', function(event) {
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

        function printTable() {
            const table = document.querySelector('.archive-table').outerHTML;
            const newWin = window.open('', '', 'width=800, height=600');
            newWin.document.write(`
            <html>
            <head>
                <title>Print Table</title>
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid black; padding: 8px; text-align: center; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                <h3>Year-to-Year Comparison Table</h3>
                ${table}
                <script>
                    window.onload = function() {
                        window.print();
                        window.onafterprint = function() { window.close(); }
                    };
                <\/script>
            </body>
            </html>
        `);
            newWin.document.close();
        }


        document.addEventListener('DOMContentLoaded', function() {
            // Show Confirm Logout Modal
            document.getElementById('logoutLink').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('logoutModal').style.display = 'flex';
                document.getElementById('profileDropdown').classList.remove('show'); // <-- Add this line
            });

            // Handle Confirm Logout
            document.getElementById('confirmLogout').addEventListener('click', function() {
                window.location.href = 'logout.php';
            });

            // Handle Cancel Logout
            document.getElementById('cancelLogout').addEventListener('click', function() {
                document.getElementById('logoutModal').style.display = 'none';
            });
        });

        window.onclick = function(event) {
            // ...existing code...
            const logoutModal = document.getElementById('logoutModal');
            if (event.target === logoutModal) {
                logoutModal.style.display = 'none';
            }
        };
    </script>

    <div id="logoutModal" class="confirm-delete-modal">
        <div class="modal-content">
            <h3 style="margin-bottom:10px;">Confirm Logout?</h3>
            <hr>
            <p style="margin-bottom:24px;">Are you sure you want to logout?</p>
            <button id="confirmLogout" class="confirm-btn">Logout</button>
            <button id="cancelLogout" class="cancel-btn">Cancel</button>
        </div>
    </div>
</body>

</html>
<script src="../js/archivescript.js"></script>