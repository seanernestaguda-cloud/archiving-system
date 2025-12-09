<?php
// session_start();
// if (!isset($_SESSION['username'])) {
//     header("Location: adminlogin.php");
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
$query = "SELECT * FROM users WHERE username = '$username' LIMIT 1";
$result = mysqli_query($conn, $query);

if ($result && $user = mysqli_fetch_assoc($result)) {
    // User data loaded
    $avatar = ($user['avatar'] && file_exists($user['avatar'])) ? $user['avatar'] : '../avatars/default_avatar.png';
} else {
    echo "User not found.";
    exit();
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="reportstyle.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="icon" type="image/png" href="../REPORT.png">
    <style>
        .profile-container {
            max-width: 500px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px #003d7322;
            padding: 30px 40px;
            text-align: center;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #003D73;
            margin-bottom: 15px;
        }

        .profile-info {
            text-align: left;
            margin-top: 10px;
        }

        .profile-info label {
            font-weight: bold;
            color: #003D73;
            margin-bottom: 10px;
        }

        .form-group label {
            font-weight: bold;
        }

        .profile-info p {
            margin: 0 0 12px 0;
            color: #222;
            margin-top: 5px;
            margin-bottom: 10px;
        }

        .profile-actions {
            margin-top: 30px;
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .profile-actions a {
            flex: 0 1 auto;
            background: #003D73;
            color: #fff;
            padding: 10px 22px;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 10px;
        }

        .profile-actions a:hover {
            background: #002D57;
        }

        .profile-card {
            background: #fff;
            max-width: 800px;
            margin: 40px auto 0 auto;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            padding: 40px 32px 32px 32px;
            display: flex;
            gap: 40px;
            align-items: flex-start;
        }

        @media (max-width: 900px) {
            .profile-card {
                flex-direction: column;
                align-items: center;
                padding: 24px 10px;
            }

            .profile-details,
            .profile-info {
                width: 100%;
            }
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

            <div class="profile-card">
                <div class="profile-info" style="text-align:center;">
                    <img class="avatar" src="<?php $avatarPath = '../avatars/' . ($user['avatar'] ? $user['avatar'] : 'default_avatar.png');
                                                echo file_exists($avatarPath) ? $avatarPath : '../avatars/default_avatar.png'; ?>" alt="Avatar" style="width:140px;height:140px;border-radius:50%;border:4px solid #e3e6ea;object-fit:cover;margin-bottom:18px;">
                    <h3 style="margin:0 0 6px 0;font-size:1.4rem;color:#222;font-weight:600;">
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']); ?>
                    </h3>
                    <p style="color:#888;margin:0 0 18px 0;font-size:1rem;">
                        @<?php echo htmlspecialchars($user['username']); ?>
                    </p>
                    <div class="profile-meta" style="font-size:0.98rem;color:#555;margin-bottom:8px;">
                        <div><strong>Department:</strong> <?php echo htmlspecialchars($user['department']); ?></div><br>
                        <div><strong>User Role:</strong> <?php echo htmlspecialchars(ucfirst($user['user_type'])); ?></div><br>
                    </div>
                </div>
                <div class="profile-details" style="flex:2 1 350px;">
                    <h2 style="font-size:1.2rem;color:#4267b2;margin-bottom:18px;font-weight:600;">Profile Details</h2>
                    <div class="form-group-container" style="display:flex;gap:16px;margin-bottom:12px;">
                        <div class="form-group" style="flex:1 1 0;display:flex;flex-direction:column;">
                            <label>Username:</label>
                            <p><?php echo htmlspecialchars($user['username']); ?></p>
                        </div>
                        <div class="form-group" style="flex:1 1 0;display:flex;flex-direction:column;">
                            <label>Department:</label>
                            <p><?php echo htmlspecialchars($user['department']); ?></p>
                        </div>
                    </div>
                    <div class="form-group-container" style="display:flex;gap:16px;margin-bottom:12px;">
                        <div class="form-group" style="flex:1 1 0;display:flex;flex-direction:column;">
                            <label>Contact:</label>
                            <p><?php echo htmlspecialchars($user['contact']); ?></p>
                        </div>
                        <div class="form-group" style="flex:1 1 0;display:flex;flex-direction:column;">
                            <label>Birthday:</label>
                            <p><?php echo htmlspecialchars($user['birthday']); ?></p>
                        </div>
                    </div>
                    <div class="form-group-container" style="display:flex;gap:16px;margin-bottom:12px;">
                        <div class="form-group" style="flex:1 1 0;display:flex;flex-direction:column;">
                            <label>Gender:</label>
                            <p><?php echo htmlspecialchars(ucfirst($user['gender'])); ?></p>
                        </div>
                        <div class="form-group" style="flex:1 1 0;display:flex;flex-direction:column;">
                            <label>Address:</label>
                            <p><?php echo htmlspecialchars($user['address']); ?></p>
                        </div>
                    </div>
                    <div class="form-group-container" style="display:flex;gap:16px;margin-bottom:12px;">
                        <div class="form-group" style="flex:1 1 0;display:flex;flex-direction:column;">
                            <label>User Type:</label>
                            <p><?php echo htmlspecialchars(ucfirst($user['user_type'])); ?></p>
                        </div>
                    </div>
                    <div class="profile-actions" style="margin-top:30px;">
                        <a href="edit_profile.php">Edit Profile</a>
                        <a href="admindashboard.php">Back to Dashboard</a>
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