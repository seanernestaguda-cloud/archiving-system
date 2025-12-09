<?php
// session_start();

// // Check if the user is logged in
// if (!isset($_SESSION['username'])) {
//     header("Location: userlogin.php");
//     exit();
// }

include 'connection.php';
include('auth_check.php');

$sql_settings = "SELECT system_name FROM settings LIMIT 1";
$result_settings = $conn->query($sql_settings);
$system_name = 'BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM';
if ($result_settings && $row_settings = $result_settings->fetch_assoc()) {
    $system_name = $row_settings['system_name'];
}


$username = $_SESSION['username'];
$sql_user = "SELECT avatar FROM users WHERE username = '$username' LIMIT 1";
$result_user = $conn->query($sql_user);
$avatar = '../avatars/default_avatar.png';
if ($result_user && $row_user = $result_user->fetch_assoc()) {
    if (!empty($row_user['avatar']) && file_exists('../avatars/' . $row_user['avatar'])) {
        $avatar = '../avatars/' . $row_user['avatar'];
    }
}


// Fetch aggregated data grouped by month
$sql_table_data = "SELECT 
    CONCAT(YEAR(incident_date), ' ', MONTHNAME(incident_date)) AS month,
    COUNT(*) AS incident_count,
    SUM(CAST(REPLACE(property_damage, ',', '') AS UNSIGNED)) AS total_damage,
    SUM(
        CASE 
            WHEN victims IS NULL OR victims = '' THEN 0
            ELSE LENGTH(victims) - LENGTH(REPLACE(victims, ',', '')) + 1
        END
    ) AS total_victims
FROM fire_incident_reports
WHERE deleted_at IS NULL
GROUP BY YEAR(incident_date), MONTH(incident_date)
ORDER BY YEAR(incident_date), MONTH(incident_date);";

$result_table_data = $conn->query($sql_table_data);

