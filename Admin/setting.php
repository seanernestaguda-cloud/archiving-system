
<?php
include 'connection.php';
include('auth_check.php');

$username = $_SESSION['username'];
$sql_user = "SELECT avatar FROM users WHERE username = ? LIMIT 1";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param('s', $username);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$avatar = '../avatars/default_avatar.png';
if ($result_user && $row_user = $result_user->fetch_assoc()) {
    if (!empty($row_user['avatar']) && file_exists('../avatars/' . $row_user['avatar'])) {
        $avatar = '../avatars/' . $row_user['avatar'];
    }
}

// Fetch current settings (assuming a 'settings' table with one row)

$sql = "SELECT * FROM settings LIMIT 1";
$result = $conn->query($sql);
$settings = $result ? $result->fetch_assoc() : [];
$about = $settings['about'] ?? '';

// Fetch system name from settings BEFORE closing connection
$sql_settings = "SELECT system_name FROM settings LIMIT 1";
$result_settings = $conn->query($sql_settings);
$system_name = 'BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM';
if ($result_settings && $row_settings = $result_settings->fetch_assoc()) {
    $system_name = $row_settings['system_name'];
}

// Handle form submission

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $system_name = $_POST['system_name'];
    $contact_email = $_POST['contact_email'];
    $about = $_POST['about'];
    $logo = $settings['logo'] ?? '';

    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
        $logo_name = basename($_FILES['logo']['name']);
        $target_path = "../webfonts/" . $logo_name;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
            $logo = $logo_name;
        }
    }

    // Update settings
    $stmt = $conn->prepare("UPDATE settings SET system_name=?, contact_email=?, logo=?, about=?");
    $stmt->bind_param("ssss", $system_name, $contact_email, $logo, $about);
    $stmt->execute();
    $stmt->close();

    header("Location: setting.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings</title>
    <link rel="stylesheet" href="reportstyle.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="icon" type="image/png" href="../REPORT.png">
    <style>
        .header{
            position: fixed;
            z-index: 1000;
        }
        .card{
            max-width: 700px;
            width: 90%;
            margin: 90px auto 40px;
            padding: 18px;
            box-sizing: border-box;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.08);
        }
        .form-header{
            background: #003D73;
            color: white;
            padding: 15px;
            margin: 0px;
            margin-bottom:20px;
            text-align: center;
            font-size: 18px;
            border-radius: 10px;
        }
        .form-group {
            margin: 10px 0;
        }
        label {
            color: #003D73;
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            border-bottom: 1px solid #444;
        }
        input[type="file"] {
            margin-top: 8px;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }
        button[type="submit"] {
            background-color: #003D73;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }
        button[type="submit"]:hover {
            background-color: #002D57;
        }
        .logo-preview {
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <nav>
            <ul>
                <li class = "archive-text"><h4><?php echo htmlspecialchars($system_name); ?></h4></li>
                <li><a href="admindashboard.php"><i class="fa-solid fa-gauge"></i> <span>Dashboard</span></a></li>
                <li class = "archive-text"><p>Archives</p></li>
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
        <div class="form-header">
            <h2><i class="fa-solid fa-gear"></i> System Settings</h2>
        </div>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="system_name">System Name:</label>
                <input type="text" name="system_name" id="system_name" value="<?php echo htmlspecialchars($settings['system_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="contact_email">Contact Email:</label>
                <input type="email" name="contact_email" id="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="about">About (HTML allowed):</label>
                <textarea name="about" id="about" rows="7" style="width:100%;font-size:16px;"><?php echo htmlspecialchars($about); ?></textarea>
            </div>
            <div class="form-group">
               <label for="logo" class="upload-btn">Choose Logo</label>
                <input type="file" name="logo" id="logo" accept="image/*" style="display:none;">
                <?php if (!empty($settings['logo'])): ?>
                    <div class="logo-preview">
                        <img src="../webfonts/<?php echo htmlspecialchars($settings['logo']); ?>" width="80" style="border-radius:8px;">
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-actions">
                <button type="submit">Update Settings</button>
            </div>
        </form>
    </div>

    <div id="successModal" class="success-modal" style="display: none;">
    <div class="success-modal-content">
    <i class="fa-regular fa-circle-check"></i><h2>Success!</h2>
        <p id="successMessage"> </p>
    </div>
</div>


<div id="logoutModal" class = "confirm-delete-modal">
<div class = "modal-content">   
<h3 style="margin-bottom:10px;">Confirm Logout?</h3>
<hr>
    <p style="margin-bottom:24px;">Are you sure you want to logout?</p>
    <button id="confirmLogout" class = "confirm-btn">Logout</button>
    <button id="cancelLogout" class = "cancel-btn">Cancel</button>
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
    if (window.location.search.includes('success=1')) {
        var modal = document.getElementById('successModal');
        var message = document.getElementById('successMessage');
        if (modal) {
            modal.style.display = 'block';
            if (message) {
                message.textContent = 'Settings updated successfully!';
            }
            setTimeout(function() {
                modal.style.display = 'none';
            }, 3000);
        }
        // Remove ?success=1 from the URL
        if (window.history.replaceState) {
            const url = window.location.pathname + window.location.search.replace(/(\?|&)success=1(&)?/, function(match, p1, p2) {
                if (p1 && p2) return p1;
                return '';
            });
            window.history.replaceState({}, document.title, url);
        }
    }
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
</body>
</html>
<script src="../js/archivescript.js"></script>