<?php
include 'connection.php';
include('auth_check.php');


// // Check if the user is logged in
// if (!isset($_SESSION['username'])) {
//     header("Location: adminlogin.php");
//     exit();
// }

$username = $_SESSION['username'];
$avatar = '../avatars/default_avatar.png';

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT avatar FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result_user = $stmt->get_result();

if ($result_user && $row_user = $result_user->fetch_assoc()) {
    if (!empty($row_user['avatar']) && file_exists('../avatars/' . $row_user['avatar'])) {
        $avatar = '../avatars/' . $row_user['avatar'];
    }
}
$stmt->close();

$sql = "SELECT fire_types, COUNT(*) AS fire_count FROM fire_incident_reports WHERE deleted_at IS NULL GROUP BY fire_types ORDER BY fire_count DESC";
$result = $conn->query($sql);

// Fetch total number of reports
$sql_reports = "SELECT COUNT(*) AS total_reports FROM fire_incident_reports WHERE deleted_at IS NULL";
$result_reports = $conn->query($sql_reports);
$total_reports = 0;
if ($result_reports->num_rows > 0) {
    $row_reports = $result_reports->fetch_assoc();
    $total_reports = $row_reports['total_reports'];
}

// Fetch total number of active users (status = 'verified')
$sql_active_users = "SELECT COUNT(*) AS active_users FROM users WHERE status = 'verified'";
$result_active_users = $conn->query($sql_active_users);
$active_users = 0;
if ($result_active_users->num_rows > 0) {
    $row_active_users = $result_active_users->fetch_assoc();
    $active_users = $row_active_users['active_users'];
}

$sql_barangays = "SELECT COUNT(*) AS total_barangays FROM barangays";
$result_barangays = $conn->query($sql_barangays);
$total_barangays = 0;
if ($result_barangays->num_rows > 0) {
    $row_barangays = $result_barangays->fetch_assoc();
    $total_barangays = $row_barangays['total_barangays'];
}

$sql_not_verified_users = "SELECT COUNT(*) AS not_verified_users FROM users WHERE status = 'not verified'";
$result_not_verified_users = $conn->query($sql_not_verified_users);
$not_verified_users = 0;
if ($result_not_verified_users->num_rows > 0) {
    $row_not_verified_users = $result_not_verified_users->fetch_assoc();
    $not_verified_users = $row_not_verified_users['not_verified_users'];
}

// $sql_max_fire_count = "SELECT COUNT(*) AS fire_count
//     FROM fire_incident_reports
//     GROUP BY fire_location
//     ORDER BY fire_count DESC
//     LIMIT 1";
// $result_max_fire_count = $conn->query($sql_max_fire_count);
// $max_fire_count = 0;
// if ($result_max_fire_count && $row = $result_max_fire_count->fetch_assoc()) {
//     $max_fire_count = $row['fire_count'];
// }

// $top_barangays = [];
// if ($max_fire_count > 0) {
//     $sql_top_barangays = "SELECT fire_location
//         FROM fire_incident_reports
//         GROUP BY fire_location
//         HAVING COUNT(*) = $max_fire_count";
//     $result_top_barangays = $conn->query($sql_top_barangays);
//     if ($result_top_barangays && $result_top_barangays->num_rows > 0) {
//         while ($row = $result_top_barangays->fetch_assoc()) {
//             $top_barangays[] = $row['fire_location'];
//         }
//     }
// }
$sql_fsic_reports = "SELECT COUNT(*) AS total_fsic_reports FROM fire_safety_inspection_certificate WHERE deleted_at IS NULL";
$result_fsic_reports = $conn->query($sql_fsic_reports);
$total_fsic_reports = 0;
if ($result_fsic_reports && $row_fsic_reports = $result_fsic_reports->fetch_assoc()) {
    $total_fsic_reports = $row_fsic_reports['total_fsic_reports'];
}

$sql_top_fire_type = "SELECT fire_types, COUNT(*) AS fire_count
    FROM fire_incident_reports
    WHERE deleted_at IS NULL
    GROUP BY fire_types
    ORDER BY fire_count DESC
    LIMIT 1";
