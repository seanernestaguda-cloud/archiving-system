<?php
// session_start(); // Start the session

// Check if the user is logged in
// if (!isset($_SESSION['username'])) {
//     header("Location: adminlogin.php"); // Redirect to login if not logged in
//     exit();
// }

// Include database connection
include 'connection.php';
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

$departmentQuery = "SELECT * FROM departments";
$departmentResult = $conn->query($departmentQuery);
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['first_name'], $_POST['last_name'], $_POST['username'], $_POST['password'], $_POST['department'])) {
        $firstName = $_POST['first_name'];
        $middleName = $_POST['middle_name'];
        $lastName = $_POST['last_name'];
        $birthday = $_POST['birthday'];
        $address = $_POST['address'];
        $username = $_POST['username'];
        $gender = $_POST['gender'];
        $password = $_POST['password'];
        $department = $_POST['department'];
        $contact = $_POST['contact'];
        $user_type = 'admin';

        // Prevent using "admin" as username
        if (strtolower($username) === 'admin') {
            echo "Error: The username 'admin' is not allowed.";
            exit();
        }

        // Handle avatar upload
        $avatarPath = '';
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
            $targetDir = "../avatars/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $fileName = uniqid() . '_' . basename($_FILES["avatar"]["name"]);
            $targetFile = $targetDir . $fileName;
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($imageFileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $targetFile)) {
                    $avatarPath = $targetFile;
                } else {
                    echo "Error uploading avatar.";
                }
            } else {
                echo "Invalid avatar file type.";
            }
        }

$checkQuery = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "Error: Username already exists. Please choose a different username.";
    exit();
} else {
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert into database using prepared statement
    $query = "INSERT INTO users (first_name, middle_name, last_name, birthday, address, username, password, department, contact, user_type, status, avatar) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'not verified', ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "sssssssssss",
        $firstName,
        $middleName,
        $lastName,
        $birthday,
        $address,
        $username,
        $hashedPassword,
        $department,
        $contact,
        $user_type,
        $avatarPath
    );
    if ($stmt->execute()) {
        echo "success";
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
    } else {
        echo "Error: Missing required fields.";
    }
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
    <title>Create Admin</title>
</head>
<style>
.header{
    position: fixed;
    z-index: 1000;
}

