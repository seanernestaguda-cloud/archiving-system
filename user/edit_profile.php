<?php
// session_start();
// if (!isset($_SESSION['username'])) {
//     header("Location: adminlogin.php");
//     exit();
// }
include 'connection.php';
include 'auth_check.php';

$sql_settings = "SELECT system_name FROM settings LIMIT 1";
$result_settings = $conn->query($sql_settings);
$system_name = 'BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM';
if ($result_settings && $row_settings = $result_settings->fetch_assoc()) {
    $system_name = $row_settings['system_name'];
}

$username = $_SESSION['username'];
$query = "SELECT * FROM users WHERE username = '$username' LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result || !$user = mysqli_fetch_assoc($result)) {
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




$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $middle_name = mysqli_real_escape_string($conn, $_POST['middle_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $original_username = $_SESSION['username'];
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $birthday = mysqli_real_escape_string($conn, $_POST['birthday']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    // Check if username is already taken (excluding current user)
    if (strtolower($username) !== strtolower($original_username)) {
        $check_username = "SELECT username FROM users WHERE username = '$username' LIMIT 1";
        $check_result = mysqli_query($conn, $check_username);
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $error = "Username is already taken.";
        }
    }

    // Handle avatar upload
    $avatar_path = $user['avatar'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../avatars/";
        $file_name = basename($_FILES["avatar"]["name"]);
        $target_file = $target_dir . uniqid() . "_" . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $check = getimagesize($_FILES["avatar"]["tmp_name"]);
        if ($check !== false && in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
         if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
    $avatar_path = basename($target_file); // Save only the filename
        } else {
            $error = "Sorry, there was an error uploading your file.";
        }
        } else {
            $error = "File is not an image or invalid format.";
        }
    }

    if (strtolower($username) === 'admin') {
        $error = "You cannot use 'admin' as your username.";
    }

    if (!$error) {
        $update = "UPDATE users SET 
            first_name='$first_name',
            middle_name='$middle_name',
            last_name='$last_name',
            department='$department',
            contact='$contact',
            username='$username',
            birthday='$birthday',
            gender='$gender',
            address='$address',
            avatar='$avatar_path'
            WHERE username='$original_username' LIMIT 1";
        if (mysqli_query($conn, $update)) {
            $success = " ";
            // Update session username if changed
            $_SESSION['username'] = $username;
            // Refresh user data
            $query = "SELECT * FROM users WHERE username = '$username' LIMIT 1";
            $result = mysqli_query($conn, $query);
            $user = mysqli_fetch_assoc($result);
        } else {
            $error = "Failed to update profile.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="reportstyle.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="icon" type="image/png" href="../REPORT.png">
  <style>
        .profile-container { max-width: 500px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #003d7322; padding: 30px 40px; }
        .profile-avatar { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #003D73; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; color: #003D73; }
        input, select, textarea { width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc; }
        .profile-actions { margin-top: 20px; }
        
        .profile-actions {
    margin-top: 30px;
    display: flex;
    gap: 0px;
    flex-wrap: wrap; 
    justify-content: flex-end;
}
        .profile-actions a {
            flex: 0 1 auto;
            background: #bd000a;
            color: #fff;
            padding: 10px 22px;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 10px;
        }
        .profile-actions a:hover {
            background: #810000;
        }

        .profile-actions button{
                 background: #003D73;
                  flex: 0 1 auto;
            color: #fff;
            padding: 10px 22px;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 5px;
            cursor: pointer;
        }

        .profile-actions button:hover {
            background: #002D57;
        }

        .success { color: green; }
        .error { color: red; }
        .avatar-wrapper {
    position: relative;
    display: inline-block;
}


.change-avatar-btn {
    position: absolute;
    bottom: 10px;
    left: 50%;
    transform: translateX(-50%);
    background: #003D73;
    color: #fff;
    padding: 6px 18px;
    border-radius: 20px;
    font-size: 14px;
    cursor: pointer;
    opacity: 0.9;
    transition: background 0.2s;
    text-align: center;
}
.change-avatar-btn:hover {
    background: #002D57;
}

.profile-card {
    background: #fff;
    max-width: 800px;
    margin: 40px auto 0 auto;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08);
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
    .edit-form, .profile-info {
        width: 100%;
    }
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
    </style>
</head>
<body>
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
    <br>
<div class="profile-card">
    <div class="profile-info" style="text-align:center;">
        <div class="avatar-wrapper" style="position: relative; display: inline-block;">
<img class="profile-avatar" src="<?php
    $avatarPath = '../avatars/' . ($user['avatar'] ? $user['avatar'] : 'default_avatar.png');
    echo file_exists($avatarPath) ? $avatarPath : '../avatars/default_avatar.png';
?>" alt="Avatar" style="width:140px;height:140px;border-radius:50%;border:4px solid #e3e6ea;object-fit:cover;margin-bottom:18px;">           
 <label for="avatar" class="change-avatar-btn" style="position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); background: #003D73; color: #fff; padding: 6px 18px; border-radius: 20px; font-size: 14px; cursor: pointer; opacity: 0.9; transition: background 0.2s; text-align: center;">Change</label>
        </div>
        <h3 style="margin:0 0 6px 0;font-size:1.4rem;color:#222;font-weight:600;">
            <?php echo htmlspecialchars($user['first_name'] . ' '.$user['middle_name'].' '.  $user['last_name']); ?>
        </h3>
        <p style="color:#888;margin:0 0 18px 0;font-size:1rem;">
            @<?php echo htmlspecialchars($user['username']); ?>
        </p>
        <div class="profile-meta" style="font-size:0.98rem;color:#555;margin-bottom:8px;">
            <div><strong>Department:</strong> <?php echo htmlspecialchars($user['department']); ?></div><br>
        </div>
    </div>
    <form class="edit-form" method="post" enctype="multipart/form-data" style="flex:2 1 350px;">
        <!-- Move the file input here -->
        <input type="file" name="avatar" id="avatar" accept="image/*" style="display:none;">
        <h2 style="font-size:1.2rem;color:#4267b2;margin-bottom:18px;font-weight:600;">Edit Profile</h2>
        <div class="form-group-container" style="display:flex;gap:16px;margin-bottom:12px;">
            <div class="form-group" style="flex:1 1 0;display:flex;flex-direction:column;">
                <label>First Name:</label>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
            </div>
            
            <div class="form-group" style="flex:1 1 0;display:flex;flex-direction:column;">
                <label>Middle Name:</label>
                <input type="text" name="middle_name" value="<?php echo htmlspecialchars($user['middle_name']); ?>">
            </div>

            <div class="form-group" style="flex:1 1 0;display:flex;flex-direction:column;">
                <label>Last Name:</label>
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
            </div>
        </div>
        <div class="form-group-container" style="display:flex;gap:16px;margin-bottom:12px;">
            <div class="form-group" style="flex:1 1 0;display:flex;flex-direction:column;">
                <label>Department:</label>
                <input type="text" name="department" value="<?php echo htmlspecialchars($user['department']); ?>">
            </div>
            <div class="form-group" style="flex:1 1 0;display:flex;flex-direction:column;">
                <label>Contact:</label>
                <input type="text" name="contact" value="<?php echo htmlspecialchars($user['contact']); ?>">
            </div>
        </div>
        <div class="form-group-container" style="display:flex;gap:16px;margin-bottom:12px;">
            <div class="form-group" style="flex:1 1 0;display:flex;flex-direction:column;">
                <label>Username:</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
            </div>
            <div class="form-group" style="flex:1 1 0;display:flex;flex-direction:column;">
                <label>Birthday:</label>
                <input type="date" name="birthday" value="<?php echo htmlspecialchars($user['birthday']); ?>">
            </div>
        </div>
        <div class="form-group-container" style="display:flex;gap:16px;margin-bottom:12px;">
            <div class="form-group" style="flex:1 1 0;display:flex;flex-direction:column;">
                <label>Gender:</label>
                <select name="gender">
                    <option value="male" <?php if($user['gender']=='male') echo 'selected'; ?>>Male</option>
                    <option value="female" <?php if($user['gender']=='female') echo 'selected'; ?>>Female</option>
                    <option value="other" <?php if($user['gender']=='other') echo 'selected'; ?>>Other</option>
                </select>
            </div>
            <div class="form-group" style="flex:1 1 0;display:flex;flex-direction:column;">
                <label>Address:</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">
            </div>
        </div>
        <div class="profile-actions" style="margin-top:30px;">
            <button type="submit">Save Changes</button>
            <a href="myprofile.php">Cancel</a>
        </div>
        
       
    </form>
</div>

<div id="successModal" class="success-modal" style="display: none;">
    <div class="success-modal-content">
    <i class="fa-regular fa-circle-check"></i><h2>Success!</h2>
        <p id="successMessage"> </p>
    </div>
</div>

<div id="errorModal" class="success-modal" style="display: none;">
    <div class="success-modal-content">
        <i class="fa-solid fa-triangle-exclamation" style = "color:red;font-size:2rem;"></i>
        <h2 style="color:red;">Error!</h2>
        <p id="errorMessage"></p>
    </div>
</div>

<script>
  document.getElementById('avatar').addEventListener('change', function(event) {
    const [file] = event.target.files;
    if (file) {
        const img = document.querySelector('.profile-avatar');
        img.src = URL.createObjectURL(file);
    }
});
  document.getElementById('avatar').addEventListener('change', function(event) {
    const [file] = event.target.files;
    if (file) {
        const img = document.querySelector('.profile-avatar');
        img.src = URL.createObjectURL(file);
    }
  });

  // Show success modal if update was successful
document.getElementById('avatar').addEventListener('change', function(event) {
    const [file] = event.target.files;
    if (file) {
        const img = document.querySelector('.profile-avatar');
        img.src = URL.createObjectURL(file);
    }
  });

  // Show success modal if update was successful, then redirect
<?php if ($success): ?>
  document.getElementById('successMessage').textContent = " Profile Updated Successfully "; // No message
  document.getElementById('successModal').style.display = 'block';
  setTimeout(function() {
    window.location.href = "myprofile.php";
  }, 2000);
<?php endif; ?>


<?php if ($error): ?>
  document.getElementById('errorMessage').textContent = "<?php echo $error; ?>";
  document.getElementById('errorModal').style.display = 'block';
  setTimeout(function() {
    document.getElementById('errorModal').style.display = 'none';
  }, 2000);
<?php endif; ?>

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