<?php
// session_start();
// if (!isset($_SESSION['username'])) {
//     header("Location: adminlogin.php");
//     exit();
// }

include 'connection.php'; // Include database connection
include 'auth_check.php';

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
if (isset($_GET['id']) || isset($_POST['id'])) {
    $userId = isset($_GET['id']) ? $_GET['id'] : $_POST['id'];

    // Fetch user details
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        $_SESSION['message'] = "User not found.";
        $_SESSION['message_type'] = "error";
        header("Location: manageuser.php");
        exit();
    }
} else {
    header("Location: manageuser.php");
    exit();
}

$departmentQuery = "SELECT * FROM departments";
$departmentResult = $conn->query($departmentQuery);

// Initialize avatar variables to avoid undefined variable warnings
$avatarFileName = '';
$avatarPath = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update user details (including avatar)
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $middleName = $_POST['middle_name'];
    $birthday = $_POST['birthday'];
    $address = $_POST['address'];
    $username = $_POST['username'];
    $department = $_POST['department'];
    $contact = $_POST['contact'];
    $user_type = $_POST['user_type'];
    $status = $_POST['status'];

    // Prevent using "admin" as username
    if (strtolower($username) === 'admin') {
        $_SESSION['show_error_modal'] = true;
        $_SESSION['error_message'] = "Error: The username 'admin' is not allowed.";
        header("Location: edit_user.php?id=$userId");
        exit();
    }

    // Check for duplicate username (excluding current user)
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt_check->bind_param("si", $username, $userId);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $_SESSION['show_error_modal'] = true;
        $_SESSION['error_message'] = "Error: The username '$username' is already taken.";
        header("Location: edit_user.php?id=$userId");
        exit();
    }
    $stmt_check->close();

    // Handle avatar upload
    $avatarFileName = $user['avatar'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatarTempName = $_FILES['avatar']['tmp_name'];
        $avatarOriginalName = basename($_FILES['avatar']['name']);
        $avatarFileName = uniqid() . '_' . $avatarOriginalName;
        $avatarDestination = '../avatars/' . $avatarFileName;
        if (move_uploaded_file($avatarTempName, $avatarDestination)) {
            // Optionally delete old avatar file if needed
        }
    }

    $stmt = $conn->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, birthday = ?, address = ?, username = ?, department = ?, contact = ?, user_type = ?, status = ?, avatar = ? WHERE id = ?");
    $stmt->bind_param("sssssssssssi", $firstName, $middleName, $lastName, $birthday, $address, $username, $department, $contact, $user_type, $status, $avatarFileName, $userId);
    $stmt->execute();
    if ($_SESSION['username'] !== $username && $_SESSION['username'] == $user['username']) {
        $_SESSION['username'] = $username;
    }

    $_SESSION['show_success_modal'] = true;
    $_SESSION['success_message'] = "User updated successfully!";
    $_SESSION['redirect_after_modal'] = true;
    header("Location: edit_user.php?id=$userId");
    exit();
}


// Success modal logic
$showSuccessModal = false;
$successMessage = '';
$redirectAfterModal = false;
if (isset($_SESSION['show_success_modal']) && $_SESSION['show_success_modal']) {
    $showSuccessModal = true;
    $successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
    $redirectAfterModal = isset($_SESSION['redirect_after_modal']) ? $_SESSION['redirect_after_modal'] : false;
    unset($_SESSION['show_success_modal']);
    unset($_SESSION['success_message']);
    unset($_SESSION['redirect_after_modal']);
}