$result_top_fire_type = $conn->query($sql_top_fire_type);

$top_fire_type = null;
$fire_count_fire_type = 0;

if ($result_top_fire_type->num_rows > 0) {
    $row = $result_top_fire_type->fetch_assoc();
    $top_fire_type = $row['fire_types'];
    $fire_count_fire_type = $row['fire_count'];
}

$sql_damage = "SELECT SUM(REPLACE(property_damage, ',', '')) AS total_damage FROM fire_incident_reports WHERE deleted_at IS NULL";
$result_damage = $conn->query($sql_damage);
$total_damage = 0;
if ($result_damage && $row_damage = $result_damage->fetch_assoc()) {
    $total_damage = $row_damage['total_damage'];
}

$sql_occupancy = "SELECT occupancy_type, COUNT(*) AS occupancy_count FROM fire_incident_reports WHERE deleted_at IS NULL GROUP BY occupancy_type ORDER BY occupancy_count DESC";
$result_occupancy = $conn->query($sql_occupancy);


$sql_top_barangay = "SELECT fire_location, COUNT(*) AS cnt
    FROM fire_incident_reports
    WHERE deleted_at IS NULL
    GROUP BY fire_location
    ORDER BY cnt DESC
    LIMIT 1";
$result_top_barangay = $conn->query($sql_top_barangay);
$top_barangay_name = null;
$top_barangay_count = 0;
if ($result_top_barangay && $row_tb = $result_top_barangay->fetch_assoc()) {
    $top_barangay_name = $row_tb['fire_location'];
    $top_barangay_count = $row_tb['cnt'];
}