// Prepare data for chart visualization
$chart_data = [];
while ($row = $result_table_data->fetch_assoc()) {
    $chart_data[] = [
        'month' => $row['month'],
        'incident_count' => $row['incident_count'],
        'total_damage' => $row['total_damage'],
        'total_victims' => $row['total_victims']
    ];
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
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="icon" type="image/png" href="../REPORT.png">
    <title>Monthly Fire Incident Report</title>
    <style>
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            margin-bottom: 32px;
            padding: 32px 28px 28px 28px;
        }
        .card h3 {
            margin-top: 0;
            margin-bottom: 18px;
            font-size: 1.5rem;
            color: #003D73;
            padding-bottom: 10px;
            letter-spacing: 1px;
        }
        .charts-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
        }
        #monthlyChart {
            max-width: 800px;
            width: 100%;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            padding: 12px;
            border: 1px solid;
        }
        .archive-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            background: #fff;
        }
        .archive-table th, .archive-table td {
            border: 1px solid #e0e0e0;
            padding: 12px 10px;
            text-align: center;
        }
        .archive-table th {
            background: #003D73;
            color: #fff;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .archive-table tr:nth-child(even) {
            background: #f7fafd;
        }
        .top-controls {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 10px;
        }
        .create-new-button {
            background: #003D73;
            color: #fff;
            border: none;
            padding: 10px 22px;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .create-new-button:hover {
            background: #00509e;
        }
        @media print {
            body, .main-content, .card {
                background: #fff !important;
                box-shadow: none !important;
            }
            .sidebar, .header, .top-controls, .create-new-button {
                display: none !important;
            }
            .card {
                padding: 0;
                margin: 0;
            }
            .archive-table th, .archive-table td {
                font-size: 12pt;
            }
        }

        .section-separator.full-bleed {
    height: 1px;
    background: linear-gradient(90deg, rgba(0,0,0,0.08), rgba(0,0,0,0.18), rgba(0,0,0,0.08));
    border: none;
    margin: 12px 0 20px;
    width: calc(100% + 40px); /* expand across left+right padding (2 * 20px) */
    margin-left: -20px;        /* shift left by container padding */
    box-sizing: border-box;
}
    </style>
</head>
<body>
    <div class="dashboard">
         <aside class="sidebar">
        <nav>
            <ul>
                <li class = "archive-text"><h4><?php echo htmlspecialchars($system_name); ?></h4></li>
                <li><a href="userdashboard.php"><i class="fa-solid fa-gauge"></i> <span>Dashboard</span></a></li>
                <li class = "archive-text"><p>Archives</p></li>
                <!-- <li><a href="fire_types.php"><i class="fa-solid fa-fire-flame-curved"></i><span> Causes of Fire </span></a></li>
                <li><a href="barangay_list.php"><i class="fa-solid fa-building"></i><span> Barangay List </span></a></li> -->
                <li><a href="myarchives.php"><i class="fa-solid fa-box-archive"></i><span> My Archives </span></a></li>
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
                
                <!-- <li class="archive-text"><span>Maintenance</span></li>
                <li><a href="activity_logs.php"><i class="fa-solid fa-file-invoice"></i><span> Activity Logs </span></a></li>
                <li><a href="departments.php"><i class="fas fa-users"></i><span> Department List </span></a></li>
                <li><a href="manageuser.php"><i class="fas fa-users"></i><span> Manage Users </span></a></li>
                <li><a href="setting.php"><i class="fa-solid fa-gear"></i> <span>Settings</span></a></li> -->
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
                <h3>Monthly Fire Incident Report</h3>
<hr class="section-separator full-bleed">
                <div class="charts-container">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
            <div class="card">
                <h3>Monthly Incident Reports</h3>
                <p> List of Incidents per Month </p>
                <hr class="section-separator full-bleed">
                <div class="top-controls">
                    <button onclick="printTable()" class="create-new-button"><i class="fa-solid fa-print"></i>Print Report</button>
                </div>
                <hr class="section-separator full-bleed">
                <table class="archive-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Incident Count</th>
                            <th>Total Damage</th>
                            <th>Total Casualties</th>
                        </tr>
                    </thead>
                   <tbody>
                        <?php if (empty($chart_data)): ?>
                            <tr>
                                <td colspan="4">No data available</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($chart_data as $data): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($data['month']); ?></td>
                                    <td><?php echo htmlspecialchars($data['incident_count']); ?></td>
                                    <td>₱<?php echo htmlspecialchars(number_format($data['total_damage'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($data['total_victims']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        const chartData = <?php echo json_encode($chart_data); ?>;
        const labels = chartData.map(item => item.month);
    const data = {
    labels: labels,
    datasets: [{
        label: 'Incident Count',
        data: chartData.map(item => item.incident_count),
        borderColor: '#00509e',      // Pinkish line color
        backgroundColor: '#00509e',  // Point color
        fill: false,                 // No area fill
        tension: 0.3,                // Smooth curve
        pointRadius: 5,              // Size of points
        pointHoverRadius: 7,         // Point size on hover
        pointBackgroundColor: '#00509e',
        pointBorderColor: '#00509e',
        borderWidth: 3
    }]
};

const config = {
    type: 'line',
    data: data,
    options: {
        responsive: true,
        plugins: {
            legend: { display: true, position: 'top' },
            title: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    callback: function(value) {
                        if (Number.isInteger(value)) {
                            return value;
                        }
                    }
                },
                title: {
                    display: true,
                    text: 'Number of Incidents',
                    color: '#003D73',
                    font: { weight: 'bold' }
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Month',
                    color: '#003D73',
                    font: { weight: 'bold' }
                }
            }
        }
    }
};
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(ctx, config);

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
            const chartCanvas = document.getElementById('monthlyChart');
            const chartImage = chartCanvas.toDataURL('image/png');
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Print Report</title>');
            printWindow.document.write('<style>body{font-family:Arial,sans-serif;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #333;padding:8px;text-align:center;}th{background:#003D73;color:#fff;}img{display:block;margin:20px auto;max-width:90%;}</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<h2 style="text-align:center;">Monthly Fire Incident Report</h2>');
            printWindow.document.write('<img src="' + chartImage + '" alt="Monthly Chart" />');
            printWindow.document.write('<table>');
            printWindow.document.write(document.querySelector('table').outerHTML);
            printWindow.document.write('</table>');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
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

<div id="logoutModal" class = "confirm-delete-modal">
<div class = "modal-content">   
<h3 style="margin-bottom:10px;">Confirm Logout?</h3>
<hr>
    <p style="margin-bottom:24px;">Are you sure you want to logout?</p>
    <button id="confirmLogout" class = "confirm-btn">Logout</button>
    <button id="cancelLogout" class = "cancel-btn">Cancel</button>
  </div>
</div>
</body>
</html>
<script src = "../js/archivescript.js"></script>