// Error modal logic
$showErrorModal = false;
$errorMessage = '';
if (isset($_SESSION['show_error_modal']) && $_SESSION['show_error_modal']) {
    $showErrorModal = true;
    $errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
    unset($_SESSION['show_error_modal']);
    unset($_SESSION['error_message']);
}
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
    <title>Edit User</title>
    <style>
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
            border-top: solid 5px #003D73;
        }

        .profile-info {
            flex: 1 1 220px;
            text-align: center;
        }

        .profile-info img.avatar {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 4px solid #e3e6ea;
            object-fit: cover;
            margin-bottom: 18px;
        }

        .profile-info h3 {
            margin: 0 0 6px 0;
            font-size: 1.4rem;
            color: #222;
            font-weight: 600;
        }

        .profile-info p {
            color: #888;
            margin: 0 0 18px 0;
            font-size: 1rem;
        }

        .profile-info .profile-meta {
            font-size: 0.98rem;
            color: #555;
            margin-bottom: 8px;
        }

        .edit-form {
            flex: 2 1 350px;
        }

        .edit-form h2 {
            font-size: 1.2rem;
            color: #003D73;
            margin-bottom: 18px;
            font-weight: 600;
        }

        .form-group-container {
            display: flex;
            gap: 16px;
            margin-bottom: 12px;
        }

        .form-group {
            flex: 1 1 0;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 0.97rem;
            color: #555;
            margin-bottom: 4px;
        }

        .form-group input,
        .form-group select {
            padding: 9px 12px;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            font-size: 1rem;
            background: #f9fafb;
            margin-bottom: 0;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #003D73;
            background: #fff;
        }

        .edit-form button[type="submit"] {
            background: #003D73;
            color: #fff;
            border: none;
            padding: 12px 28px;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            margin-right: 0px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .edit-form button[type="submit"]:hover {
            background: #002D57;
        }

        .edit-form button[type="button"] {
            background: #e3e6ea;
            color: #222;
            border: none;
            padding: 12px 28px;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .edit-form button[type="button"]:hover {
            background: #d1d5db;
        }

        /* Status badge styles */
        .status-badge {
            display: inline-block;
            padding: 5px 18px;
            border-radius: 20px;
            font-size: 1.05rem;
            font-weight: 700;
            color: #fff;
            background: #1ca21c;
            text-transform: lowercase;
            margin-top: 2px;
        }

        .status-badge.verified {
            background: #1ca21c;
        }

        .status-badge.not-verified {
            background: #bd000a;
        }

        /* Status dropdown color styles */
        #status.verified {
            color: #1ca21c;
        }

        #status.not-verified {
            color: #bd000a;
        }

        /* Color the dropdown options (Webkit/Blink/Edge/Chrome) */
        #status option[value="verified"] {
            color: #1ca21c;
        }

        #status option[value="not verified"] {
            color: #bd000a;
        }

        @media (max-width: 900px) {

            .edit-form,
            .profile-info {
                width: 100%;
            }
        }

        .change-avatar {
            background: #003D73;
            color: #fff;
            border: none;
            border-radius: 20px;
            padding: 5px 16px;
            font-size: 0.95rem;
            cursor: pointer;
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
        }

        .change-avatar:hover {
            background-color: #002D57;
        }
    </style>
</head>

