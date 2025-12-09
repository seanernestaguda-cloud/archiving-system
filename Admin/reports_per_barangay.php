<?php
// session_start();
include 'connection.php';
include('auth_check.php');

// Check if the user is logged in
// if (!isset($_SESSION['username'])) {
//     header("Location: userlogin.php");
//     exit();
// }


$sql_settings = "SELECT system_name FROM settings LIMIT 1";
$result_settings = $conn->query($sql_settings);
$system_name = 'BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM';
if ($result_settings && $row_settings = $result_settings->fetch_assoc()) {
    $system_name = $row_settings['system_name'];
}

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


$sql_barangays = "SELECT DISTINCT fire_location AS barangay FROM fire_incident_reports WHERE deleted_at IS NULL ORDER BY barangay ASC";
$result_barangays = $conn->query($sql_barangays);

$sql_table_data = "SELECT fire_location AS barangay,
    COUNT(*) AS incident_count,
    SUM(CAST(REPLACE(property_damage, ',', '') AS UNSIGNED)) AS total_damage,
    
    -- Count victims
    SUM(
        CASE 
            WHEN victims <> '' THEN LENGTH(victims) - LENGTH(REPLACE(victims, ',', '')) + 1 
            ELSE 0 
        END
    ) AS total_victims,

    -- Count firefighters
    SUM(
        CASE 
            WHEN firefighters <> '' THEN LENGTH(firefighters) - LENGTH(REPLACE(firefighters, ',', '')) + 1 
            ELSE 0 
        END
    ) AS total_firefighters

    FROM fire_incident_reports
    WHERE deleted_at IS NULL
    GROUP BY fire_location
    ORDER BY fire_location ASC;";


$result_table_data = $conn->query($sql_table_data);

// Query for the monthly reports data
$sql_monthly_reports = "SELECT fire_location, 
                               MONTH(incident_date) AS month, 
                               COUNT(*) AS report_count
                        FROM fire_incident_reports
                        WHERE deleted_at IS NULL
                        GROUP BY fire_location, MONTH(incident_date)
                        ORDER BY fire_location, month ASC";
$result_monthly_reports = $conn->query($sql_monthly_reports);

// Initialize arrays for the monthly report data
$monthly_reports = [];
$monthly_labels = [];

