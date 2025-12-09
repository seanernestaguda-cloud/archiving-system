<?php 
include 'connection.php';
include('auth_check.php');
// Check if the user is logged in
// if (!isset($_SESSION['username'])) {
//     header("Location: userlogin.php");
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
$sql_settings = "SELECT system_name FROM settings LIMIT 1";
$result_settings = $conn->query($sql_settings);
$system_name = 'BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM';
if ($result_settings && $row_settings = $result_settings->fetch_assoc()) {
    $system_name = $row_settings['system_name'];
}

$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="reportstyle.css">
        <link rel="stylesheet" href="modal.css">
        <link rel="stylesheet" href="../css/all.min.css">
        <link rel="stylesheet" href="../css/fontawesome.min.css">
        <link rel="icon" type="image/png" href="../REPORT.png">
        <title>Archives</title>
        <style>
.card a > div:hover {
    transform: translateY(-6px) scale(1.03);
    box-shadow: 0 8px 24px rgba(44,62,80,0.13);
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
    <h2 style="text-align:center; margin-bottom: 32px; color: #2c3e50; font-size: 2rem; letter-spacing: 1px;">Archives</h2>
    <div style="display: flex; gap: 32px; justify-content: center; flex-wrap: wrap;">
        <a href="fire_incident_report.php" style="text-decoration: none;">
            <div style="border-radius: 14px; padding: 36px 48px; box-shadow: 0 2px 12px rgba(211,84,0,0.10); display: flex; flex-direction: column; align-items: center; background: #fff7f0; transition: transform 0.2s, box-shadow 0.2s;">
                <i class="fa-solid fa-fire-flame-curved" style="font-size: 2.7rem; color: #d35400; margin-bottom: 14px;"></i>
                <span style="font-size: 1.15rem; color: #d35400; font-weight: 600; margin-bottom: 6px;">Fire Incident Reports</span>
                <span style="font-size: 0.95rem; color: #555; text-align: center;">View and manage all fire incident reports.</span>
            </div>
        </a>
        <a href="fire_safety_inspection_certificate.php" style="text-decoration: none;">
            <div style="border-radius: 14px; padding: 36px 48px; box-shadow: 0 2px 12px rgba(41,128,185,0.10); display: flex; flex-direction: column; align-items: center; background: #f0f7ff; transition: transform 0.2s, box-shadow 0.2s;">
                <i class="fa-solid fa-file-shield" style="font-size: 2.7rem; color: #2980b9; margin-bottom: 14px;"></i>
                <span style="font-size: 1.15rem; color: #2980b9; font-weight: 600; margin-bottom: 6px;">Fire Safety Inspection Reports</span>
                <span style="font-size: 0.95rem; color: #555; text-align: center;">Access fire safety inspection reports.</span>
            </div>
        </a>
    </div>
</div>
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
<script src="../js/archivescript.js"></script>