<body>
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
                <li><a href="myarchives.php"><i class="fa-solid fa-box-archive"></i><span> My Archives</span></a></li>
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
                        <li><a href="year_to_year_comparison.php"><i class="fa-regular fa-calendar-days"></i> Year to
                                Year Comparison </a></li>
                    </ul>
                </li>

                <li class="archive-text"><span>Maintenance</span></li>
                <li><a href="activity_logs.php"><i class="fa-solid fa-file-invoice"></i><span> Activity Logs </span></a>
                </li>
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
                        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar"
                            style="width:40px;height:40px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:0px;">
                        <p><?php echo htmlspecialchars($_SESSION['username']); ?><i class="fa-solid fa-caret-down"></i>
                        </p>
                    </a>
                    <div id="profileDropdown" class="dropdown-content">
                        <a href="myprofile.php"><i class="fa-solid fa-user"></i> View Profile</a>
                        <a href="#" id="logoutLink"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </div>
                </div>
            </div>
        </header>
        <br>

        <div class="profile-card">
            <div class="profile-info">
                <?php
                if (isset($user['avatar']) && !empty($user['avatar'])) {
                    $avatarPath = '../avatars/' . $user['avatar'];
                } else {
                    $avatarPath = '../avatars/default_avatar.png';
                }
                ?>

                <div style="position: relative; display: inline-block;">
                    <img src="<?php echo $avatarPath; ?>" alt="User Avatar" class="avatar" id="profileAvatar">
                    <label for="avatarInput" class="change-avatar">Change</label>
                    <!-- <input type="file" name="avatar" id="avatarInput" style="display:none;" accept="image/*"> -->
                </div>
                <h3><?= htmlspecialchars($user['first_name']) ?> <?= htmlspecialchars($user['middle_name']) ?>
                    <?= htmlspecialchars($user['last_name']) ?>
                </h3>
                <p>@<?= htmlspecialchars($user['username']) ?></p>
                <div class="profile-meta">
                    <div><strong>Department:</strong> <?= htmlspecialchars($user['department']) ?></div><br>
                    <div><strong>User Type:</strong> <?= htmlspecialchars($user['user_type']) ?></div><br>
                    <div><strong>Status:</strong>
                        <?php if (strtolower($user['status']) === 'verified'): ?>
                            <span class="status-badge verified">verified</span>
                        <?php else: ?>
                            <span class="status-badge not-verified">not verified</span>
                        <?php endif; ?>
                    </div><br>
                </div>
            </div>
            <form class="edit-form" method="POST" action="edit_user.php?id=<?php echo $userId; ?>"
                enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $userId; ?>">
                <input type="file" name="avatar" id="avatarInput" style="display:none;" accept="image/*">
                <h2>Edit Profile</h2>
                <div class="form-group-container">
                    <div class="form-group">
                        <label for="first_name">First Name:</label>
                        <input type="text" name="first_name" id="first_name"
                            value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="middle_name">Middle Name:</label>
                        <input type="text" name="middle_name" id="middle_name"
                            value="<?php echo htmlspecialchars($user['middle_name']); ?>">
                    </div>
                </div>
                <div class="form-group-container">
                    <div class="form-group">
                        <label for="last_name">Last Name:</label>
                        <input type="text" name="last_name" id="last_name"
                            value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="birthday">Birthday:</label>
                        <input type="date" name="birthday" id="birthday"
                            value="<?php echo htmlspecialchars($user['birthday']); ?>" required>
                    </div>
                </div>
                <div class="form-group-container">
                    <div class="form-group">
                        <label for="address">Address:</label>
                        <input type="text" name="address" id="address"
                            value="<?php echo htmlspecialchars($user['address']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" name="username" id="username"
                            value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                </div>
                <div class="form-group-container">
                    <div class="form-group">
                        <label for="department">Department:</label>
                        <select name="department" required>
                            <option value=''>Department</option>
                            <?php
                            if ($departmentResult->num_rows > 0) {
                                while ($row = $departmentResult->fetch_assoc()) {
                                    $selected = ($row['departments'] == $user['department']) ? 'selected' : '';
                                    echo "<option value='" . $row['departments'] . "' $selected>" . $row['departments'] . "</option>";
                                }
                            } else {
                                echo "<option value=''>No departments available</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="contact">Contact:</label>
                        <input type="text" name="contact" id="contact"
                            value="<?php echo htmlspecialchars($user['contact']); ?>" required>
                    </div>
                </div>
                <div class="form-group-container">
                    <div class="form-group">
                        <label for="user_type">User Type:</label>
                        <input type="text" name="user_type" id="user_type"
                            value="<?php echo htmlspecialchars($user['user_type']); ?>" readonly>
                        <input type="hidden" name="user_type"
                            value="<?php echo htmlspecialchars($user['user_type']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status" required>
                            <option value="verified" style="color:#1ca21c;" <?php echo $user['status'] === 'verified' ? 'selected' : ''; ?>>Verified</option>
                            <option value="not verified" style="color:#bd000a;" <?php echo $user['status'] === 'not verified' ? 'selected' : ''; ?>>Not Verified</option>
                        </select>
                    </div>
                </div>
                <button type="submit">Save Changes</button>
                <button type="button" onclick="window.location.href='manageuser.php'">Cancel</button>
            </form>
        </div>
    </div>
    <!-- Success Modal -->
    <div id="successModal" class="success-modal" style="display: none;">
        <div class="success-modal-content">
            <i class="fa-regular fa-circle-check"></i>
            <h2>Success!</h2>
            <p id="successMessage"></p>
        </div>
    </div>

    <div id="errorModal" class="success-modal">
        <div class="success-modal-content" style="color: #bd000a;">
            <i class="fa-regular fa-circle-xmark" style="color: #bd000a;"></i>
            <h2>Error!</h2>
            <p id="errorMessage"></p>
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

    <?php if ($showSuccessModal): ?>
        <script>
            <?php if ($showSuccessModal): ?>
                document.addEventListener('DOMContentLoaded', function () {
                    var modal = document.getElementById('successModal');
                    var msg = document.getElementById('successMessage');
                    modal.style.display = 'block';
                    msg.textContent = <?php echo json_encode($successMessage); ?>;
                    setTimeout(function () {
                        modal.style.display = 'none';
                        <?php if ($redirectAfterModal): ?>
                            window.location.href = 'manageuser.php';
                        <?php endif; ?>
                    }, 2000); // 2 seconds before redirect
                });
            <?php endif; ?>
        </script>
    <?php endif; ?>

    <?php if ($showErrorModal): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modal = document.getElementById('errorModal');
                var msg = document.getElementById('errorMessage');
                modal.style.display = 'block';
                msg.textContent = <?php echo json_encode($errorMessage); ?>;
                setTimeout(function () {
                    modal.style.display = 'none';
                }, 2500);
            });
        </script>
    <?php endif; ?>

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

        document.addEventListener('DOMContentLoaded', () => {
            // Display modal if it exists
            const successModal = document.getElementById('successModal');
            if (successModal.style.display === "block") {
                const closeModal = document.getElementById('closeModal');
                closeModal.hidden = false; // Make the close button visible

                // Close the modal when the close button is clicked
                closeModal.addEventListener('click', () => {
                    successModal.style.display = "none";
                });

                // Close the modal if the user clicks outside the modal content
                window.addEventListener('click', event => {
                    if (event.target === successModal) {
                        successModal.style.display = "none";
                    }
                });
            }
        });
        document.getElementById('avatarInput').addEventListener('change', function (event) {
            const [file] = event.target.files;
            if (file) {
                document.getElementById('profileAvatar').src = URL.createObjectURL(file);
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            // Show Confirm Logout Modal
            document.getElementById('logoutLink').addEventListener('click', function (e) {
                e.preventDefault();
                document.getElementById('logoutModal').style.display = 'flex';
                document.getElementById('profileDropdown').classList.remove('show'); // <-- Add this line
            });

            // Handle Confirm Logout
            document.getElementById('confirmLogout').addEventListener('click', function () {
                window.location.href = 'logout.php';
            });

            // Handle Cancel Logout
            document.getElementById('cancelLogout').addEventListener('click', function () {
                document.getElementById('logoutModal').style.display = 'none';
            });
        });

        window.onclick = function (event) {
            // ...existing code...
            const logoutModal = document.getElementById('logoutModal');
            if (event.target === logoutModal) {
                logoutModal.style.display = 'none';
            }
        };
    </script>
    <script>
        // Status dropdown color logic
        function updateStatusColor() {
            const statusSelect = document.getElementById('status');
            if (!statusSelect) return;
            // Remove both classes first
            statusSelect.classList.remove('verified', 'not-verified');
            // Set color for the select element
            if (statusSelect.value === 'verified') {
                statusSelect.classList.add('verified');
                statusSelect.style.color = '#1ca21c';
            } else {
                statusSelect.classList.add('not-verified');
                statusSelect.style.color = '#bd000a';
            }
            // Set color for each option (for browsers that support it)
            Array.from(statusSelect.options).forEach(option => {
                if (option.value === 'verified') {
                    option.style.color = '#1ca21c';
                } else if (option.value === 'not verified') {
                    option.style.color = '#bd000a';
                } else {
                    option.style.color = '';
                }
            });
        }
        document.addEventListener('DOMContentLoaded', function () {
            updateStatusColor();
            const statusSelect = document.getElementById('status');
            statusSelect.addEventListener('change', updateStatusColor);
        });
    </script>
</body>

</html>
<script src="../js/archivescript.js"></script>