// Fetch system name from settings BEFORE closing connection
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
    <link rel="icon" type="image/png" href="../REPORT.png">
    <link rel="stylesheet" href="reportstyle.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Dashboard</title>
    <style>
        .header {
            position: fixed;
            z-index: 1000;
        }

        .card-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 20px;
            /* Adds spacing between cards */
            padding: 20px;
        }

        .cards {
            background-color: #ffffff;
            /* White background for the cards */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            /* Subtle shadow for a lifted effect */
            border-radius: 10px;
            border-left: 5px solid #003D73;
            width: calc(33.333% - 20px);
            /* Responsive width for 3 cards per row with gap */
            padding: 20px;
            text-align: left;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .cards:hover {
            transform: translateY(-6px) scale(1.03);
            box-shadow: 0 8px 24px rgba(44, 62, 80, 0.13);
        }

        .cards i {
            font-size: 40px;
            /* Icon size */
            color: white;
            /* Primary theme color */
            margin-bottom: 10px;
            background-color: #003D73;
            padding: 10px;
            border-radius: 8px;
        }

        .cards h2 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #444;
            /* Dark text color */
        }

        .cards p {
            font-size: 16px;
            color: #666;
            /* Slightly lighter text color for numbers */
        }

        @media screen and (max-width: 768px) {
            .cards {
                width: calc(50% - 20px);
                /* Adjust width for smaller screens */
            }
        }

        @media screen and (max-width: 480px) {
            .cards {
                width: 100%;
                /* Full width for very small screens */
            }
        }


        /* Add some basic styles for the dropdown */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .show {
            display: block;
        }

        h4 {
            text-align: left;
            padding-bottom: 10px;
        }

        .report-dropdown .fa-chevron-right {
            transition: transform 0.3s ease;
            /* Smooth rotation animation */
        }

        .report-dropdown.show .fa-chevron-right {
            transform: rotate(90deg);
            /* Rotate the caret when dropdown is open */
        }

        .dashboard-header {
            text-align: left;
            margin-bottom: 20px;
        }

        .dashboard-header h1 {
            font-size: 2rem;
            margin-bottom: 5px;
            color: #003D73;
        }

        .dashboard-subtitle {
            font-size: 1rem;
            color: #666;
            margin-bottom: 20px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;
        }

        .dashboard-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
            padding: 24px 16px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 160px;
            transition: box-shadow 0.2s;
            border-left: 2px solid #003D73;
        }

        .dashboard-card:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.13);
        }

        .dashboard-card i {
            font-size: 36px;
            color: #003D73;
            border-radius: 50%;
            padding: 12px;
            margin-bottom: 12px;
        }

        .dashboard-card h2 {
            font-size: 1.1rem;
            color: #003D73;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .dashboard-card .card-number {
            font-size: 1.5rem;
            color: #222;
            font-weight: bold;
        }

        .dashboard-section {
            margin-top: 32px;
        }

        .dashboard-section h3 {
            font-size: 1.2rem;
            color: #003D73;
            margin-bottom: 16px;
            font-weight: 600;
        }

        .barangay-most-fire-list {
            list-style: none;
            padding: 0;
            margin: 0;
            width: 100%;
        }

        .barangay-most-fire-list li {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 8px;
            padding: 6px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .barangay-most-fire-list li:last-child {
            border-bottom: none;
        }

        .fire-icon i {
            color: #e25822;
            font-size: 1.2em;
        }

        .barangay-name {
            font-weight: 600;
            color: #003D73;
            flex: 1;
            text-align: left;
        }

        .fire-badge {
            background: #003D73;
            color: #fff;
            border-radius: 12px;
            padding: 2px 12px;
            font-size: 0.95em;
            font-weight: 500;
        }

        .no-data {
            color: #888;
            font-style: italic;
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
                    <li><a href="fire_types.php"><i class="fa-solid fa-fire-flame-curved"></i><span> Causes of Fire
                            </span></a></li>
                    <li><a href="barangay_list.php"><i class="fa-solid fa-map-location-dot"></i><span> Barangay List
                            </span></a></li>
                    <li><a href="myarchives.php"><i class="fa-solid fa-box-archive"></i><span> My Archives</span></a>
                    </li>
                    <li><a href="archives.php"><i class="fa-solid fa-fire"></i><span> Archives </span></a></li>

                    <li class="report-dropdown">
                        <a href="#" class="report-dropdown-toggle">
                            <i class="fa-solid fa-chart-column"></i>
                            <span>Reports</span>
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                        <ul class="report-dropdown-content">
                            <li><a href="reports_per_barangay.php"><i class="fa-solid fa-chart-column"></i> Reports per
                                    Barangay</a></li>
                            <li><a href="monthly_reports_chart.php"><i class="fa-solid fa-chart-column"></i> Reports per
                                    Month </a></li>
                            <li><a href="year_to_year_comparison.php"><i class="fa-regular fa-calendar-days"></i> Year
                                    to Year Comparison </a></li>
                        </ul>
                    </li>

                    <li class="archive-text"><span>Maintenance</span></li>
                    <li><a href="activity_logs.php"><i class="fa-solid fa-file-invoice"></i><span> Activity Logs
                            </span></a></li>
                    <li><a href="departments.php"><i class="fas fa-users"></i><span> Department List </span></a></li>
                    <li><a href="manageuser.php"><i class="fas fa-users"></i><span> Manage Users </span></a></li>
                    <li><a href="setting.php"><i class="fa-solid fa-gear"></i> <span>Settings</span></a></li>
                </ul>
            </nav>
        </aside>
        <div class="main-content">
            <header class="header">
                <button id="toggleSidebar" class="toggle-sidebar-btn">
                    <i class="fa-solid fa-bars"></i> <!-- Sidebar toggle icon -->
                </button>
                <h2><?php echo htmlspecialchars($system_name); ?></h2>
                <div class="header-right">
                    <div class="dropdown">
                        <a href="#" class="user-icon" onclick="toggleDropdown(event)"
                            style="display:flex;align-items:center;gap:0px;">
                            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar"
                                style="width:40px;height:40px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:0px;">
                            <p><?php echo htmlspecialchars($_SESSION['username']); ?><i
                                    class="fa-solid fa-caret-down"></i></p>
                        </a>
                        <div id="profileDropdown" class="dropdown-content">
                            <a href="myprofile.php"><i class="fa-solid fa-user"></i> View Profile</a>
                            <a href="logout.php" id="logoutLink"><i class="fa-solid fa-right-from-bracket"></i>
                                Logout</a>
                        </div>
                    </div>
                </div>
            </header>
            <div class="card">
                <div class="dashboard-header">
                    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
                    <hr class="section-separator full-bleed">
                    <p class="dashboard-subtitle">Overview & Statistics</p>
                </div>
                <div class="card-container dashboard-grid">
                    <!-- Total Reports -->

                    <div class="dashboard-card">
                        <i class="fas fa-folder"></i>
                        <h2>Archived Reports</h2>
                        <p class="card-number">
                            <?php echo $total_reports + $total_fsic_reports; ?>
                        </p>
                    </div>

                    <div class="dashboard-card">
                        <i class="fa-solid fa-fire-flame-curved"></i>
                        <h2>Fire Incident Reports</h2>
                        <p class="card-number"><?php echo $total_reports; ?></p>
                    </div>

                    <div class="dashboard-card">
                        <i class="fa-solid fa-file-shield"></i>
                        <h2>Fire Safety Inspection Reports</h2>
                        <p class="card-number"><?php echo $total_fsic_reports; ?></p>
                    </div>
                    <div class="dashboard-card">
                        <i class="fa-solid fa-map-location-dot"></i>
                        <h2>Barangays</h2>
                        <p class="card-number"><?php echo $total_barangays; ?></p>
                    </div>

                    <!-- ADDED: Barangay with Most Fires -->
                    <div class="dashboard-card">
                        <i class="fa-solid fa-map-location-dot"></i>
                        <h2>Barangay with Most Fires</h2>
                        <p class="card-number">
                            <?php
                            if ($top_barangay_name) {
                                echo htmlspecialchars($top_barangay_name) . " (" . $top_barangay_count . " incident(s))";
                            } else {
                                echo '<span class="no-data">No data</span>';
                            }
                            ?>
                        </p>
                    </div>

                    <div class="dashboard-card">
                        <i class="fas fa-users"></i>
                        <h2>Verified Users</h2>
                        <p class="card-number"><?php echo $active_users; ?></p>
                    </div>
                    <div class="dashboard-card">
                        <i class="fas fa-user-slash"></i>
                        <h2>Not Verified Users</h2>
                        <p class="card-number"><?php echo $not_verified_users; ?></p>
                    </div>
                </div>

                <div class="dashboard-section">
                    <h3>Fire Causes</h3>
                    <div class="card-container dashboard-grid">
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $fireTypeRaw = $row['fire_types'];
                                $fireType = strtolower($fireTypeRaw);
                                $icon = 'fa-fire';
                                $displayType = htmlspecialchars($fireTypeRaw);
                                if (is_null($fireTypeRaw) || trim($fireTypeRaw) === '') {
                                    $icon = 'fa-search';
                                    $displayType = 'Under Investigation';
                                } else {
                                    switch (true) {
                                        case strpos($fireType, 'electrical connection') !== false:
                                        case strpos($fireType, 'electrical appliances') !== false:
                                        case strpos($fireType, 'electrical machineries') !== false:
                                            $icon = 'fa-bolt'; // electrical
                                            break;
                                        case strpos($fireType, 'spontaneous combustion') !== false:
                                            $icon = 'fa-flask'; // chemical/combustion
                                            break;
                                        case strpos($fireType, 'open flame') !== false:
                                            $icon = 'fa-fire-burner';
                                            break;
                                        case strpos($fireType, 'lpg explosion') !== false:
                                        case strpos($fireType, 'chemicals/lpg leaking') !== false:
                                            $icon = 'fa-gas-pump';
                                            break;
                                        case strpos($fireType, 'lighted cigarette') !== false:
                                            $icon = 'fa-smoking';
                                            break;
                                        case strpos($fireType, 'pyrotechnics') !== false:
                                            $icon = 'fa-rocket'; // not in FA, fallback
                                            break;
                                        case strpos($fireType, 'matchstick') !== false:
                                        case strpos($fireType, 'lighter') !== false:
                                            $icon = 'fa-fire';
                                            break;
                                        case strpos($fireType, 'incendiary device') !== false:
                                        case strpos($fireType, 'ignited flammable liquids') !== false:
                                            $icon = 'fa-bomb';
                                            break;
                                        case strpos($fireType, 'lightning') !== false:
                                            $icon = 'fa-cloud-bolt';
                                            break;
                                        case strpos($fireType, 'mechanical collision') !== false:
                                            $icon = 'fa-gears';
                                            break;
                                        case strpos($fireType, 'airplane crash') !== false:
                                            $icon = 'fa-plane';
                                            break;
                                        case strpos($fireType, 'bomb explosion') !== false:
                                            $icon = 'fa-bomb';
                                            break;
                                        case strpos($fireType, 'others') !== false:
                                            $icon = 'fa-ellipsis-h';
                                            break;
                                        default:
                                            $icon = 'fa-fire';
                                    }
                                }
                        ?>
                                <div class="dashboard-card">
                                    <i class="fa-solid <?php echo $icon; ?>"></i>
                                    <h2><?php echo $displayType; ?></h2>
                                    <p class="card-number"><?php echo $row['fire_count']; ?> incident(s)</p>
                                </div>
                        <?php
                            }
                        }
                        ?>
                    </div>
                </div>
                <div class="dashboard-section">
                    <h3>Occupancy Types</h3>
                    <div class="card-container dashboard-grid">
                        <?php
                        if ($result_occupancy && $result_occupancy->num_rows > 0) {
                            while ($row = $result_occupancy->fetch_assoc()) {
                                $icon = 'fa-building';
                                switch (strtolower($row['occupancy_type'])) {
                                    case 'residential':
                                        $icon = 'fa-house-chimney';
                                        break;
                                    case 'commercial':
                                        $icon = 'fa-store';
                                        break;
                                    case 'industrial':
                                        $icon = 'fa-industry';
                                        break;
                                    case 'institutional':
                                        $icon = 'fa-school';
                                        break;
                                    case 'vehicular':
                                        $icon = 'fa-car';
                                        break;
                                    case 'others':
                                        $icon = 'fa-ellipsis-h';
                                        break;
                                    default:
                                        $icon = 'fa-building';
                                }
                        ?>
                                <div class="dashboard-card">
                                    <i class="fa-solid <?php echo $icon; ?>"></i>
                                    <h2><?php echo htmlspecialchars($row['occupancy_type']); ?></h2>
                                    <p class="card-number"><?php echo $row['occupancy_count']; ?> incident(s)</p>
                                </div>
                        <?php
                            }
                        }
                        ?>
                    </div>
                </div>
                <div class="dashboard-card">
                    <i class="fas fa-coins"></i>
                    <h2>Total Property Damage</h2>
                    <p class="card-number">
                        ₱<?php echo number_format((float) str_replace(',', '', $total_damage), 2, '.', ','); ?></p>