// Process the data for the monthly chart
while ($row = $result_monthly_reports->fetch_assoc()) {
    $barangay = $row['fire_location'];
    $month = date('F', mktime(0, 0, 0, $row['month'], 10));  // Convert month number to month name
    $report_count = $row['report_count'];

    if (!isset($monthly_reports[$barangay])) {
        $monthly_reports[$barangay] = [];
    }

    $monthly_reports[$barangay][$month] = $report_count;

    if (!in_array($month, $monthly_labels)) {
        $monthly_labels[] = $month;
        usort($monthly_labels, function ($a, $b) {
            $monthOrder = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            return array_search($a, $monthOrder) - array_search($b, $monthOrder);
        });
    }
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
    <title>Reports Per Barangay</title>
    <style>
        .filter {
            margin: 0px;
            padding: 5px;
            background-color: #f9f9f9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }

        .filter label {
            font-weight: bold;
            margin-right: 10px;
        }

        .filter select {
            padding: 8px;
            font-size: 14px;
            border-radius: 4px;
            border: 1px solid #ccc;
            cursor: pointer;
        }

        .filter select:focus {
            border-color: #007bff;
        }

        .charts-container {
            display: flex;
            justify-content: center;
            gap: 50px;
        }

        #monthlyChart {
            max-width: 700px;
            margin: 20px 10px;
            border: 1px solid;
        }

        .download-excel-btn {
            margin-right: 5px;
            background-color: #003D73;
            /* Green background */
            color: white;
            /* White text */
            padding: 10px 20px;
            /* Adjusted padding for better clickability */
            border: none;
            /* No border */
            /* Rounded corners */
            cursor: pointer;
            /* Pointer cursor on hover */
            font-size: 14px;
            /* Font size */
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
                <h3>Reports Per Barangay</h3>
                <hr class="section-separator full-bleed">
                <div class="charts-container">
                    <!-- Monthly Reports per Barangay -->
                    <canvas id="monthlyChart"></canvas>
                    <div id="barangayLegend" style="display:flex; flex-wrap:wrap; gap:10px; margin:10px 0 0 0;"></div>
                </div>
            </div>

            <div class="card">
                <h3>Barangays</h3>
                <hr class="section-separator full-bleed">
                <div class="top-controls">
                    <button onclick="printTable()" class="create-new-button"><i class="fa-solid fa-print"></i>Print Report</button>
                </div>
                <hr class="section-separator full-bleed">

                <div class="filter">
                    <label for="barangayFilter">Filter by Barangay:</label>
                    <select id="barangayFilter">
                        <option value="">All Barangays</option>
                        <?php while ($barangay = $result_barangays->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($barangay['barangay']); ?>">
                                <?php echo htmlspecialchars($barangay['barangay']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <hr>
                <table class="archive-table">
                    <thead>
                        <tr>
                            <th>Barangay</th>
                            <th>Incident Number</th>
                            <th>Total Damage</th>
                            <th>Civilian Cusualties</th>
                            <th>Firefighter Casualties</th>
                            <th>Total Casualties</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php
                        if ($result_table_data->num_rows > 0) {
                            while ($row = $result_table_data->fetch_assoc()) {

                                $total_casualties = $row['total_victims'] + $row['total_firefighters'];
                                echo "<tr data-barangay='" . htmlspecialchars($row['barangay']) . "'>";
                                echo "<td>" . htmlspecialchars($row['barangay']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['incident_count']) . "</td>";
                                echo "<td>₱" . htmlspecialchars(number_format($row['total_damage'], 2)) . "</td>";
                                echo "<td>" . htmlspecialchars($row['total_victims']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['total_firefighters']) . "</td>";
                                echo "<td>" . htmlspecialchars($total_casualties) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No data available</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div id="logoutModal" class="confirm-delete-modal">
        <div class="modal-content">
            <h3 style="margin-bottom:10px;">Confirm Logout?</h3>
            <hr>
            <p style="margin-bottom:24px;">Are you sure you want to logout?</p>
            <button id="confirmLogout" class="confirm-btn">Logout</button>
            <button id="cancelLogout" class="cancel-btn">Cancel</button>
        </div>
    </div>

    <script>
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
        const barangayFilter = document.getElementById('barangayFilter');
        const tableBody = document.getElementById('tableBody');
        const originalTableRows = [...tableBody.querySelectorAll('tr')];

        // Prepare barangay data for chart
        const barangayData = [
            <?php
            // Reset pointer for table data
            $result_table_data->data_seek(0);
            while ($row = $result_table_data->fetch_assoc()) {
                echo "{ barangay: '" . addslashes($row['barangay']) . "', incident_count: " . intval($row['incident_count']) . " },";
            }
            ?>
        ];

        const barangayLabels = barangayData.map(item => item.barangay);
        const barangayCounts = barangayData.map(item => item.incident_count);
        const barangayColors = barangayLabels.map((_, i) => {
            const hue = Math.round((360 / barangayLabels.length) * i);
            return `hsl(${hue}, 70%, 50%)`;
        });

        barangayFilter.addEventListener('change', function() {
            const selectedBarangay = this.value;

            // Clear the table body
            tableBody.innerHTML = '';

            // Filter the rows
            const filteredRows = selectedBarangay === '' ?
                originalTableRows :
                originalTableRows.filter(row => row.dataset.barangay === selectedBarangay);

            // Check if there are rows to display
            if (filteredRows.length > 0) {
                filteredRows.forEach(row => tableBody.appendChild(row));
            } else {
                const noDataRow = document.createElement('tr');
                noDataRow.innerHTML = `<td colspan="6">No data available</td>`;
                tableBody.appendChild(noDataRow);
            }

            updateBarangayChart(selectedBarangay);
        });

        const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
        const barangayChart = new Chart(ctxMonthly, {
            type: 'bar',
            data: {
                labels: barangayLabels,
                datasets: [{
                    label: 'Incident Count',
                    data: barangayCounts,
                    backgroundColor: barangayColors,
                    hoverBackgroundColor: barangayColors
                }]
            },
            options: {
                responsive: true,
                title: {
                    display: true,
                    text: 'Incidents Count Per Barangay'
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            stepSize: 1,
                            callback: function(value) {
                                if (Number.isInteger(value)) {
                                    return value;
                                }
                            }
                        }
                    }],
                    xAxes: [{
                        scaleLabel: {
                            display: true,
                            labelString: 'Barangay'
                        }
                    }]
                }
            }
        });

        function renderBarangayLegend(selectedBarangay = '') {
            const legendContainer = document.getElementById('barangayLegend');
            legendContainer.innerHTML = '';

            if (selectedBarangay && barangayLabels.includes(selectedBarangay)) {
                const i = barangayLabels.indexOf(selectedBarangay);
                const legendItem = document.createElement('div');
                legendItem.style.display = 'flex';
                legendItem.style.alignItems = 'center';
                legendItem.style.marginRight = '15px';
                legendItem.innerHTML = `
            <span style="display:inline-block;width:20px;height:15px;background:${barangayColors[i]};margin-right:6px;border-radius:3px;"></span>
            <span style="font-size:14px;">${selectedBarangay}</span>
        `;
                legendContainer.appendChild(legendItem);
            } else {
                barangayLabels.forEach((label, i) => {
                    const legendItem = document.createElement('div');
                    legendItem.style.display = 'flex';
                    legendItem.style.alignItems = 'center';
                    legendItem.style.marginRight = '15px';
                    legendItem.innerHTML = `
                <span style="display:inline-block;width:20px;height:15px;background:${barangayColors[i]};margin-right:6px;border-radius:3px;"></span>
                <span style="font-size:14px;">${label}</span>
            `;
                    legendContainer.appendChild(legendItem);
                });
            }
        }

        // Call this after chart is created and after filtering
        renderBarangayLegend();

        function updateBarangayChart(selectedBarangay) {
            if (selectedBarangay === '') {
                barangayChart.data.labels = barangayLabels;
                barangayChart.data.datasets[0].data = barangayCounts;
                barangayChart.data.datasets[0].backgroundColor = barangayColors;
                barangayChart.data.datasets[0].hoverBackgroundColor = barangayColors;
            } else {
                const index = barangayLabels.indexOf(selectedBarangay);
                if (index !== -1) {
                    barangayChart.data.labels = [selectedBarangay];
                    barangayChart.data.datasets[0].data = [barangayCounts[index]];
                    barangayChart.data.datasets[0].backgroundColor = [barangayColors[index]];
                    barangayChart.data.datasets[0].hoverBackgroundColor = [barangayColors[index]];
                } else {
                    barangayChart.data.labels = [];
                    barangayChart.data.datasets[0].data = [];
                    barangayChart.data.datasets[0].backgroundColor = [];
                    barangayChart.data.datasets[0].hoverBackgroundColor = [];
                }
            }
            barangayChart.update();
            renderBarangayLegend(selectedBarangay); // Pass selected barangay to legend
        }


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
            const chartCanvas = document.getElementById('monthlyChart');
            const chartImage = chartCanvas.toDataURL('image/png');

            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Print Table</title>');
            printWindow.document.write('<style>table {width: 100%; border-collapse: collapse;} th, td {border: 1px solid black; padding: 8px; text-align: left;} th {background-color: #f2f2f2;} img {display:block; margin:20px auto; max-width:90%;}</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<h2>Reports Per Barangay</h2>');
            printWindow.document.write('<img id="chartImg" src="' + chartImage + '" alt="Barangay Chart" />');
            printWindow.document.write('<table>');
            printWindow.document.write(document.querySelector('table').outerHTML);
            printWindow.document.write('</table>');
            printWindow.document.write('</body></html>');
            printWindow.document.close();

            // Wait for the image to load before printing
            printWindow.onload = function() {
                const img = printWindow.document.getElementById('chartImg');
                if (img.complete) {
                    printWindow.print();
                } else {
                    img.onload = function() {
                        printWindow.print();
                    };
                }
            };
        }
    </script>
</body>

</html>
<script src="../js/archivescript.js"></script>