form.user-form {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px #003d7322;
    padding: 30px 30px 20px 30px;
    max-width: 700px;
    margin: 30px auto;
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.form-section {
    margin-bottom: 10px;
}

.form-section-title {
    font-size: 1.1rem;
    font-weight: bold;
    color: #003D73;
    margin-bottom: 10px;
    border-bottom: 1px solid #eee;
    padding-bottom: 4px;
}

.form-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.form-group {
    flex: 1 1 45%;
    display: flex;
    flex-direction: column;
    margin-bottom: 10px;
    min-width: 200px;
}

label {
    font-weight: 500;
    margin-bottom: 4px;
    color: #222;
}

input[type="text"], input[type="password"], input[type="file"], input[type="date"], input[type="number"], select {
    padding: 10px;
    border: 1px solid #bbb;
    border-radius: 4px;
    font-size: 15px;
    background: #f7f7f7;
}

input[type="text"]:required, input[type="password"]:required, input[type="date"]:required, input[type="number"]:required, select:required {
    border-left: 3px solid #003D73;
}

input[type="file"] {
    background: #fff;
}

.avatar-upload {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 0;
}

.avatar-label {
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.avatar-circle {
    width: 110px;
    height: 110px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #003D73;
    background: #fff;
    margin-bottom: 8px;
    transition: box-shadow 0.2s;
}

.avatar-label:hover .avatar-circle {
    box-shadow: 0 0 0 4px #003D7333;
}

.avatar-edit-text {
    font-size: 13px;
    color: #003D73;
    text-align: center;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 5px;
    margin-top: 10px;
}

button[type="submit"], .btn-cancel {
    padding: 12px 28px;
    font-size: 16px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
}

button[type="submit"] {
    background: #003D73;
    color: #fff;
}

button[type="submit"]:hover {
    background: #002D57;
}

.btn-cancel {
    background: #bd000a;
    color: #fff;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-cancel:hover {
    background: #81000a;
}

.password-toggle {
    position: relative;
    display: flex;
    align-items: center;
}

.password-toggle input[type="password"], .password-toggle input[type="text"] {
    width: 100%;
    padding-right: 35px;
}

.password-toggle .toggle-password {
    position: absolute;
    right: 10px;
    cursor: pointer;
    color: #003D73;
    font-size: 18px;
    background: none;
    border: none;
}
</style>
<body>

<div class="dashboard">
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
    <h2><?php echo htmlspecialchars($settings['system_name'] ?? 'BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM'); ?></h2>
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
   <form action="create_manager.php" method="POST" enctype="multipart/form-data" class="user-form">

    <div class="avatar-upload">
        <label for="avatar" class="avatar-label">
            <img id="avatarPreview" src="../avatars/default_avatar.png" alt="Avatar Preview" class="avatar-circle">
            <span class="avatar-edit-text">Click to upload avatar</span>
        </label>
        <input type="file" id="avatar" name="avatar" accept="image/*" style="display:none;">
    </div>

    <div class="form-section">
        <div class="form-section-title">Personal Information</div>
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name <span style="color:red">*</span></label>
                <input type="text" name="first_name" placeholder="Enter first name" required>
            </div>
            <div class="form-group">
                <label for="middle_name">Middle Name</label>
                <input type="text" name="middle_name" placeholder="Enter middle name">
            </div>
            <div class="form-group">
                <label for="last_name">Last Name <span style="color:red">*</span></label>
                <input type="text" name="last_name" placeholder="Enter last name" required>
            </div>
            <div class="form-group">
                <label for="birthday">Birthday <span style="color:red">*</span></label>
                <input type="date" name="birthday" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="gender">Gender <span style="color:red">*</span></label>
                <select name="gender" id="gender" required>
                    <option value="">Select gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="address">Address <span style="color:red">*</span></label>
                <input type="text" name="address" placeholder="Enter address" required>
            </div>
            <div class="form-group">
                <label for="contact">Contact No. <span style="color:red">*</span></label>
                <input type="text" name="contact" placeholder="09XXXXXXXXX" required>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section-title">Account Information</div>
        <div class="form-row">
            <div class="form-group">
                <label for="username">Username <span style="color:red">*</span></label>
                <input type="text" name="username" placeholder="Enter username" required>
            </div>
               <div class="form-group password-toggle">
                <label for="password">Password <span style="color:red">*</span></label>
                <div style="position:relative;">
                    <input type="password" name="password" id="password" placeholder="Enter password" required style="padding-right:38px;">
                    <button type="button" class="toggle-password" onclick="togglePassword()" tabindex="-1" style="position:absolute;top:50%;right:10px;transform:translateY(-50%);padding:0;background:none;border:none;">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label for="department">Department <span style="color:red">*</span></label>
                <select name="department" required>
                    <option value="">Select department</option>
                    <?php
                    if ($departmentResult->num_rows > 0) {
                        while ($row = $departmentResult->fetch_assoc()) {
                            echo "<option value='" . $row['departments'] . "'>" . $row['departments'] . "</option>";
                        }
                    } else {
                        echo "<option value=''>No departments available</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Create User</button>
        <a href="manageuser.php" class="btn btn-cancel">Cancel</a>
    </div>
</form>
</div>
</div>


<div id="successModal" class="success-modal" style="display: none;">
    <div class="success-modal-content">
    <i class="fa-regular fa-circle-check"></i><h2>Success!</h2>
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

document.getElementById('avatar').addEventListener('change', function (event) {
    const [file] = event.target.files;
    if (file) {
        document.getElementById('avatarPreview').src = URL.createObjectURL(file);
    }
});

document.addEventListener('DOMContentLoaded', () => {
    // ...existing dropdown and avatar preview code...

    // Success Modal and AJAX Form Submission
    const form = document.querySelector('.user-form');
    const successModal = document.getElementById('successModal');
    const successMessage = document.getElementById('successMessage');
    const errorModal = document.getElementById('errorModal');
    const errorMessage = document.getElementById('errorMessage');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);

        fetch('create_manager.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (data.includes('Error')) {
                errorMessage.textContent = data;
                errorModal.style.display = 'block';
                setTimeout(() => {
                    errorModal.style.display = 'none';
                }, 2500); // Hide error modal after 2.5 seconds
            } else {
                successMessage.textContent = "New user added!";
                successModal.style.display = 'block';
                setTimeout(() => {
                    window.location.href = 'manageuser.php';
                }, 1500); // Redirect after 1.5 seconds
            }
        })
        .catch(error => {
            errorMessage.textContent = 'An error occurred: ' + error;
            errorModal.style.display = 'block';
            setTimeout(() => {
                errorModal.style.display = 'none';
            }, 100000);
        });
    });
});

function togglePassword() {
    const pwd = document.getElementById('password');
    const icon = document.querySelector('.toggle-password i');
    if (pwd.type === "password") {
        pwd.type = "text";
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        pwd.type = "password";
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
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