</body>
<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="confirm-delete-modal">
    <div class="modal-content">
        <h3 style="margin-bottom:10px;">Confirm Logout?</h3>
        <hr>
        <p style="margin-bottom:24px;">Are you sure you want to logout?</p>
        <button id="confirmLogout" class="confirm-btn">Logout</button>
        <button id="cancelLogout" class="cancel-btn">Cancel</button>
    </div>
</div>

</html>
<script>
    // Sidebar toggle logic
    document.getElementById('toggleSidebar').addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');

        sidebar.classList.toggle('collapsed'); // Toggle the 'collapsed' class
        mainContent.classList.toggle('collapsed'); // Adjust main content margin
        mainContent.classList.toggle('expanded'); // Adjust expanded class
    });

    // Close Dropdown When Clicking Outside
    function toggleDropdown(event) {
        event.preventDefault();
        document.getElementById("profileDropdown").classList.toggle("show");
    }

    window.onclick = function(event) {
        if (!event.target.matches('.user-icon') && !event.target.matches('.user-icon *')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    };

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

    // Logout confirmation modal logic
    document.getElementById('logoutLink').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('logoutModal').style.display = 'flex';
    });

    document.getElementById('confirmLogout').addEventListener('click', function() {
        window.location.href = 'logout.php';
    });

    document.getElementById('cancelLogout').addEventListener('click', function() {
        document.getElementById('logoutModal').style.display = 'none';
    });

    // Optional: Close modal when clicking outside the modal box
    document.getElementById('logoutModal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
</script>