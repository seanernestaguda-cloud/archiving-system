<?php
// session_start();

// // Check if the user is logged in
// if (!isset($_SESSION['username'])) {
//     header("Location: userlogini.php");
// }

include('connection.php');
include('auth_check.php');

$sql_settings = "SELECT system_name FROM settings LIMIT 1";
$result_settings = $conn->query($sql_settings);
$system_name = 'BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM';
if ($result_settings && $row_settings = $result_settings->fetch_assoc()) {
    $system_name = $row_settings['system_name'];
}


$username = $_SESSION['username'];
$sql_user = "SELECT avatar, user_type FROM users WHERE username = ? LIMIT 1";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param('s', $username);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$avatar = '../avatars/default_avatar.png';
$user_type = '';
if ($result_user && $row_user = $result_user->fetch_assoc()) {
    if (!empty($row_user['avatar']) && file_exists('../avatars/' . $row_user['avatar'])) {
        $avatar = '../avatars/' . $row_user['avatar'];
    }
    $user_type = isset($row_user['user_type']) ? $row_user['user_type'] : '';
}
$stmt_user->close();



$report_id = isset($_GET['report_id']) ? intval($_GET['report_id']) : 0;
if ($report_id <= 0) {
    die("Invalid report ID.");
}

// Fetch the report data
$query = "SELECT * FROM fire_incident_reports WHERE report_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $report_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$report = mysqli_fetch_assoc($result);

if (!$report) {
    die("Report not found.");
}


// Restrict editing: only uploader or admin can edit, others can view
if (!isset($report['uploader'])) {
    die("Access denied. You are not allowed to view this report.");
}
$can_edit = (strtolower($user_type) === 'admin');


$query_barangays = "SELECT barangay_id, barangay_name FROM barangays ORDER BY barangay_name";
$result_barangays = mysqli_query($conn, $query_barangays);

if (!$result_barangays) {
    die("Error fetching barangays: " . mysqli_error($conn));
}

$query_fire_types = "SELECT fire_types_id, fire_types FROM fire_types ORDER BY fire_types";
$result_fire_types = mysqli_query($conn, $query_fire_types);
if (!$result_fire_types) {
    die("Error fetching fire types: " . mysqli_error($conn));
}


// Handle update if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $can_edit) {
    // Get updated form data
    $report_title = $_POST['report_title'];
    $caller_name = $_POST['caller_name'];
    $responding_team = $_POST['responding_team'];
    $fire_location = $_POST['fire_location'];
    $street = $_POST['street'];
    $purok = $_POST['purok'];
    $municipality = $_POST['municipality'];
    $incident_date = $_POST['incident_date'];
    $arrival_time = $_POST['arrival_time'];
    $fireout_time = $_POST['fireout_time'];
    $establishment = $_POST['establishment'];
    $occupancy_type = $_POST['occupancy_type'];
    $alarm_status = $_POST['alarm_status'];
    $victims = implode(',', array_map('trim', preg_split('/\r\n|\r|\n/', $_POST['victims'])));
    $firefighters = implode(',', array_map('trim', preg_split('/\r\n|\r|\n/', $_POST['firefighters'])));
    $property_damage = $_POST['property_damage'];
    $fire_types = $_POST['fire_types'];
    // Retrieve existing documentation photos
        $existing_photos = [];
            if (isset($_POST['existing_photos_input'])) {
                $existing_photos = array_filter(array_map('trim', explode(',', $_POST['existing_photos_input'])));
            }

            // Handling file uploads (documentation photos)
            if (isset($_FILES['documentation_photos']) && !empty($_FILES['documentation_photos']['name'][0])) {
                foreach ($_FILES['documentation_photos']['tmp_name'] as $index => $tmp_name) {
                    $file_name = $_FILES['documentation_photos']['name'][$index];
                    $file_tmp = $_FILES['documentation_photos']['tmp_name'][$index];
                    $file_error = $_FILES['documentation_photos']['error'][$index];

                    if ($file_error === 0) {
                        $upload_dir = '../uploads/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        $unique_file_name = time() . "_" . basename($file_name);
                        $upload_path = $upload_dir . $unique_file_name;
                        if (move_uploaded_file($file_tmp, $upload_path)) {
                            $existing_photos[] = $upload_path;
                        }
                    }
                }
            }

    // Handle narrative report upload
    $narrative_report = $report['narrative_report']; // Keep the original file if not updated
    if (isset($_FILES['narrative_report']) && $_FILES['narrative_report']['error'] === 0) {
        $narrative_report_name = $_FILES['narrative_report']['name'];
        $narrative_report_tmp = $_FILES['narrative_report']['tmp_name'];

        $narrative_report_path = '../uploads/' . time() . "_" . basename($narrative_report_name);
        if (move_uploaded_file($narrative_report_tmp, $narrative_report_path)) {
            $narrative_report = $narrative_report_path;
        }
    }

    // Handle progress report upload
$progress_report = $report['progress_report']; // Keep the original file if not updated
if (isset($_FILES['progress_report']) && $_FILES['progress_report']['error'] === 0) {
    $progress_report_name = $_FILES['progress_report']['name'];
    $progress_report_tmp = $_FILES['progress_report']['tmp_name'];

    $progress_report_path = '../uploads/' . time() . "_" . basename($progress_report_name);
    if (move_uploaded_file($progress_report_tmp, $progress_report_path)) {
        $progress_report = $progress_report_path;
    }
}

// Handle final investigation report upload
$final_investigation_report = $report['final_investigation_report']; // Keep the original file if not updated
if (isset($_FILES['final_investigation_report']) && $_FILES['final_investigation_report']['error'] === 0) {
    $final_report_name = $_FILES['final_investigation_report']['name'];
    $final_report_tmp = $_FILES['final_investigation_report']['tmp_name'];

    $final_report_path = '../uploads/' . time() . "_" . basename($final_report_name);
    if (move_uploaded_file($final_report_tmp, $final_report_path)) {
        $final_investigation_report = $final_report_path;
    }
}


    // Update report data in the database
    $query = "UPDATE fire_incident_reports 
    SET report_title = ?, caller_name = ?, responding_team = ?, fire_location = ?, street = ?, purok = ?, municipality = ?, incident_date = ?, arrival_time = ?, fireout_time = ?, establishment = ?, occupancy_type = ?, victims = ?, firefighters = ?, alarm_status = ?, property_damage = ?, fire_types = ?, documentation_photos = ?, narrative_report = ?, progress_report = ?, final_investigation_report = ? 
    WHERE report_id = ?";   
    $stmt = mysqli_prepare($conn, $query);
    $documentation_photos = implode(',', $existing_photos);    mysqli_stmt_bind_param($stmt, "sssssssssssssssssssssi", $report_title, $caller_name,  $responding_team, $fire_location, $street, $purok, $municipality, $incident_date, $arrival_time, $fireout_time, $establishment, $occupancy_type, $victims, $firefighters, $alarm_status, $property_damage, $fire_types, $documentation_photos, $narrative_report, $progress_report, $final_investigation_report, $report_id);
if (mysqli_stmt_execute($stmt)) {
    $success_message = "Report updated successfully!";
    // Log activity
    $log_query = "INSERT INTO activity_logs (username, action, report_id, details) VALUES (?, 'update', ?, ?)";
    $log_stmt = $conn->prepare($log_query);
    $log_details = "Updated Fire Incident Report: " . $report_title;
    $log_stmt->bind_param('sis', $username, $report_id, $log_details);
    $log_stmt->execute();
    $log_stmt->close();
    // Set redirect target for JS
    if (strtolower($user_type) === 'admin' && $report['uploader'] === $_SESSION['username']) {
        $redirect_target = 'my_fire_incident_reports.php';
    } else {
        $redirect_target = 'fire_incident_report.php';
    }
} else {
    $error_message = "There was an error updating the report.";
}
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($report['report_title']); ?></title>
    <link rel="stylesheet" href="reportstyle.css">
    <link rel="stylesheet" href="permitstyle.css">
    <link rel="stylesheet" href="view_report.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="icon" type="image/png" href="../REPORT.png">
  <style>

.header{
    position: fixed;
    z-index: 1000;
}
        legend{
                text-align: center;
                font-size: 20px;
                font-weight: bold;
                margin-bottom: 10px;
        }
.file-icon-label {
    cursor: pointer;
    font-size: 20px;
    color: #444444;
    margin-right: 10px;
    vertical-align: middle;
}

.file-icon-label:hover {
    color: #353535ff;
}

.file-icon-label i{
    background-color: #fff;
    padding: 20px;
    border-radius: 30px;
    border: 1px solid #003d73;
}

.file-icon-label i:hover{
    background-color: #003d73;
    color: #fff;
}

.photo-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}
.photo-container {
    position: relative;
    width: 180px;
    height: 180px;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    background: #fafafa;
}
.scene-photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.delete-photo-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #e74c3c;
    color: #fff;
    border: none;
    padding: 6px 10px;
    border-radius: 50%;
    cursor: pointer;
    font-weight: bold;
}
.documents-section {
    margin-top: 20px;
}
.report-section {
    margin-top: 20px;
    padding: 10px;
    border: 1px solid #eee;
    border-radius: 6px;
    background: #f9f9f9;
}

.photo-view-modal {
    position: fixed;
    z-index: 9999;
    left: 0; top: 0;
    width: 100vw; height: 100vh;
    background: rgba(0,0,0,0.8);
    display: flex; align-items: center; justify-content: center;
}
.photo-modal-content {
    position: relative;
    background: #fff;
    padding: 10px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}
.close-photo-modal {
    position: absolute;
    top: 10px; right: 20px;
    color: #333;
    font-weight: bold;
}

/* ...existing code... */
.btn-view, .btn-download, .btn-delete {
    display: inline-block;
    padding: 8px 14px;
    margin: 0 4px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
    vertical-align: middle;
}

.btn-view {
    background: #3498db;
    color: #fff;
}
.btn-view:hover {
    background: #217dbb;
}

.btn-download {
    background: #27ae60;
    color: #fff;
}
.btn-download:hover {
    background: #1e874b;
}

.btn-delete {
    background: #e74c3c;
    color: #fff;
}
.btn-delete:hover {
    background: #c0392b;
}

.card{
    max-width: 900px;     /* change to desired max width (e.g. 700px for smaller) */
    width: 90%;           /* responsive width */
    margin: 30px auto 40px; /* center and control vertical spacing */
    padding: 18px;        /* inner spacing */
    box-sizing: border-box;
    border-radius: 8px;   /* match existing look */
    background: #fff;
    box-shadow: 0 4px 8px rgba(0,0,0,0.08);
}
    </style>
</head>
<body>
<div class = "dashboard">
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
        <div class="form-header">
        <div class = "form-actions">
        <form method="POST" action="generate_pdf.php?report_id=<?php echo $report_id; ?>">
    <button type="submit" class="download-button"><i class="fa-solid fa-download"></i> Download Report</button>
</form>
    </div>
    <div class = "title-part">
            <h2> Fire Incident Report # <?php echo htmlspecialchars($report['report_id']); ?> </h2>
            <form method="POST" action="generate_pdf.php?report_id=<?php echo $report_id; ?>">
</form>
        </div>
        </div>
<fieldset>
    <legend> Incident Details </legend>
        <!-- Fire Incident Report Form -->
        <form method="POST" action="view_report.php?report_id=<?php echo $report_id; ?>" enctype="multipart/form-data">
            <div class="form-group-container">
            <div class="form-group" style="width: 45%; display: inline-block;">
                <label for="report_title">Report Title:</label>
                <input type="text" id="report_title" name="report_title" value="<?php echo htmlspecialchars($report['report_title']); ?>" class="form-control" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
            </div>

            <div class="form-group" style="width: 45%; display: inline-block;">
                <label for="caller_name">Name of Caller</label>
                <input type="text" id="caller_name" name="caller_name" value="<?php echo htmlspecialchars($report['caller_name']); ?>" class="form-control" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
            </div>

            <div class="form-group" style="width: 45%; display: inline-block;">
                <label for="responding_team">Responding Team</label>
                <input type="text" id="responding_team" name="responding_team" value="<?php echo htmlspecialchars($report['responding_team']); ?>" class="form-control" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
            </div>
            <div class="form-group" style="width: 45%; display: inline-block;">
                <label for="establishment">Establishment Name:</label>
                <input type="text" id="establishment" name="establishment" value="<?php echo htmlspecialchars($report['establishment']); ?>" class="form-control" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
            </div>
<h4> Fire Location </h4>

<div class = "form-group-container">
            <div class="form-group" style="width: 45%; display: inline-block;">
    <label for="street">Street:</label>
    <input type="text" id="street" name="street" value="<?php echo htmlspecialchars($report['street'] ?? ''); ?>" class="form-control" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
</div>


<div class="form-group" style="width: 45%; display: inline-block;">
    <label for="purok">Purok:</label>
    <input type="text" id="purok" name="purok" value="<?php echo htmlspecialchars($report['purok'] ?? ''); ?>" class="form-control" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
</div>
<div class="form-group" style="width: 45%; display: inline-block;">
    <label for="municipality">Municipality:</label>
    <input type="text" id="municipality" name="municipality" value="<?php echo htmlspecialchars($report['municipality'] ?? ''); ?>" class="form-control" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
</div>

<div class="form-group" style="width: 45%; display: inline-block;">
                <label for="fire_location">Barangay:</label>
                <select id="fire_location" name="fire_location" class="form-control" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                    <option value="" disabled selected>Select Barangay</option>
                    <?php while ($row = mysqli_fetch_assoc($result_barangays)) { ?>
                        <option value="<?php echo htmlspecialchars($row['barangay_name']); ?>" 
                                <?php echo ($report['fire_location'] === $row['barangay_name']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['barangay_name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            </div>

<h4> Date and Time </h4>
<div class="form-group-container">
<div class="form-group" style="width: 30%; display: inline-block;">
                <label for="incident_date">Time and Date Reported:</label>
                <input type="datetime-local" id="incident_date" name="incident_date" value="<?php echo htmlspecialchars($report['incident_date']); ?>" class="form-control" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
            </div>
<div class="form-group" style="width: 30%; display: inline-block;">
                <label for="arrival_time">Arrival Time:</label>
                <input type="time" id="arrival_time" name="arrival_time" value="<?php echo htmlspecialchars($report['arrival_time']); ?>" class="form-control" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
            </div>
            <div class="form-group" style="width: 30%; display: inline-block;">
                <label for="fireout_time">Fireout Time:</label>
                <input type="time" id="fireout_time" name="fireout_time" value="<?php echo htmlspecialchars($report['fireout_time']); ?>" class="form-control" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
            </div>
            </div>

<h4> Injured/Casualties </h4>
<br>
<div class="form-group-container">
<div class="form-group" style="width: 45%; display: inline-block;">
                <label for="victims">Civilians:</label>
                <textarea id="victims" name="victims" rows="10" class="form-control" <?php echo !$can_edit ? 'disabled' : ''; ?>><?php echo htmlspecialchars($report['victims']); ?></textarea>
            </div>

            <div class="form-group" style="width: 45%; display: inline-block;">
                <label for="firefighters">Firefighters:</label>
                <textarea id="victims" name="firefighters" rows="10" class="form-control" <?php echo !$can_edit ? 'disabled' : ''; ?>><?php echo htmlspecialchars($report['firefighters']); ?></textarea>
            </div>
            </div>

<h4></h4>
<div class = "form-group-container"></div>
            <div class="form-group" style="width: 45%; display: inline-block;">
                <label for="property_damage">Damage to Property (₱):</label>
                <input type="text" id="property_damage" name="property_damage" value="<?php echo htmlspecialchars($report['property_damage']); ?>" class="form-control" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
            </div>

<div class="form-group" style="width: 45%; display: inline-block;">
    <label for="alarm_status">Alarm Status:</label>
    <select id="alarm_status" name="alarm_status" class="form-control" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
        <option value="" disabled>Select Alarm Status</option>
        <option value="1st Alarm" <?php echo ($report['alarm_status'] === '1st Alarm') ? 'selected' : ''; ?>>1st Alarm</option>
        <option value="2nd Alarm" <?php echo ($report['alarm_status'] === '2nd Alarm') ? 'selected' : ''; ?>>2nd Alarm</option>
        <option value="3rd Alarm" <?php echo ($report['alarm_status'] === '3rd Alarm') ? 'selected' : ''; ?>>3rd Alarm</option>
        <option value="4th Alarm" <?php echo ($report['alarm_status'] === '4th Alarm') ? 'selected' : ''; ?>>4th Alarm</option>
        <option value="5th Alarm" <?php echo ($report['alarm_status'] === '5th Alarm') ? 'selected' : ''; ?>>5th Alarm</option>
    </select>
</div>
</div>
<div class = "form-group-container">
<div class="form-group" style="width: 45%; display: inline-block;">
    <label for="occupancy_type">Type of Occupancy:</label>
    <select id="occupancy_type" name="occupancy_type" class="form-control" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
        <option value="" disabled>Select Type of Occupancy</option>
        <option value="Residential" <?php echo ($report['occupancy_type'] === 'Residential') ? 'selected' : ''; ?>>Residential</option>
        <option value="Commercial" <?php echo ($report['occupancy_type'] === 'Commercial') ? 'selected' : ''; ?>>Commercial</option>
        <option value="Industrial" <?php echo ($report['occupancy_type'] === 'Industrial') ? 'selected' : ''; ?>>Industrial</option>
        <option value="Institutional" <?php echo ($report['occupancy_type'] === 'Institutional') ? 'selected' : ''; ?>>Institutional</option>
        <option value="Vehicular" <?php echo ($report['occupancy_type'] === 'Vehicular') ? 'selected' : ''; ?>>Vehicular</option>
        <option value="Others" <?php echo ($report['occupancy_type'] === 'Others') ? 'selected' : ''; ?>>Others</option>
    </select>
</div>
 
            <div class="form-group" style="width: 45%; display: inline-block;">
                <label for="fire_types">Cause of Fire:</label>
                <select id="fire_types" name="fire_types" class="form-control" <?php echo !$can_edit ? 'disabled' : ''; ?>>
                    <option value="" disabled selected>Select Fire Cause</option>
                    <?php while ($row = mysqli_fetch_assoc($result_fire_types)) { ?>
                        <option value="<?php echo htmlspecialchars($row['fire_types']); ?>" 
                                <?php echo ($report['fire_types'] === $row['fire_types']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['fire_types']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
</fieldset>
<br>
<fieldset>
    <legend>Photos of the Scene</legend>
    <div class="documents-section">

        <!-- Photo Gallery -->
        <?php if ($report['documentation_photos']) { ?>
        <section class="photo-gallery">
            <h3>Photos of the Scene</h3>
            <input type="hidden" id="existing_photos_input" name="existing_photos_input" value="<?php echo htmlspecialchars($report['documentation_photos']); ?>">
           <div class="photo-grid">
                <?php
                $photos = explode(',', $report['documentation_photos']);
                foreach ($photos as $index => $photo) {
                    echo "<div class='photo-container' data-path='$photo'>";
                    echo "<img src='$photo' alt='Documentation Photo' class='scene-photo'>";
                    // Hidden input for each photo to keep track
                    echo "<input type='hidden' name='existing_photos[]' value='$photo' class='existing-photo-input'>";
                    // echo "<button type='button' class='delete-photo-btn' data-index='$index' data-path='$photo' title='Delete Photo'>X</button>";
                    echo "</div>";
                }
                ?>
            </div>
        </section>
        <?php } ?>

        <!-- <div class="form-group">
            <label for="documentation_photos" class="file-icon-label">
                <i class="fa-solid fa-plus"></i>
            </label>
                <label for="documentation_photos" style = "font-weight: lighter;">Add New</label>
            <input type="file" id="documentation_photos" name="documentation_photos[]" class="form-control" multiple accept="image/*" onchange="previewImages(event)" style="display:none;" <?php echo !$can_edit ? 'disabled' : ''; ?>>
        </div> -->
        <div id="image-previews" class="image-previews"></div>
        </fieldset>
        <br>
        <fieldset>
        <legend> Required Attachments  </legend> 
       
   <h4>Required Attachments</h4>
     <div class="form-group" style="margin-bottom: 0;">
 
    <div class="tab-container">
        <button type="button" class="tab-btn" onclick="showTab('spot')">Spot Investigation Report</button>
        <button type="button" class="tab-btn" onclick="showTab('progress')">Progress Investigation Report</button>
        <button type="button" class="tab-btn" onclick="showTab('final')">Final Investigation Report</button>
    </div>
</div>

<div id="spot_report_section" class="report-section" style="display:none;">
    <div class="form-group">
    <?php if ($report['narrative_report']) { ?>
        <div class="narrative-report">
            <h3>Spot Investigation Report</h3>
            <a href="<?php echo $report['narrative_report']; ?>" target="_blank" class = "btn-view"><i class="fa-solid fa-eye"></i></a>
            <a href="<?php echo $report['narrative_report']; ?>" download class="btn-download"><i class="fa-solid fa-download"></i></a>
            <!-- <button type="button" class="btn btn-delete" onclick="deleteReportFile('narrative_report', <?php echo $report_id; ?>)"><i class="fa-solid fa-trash"></i></button> -->
            <div id="narrative-preview" class="narrative-preview">
                <h4>Preview:</h4>
                <?php 
                $file_extension = pathinfo($report['narrative_report'], PATHINFO_EXTENSION);
                if (strtolower($file_extension) === 'pdf') { ?>
                    <iframe src="<?php echo $report['narrative_report']; ?>" width="220%" height="500px"></iframe>
                <?php } else { ?>
                    <p>Preview not available for this file type. <a href="<?php echo $report['narrative_report']; ?>" target="_blank">Download to view the report.</a></p>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
    <div id="narrative-preview"></div>
    <!-- <label for="narrative_report">Change Spot Investigation Report:</label>
    <label for="narrative_report" class="file-icon-label"><i class="fa-solid fa-pen-to-square"><span id="labelChange"></i></label>
    <input type="file" id="narrative_report" name="narrative_report" class="form-control"
    accept=".pdf,.doc,.docx,.txt,.rtf" onchange="previewReport(event, 'narrative-preview')" <?php echo !$can_edit ? 'disabled' : ''; ?>> -->
    </div>
   
</div>
<!-- Progress Report Section -->
<div id="progress_report_section" class="report-section" style="display:none;">
    <div class="form-group">
    <?php if ($report['progress_report']) { ?>
        <div class="narrative-report">
            <h3>Progress Report</h3>
 <a href="<?php echo $report['progress_report']; ?>" target="_blank" class = "btn-view"><i class="fa-solid fa-eye"></i></a>
            <a href="<?php echo $report['progress_report']; ?>" download class="btn-download"><i class="fa-solid fa-download"></i></a>
            <!-- <button type="button" class="btn btn-delete" onclick="deleteReportFile('progress_report', <?php echo $report_id; ?>)"><i class="fa-solid fa-trash"></i></button>            -->
             <div id="progress-preview" class="narrative-preview">
                <h4>Preview:</h4>
                <?php 
                $file_extension = pathinfo($report['progress_report'], PATHINFO_EXTENSION);
                if (strtolower($file_extension) === 'pdf') { ?>
                    <iframe src="<?php echo $report['progress_report']; ?>" width="220%" height="500px"></iframe>
                <?php } else { ?>
                    <p>Preview not available for this file type. <a href="<?php echo $report['progress_report']; ?>" target="_blank">Download to view the report.</a></p>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
    <div id="progress-preview"></div>
    <!-- <label for="progress_report">Change Progress Report:</label>
    <label for="progress_report" class="file-icon-label"><i class="fa-solid fa-pen-to-square"></i></label>
     <input type="file" id="progress_report" name="progress_report" class="form-control"
         accept=".pdf,.doc,.docx,.txt,.rtf" onchange="previewReport(event, 'progress-preview')" <?php echo !$can_edit ? 'disabled' : ''; ?>> -->
    </div>
    
</div>

<!-- Final Report Section -->
<div id="final_report_section" class="report-section" style="display:none;">
    <div class="form-group">
    <?php if ($report['final_investigation_report']) { ?>
        <div class="narrative-report">
            <h3>Final Investigation Report</h3>
            <a href="<?php echo $report['final_investigation_report']; ?>" target="_blank" class="btn-view"><i class="fa-solid fa-eye"></i></a>
            <a href="<?php echo $report['final_investigation_report']; ?>" download class="btn-download"><i class="fa-solid fa-download"></i></a>
            <!-- <button type="button" class="btn btn-delete" onclick="deleteReportFile('final_investigation_report', <?php echo $report_id; ?>)"><i class="fa-solid fa-trash"></i></button> -->
            <div id="final-preview" class="narrative-preview">
                <h4>Preview:</h4>
                <?php 
                $file_extension = pathinfo($report['final_investigation_report'], PATHINFO_EXTENSION);
                if (strtolower($file_extension) === 'pdf') { ?>
                    <iframe src="<?php echo $report['final_investigation_report']; ?>" width="220%" height="500px"></iframe>
                <?php } else { ?>
                    <p>Preview not available for this file type. <a href="<?php echo $report['final_investigation_report']; ?>" target="_blank">Download to view the report.</a></p>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
    <div id="final-preview"></div>
     <!-- <label for="final_investigation_report">Change Final Investigation Report:</label>
    <label for="final_investigation_report" class="file-icon-label"><i class="fa-solid fa-pen-to-square"></i></label>
     <input type="file" id="final_investigation_report" name="final_investigation_report" class="form-control"
         accept=".pdf,.doc,.docx,.txt,.rtf" onchange="previewReport(event, 'final-preview')" <?php echo !$can_edit ? 'disabled' : ''; ?>> -->
       </div>
        </div>
 </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="saveBtn" disabled <?php echo !$can_edit ? 'style="display:none;"' : ''; ?>>Save</button>
                <a href="fire_incident_report.php" class="btn btn-cancel">Cancel</a>
            </div>
        </form>

</div>
</div>
</div>
<div id="successModal" class="success-modal">
    <div class="sucess-modal-content">
<i class="fa-regular fa-circle-check"></i> <h2>Success</h2>
        <p> Report Updated Successfully.</p>
    </div>
</div>

<div id="confirmPhotoDeleteModal" class="confirm-delete-modal" style="display:none;">
    <div class="modal-content">
        <h3>Confirm Delete?</h3>
        <hr>
        <p> Are you sure you want to delete this Photo? </p>
        <button id="confirmPhotoDeleteBtn" class="confirm-btn">Delete</button>
        <button id="cancelPhotoDeleteBtn" class="cancel-btn">Cancel</button>
    </div>
</div>

<div id="confirmDeleteModal" class="confirm-delete-modal" style="display:none;">
    <div class="modal-content">
   <h3>Confirm Delete?</h3>
        <hr>
        <p> </p>
        <button id="confirmDeleteBtn" class="confirm-btn">Delete</button>
        <button id="cancelDeleteBtn" class="cancel-btn">Cancel</button>
    </div>
</div>

<div id="photoViewModal" class="photo-view-modal" style="display:none;">
    <div class="photo-modal-content">
        <span id="closePhotoModal" class="close-photo-modal" style="cursor:pointer; font-size:2em;">&times;</span>
        <img id="modalPhoto" src="" alt="Photo" style="max-width:90vw; max-height:80vh; display:block; margin:auto;">
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
// --- Tab Switching for Substantiating Documents ---
function showTab(tab) {
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    // Add active class to the clicked tab button
    if (tab === 'spot') {
        document.querySelector('.tab-btn[onclick="showTab(\'spot\')"]').classList.add('active');
    } else if (tab === 'progress') {
        document.querySelector('.tab-btn[onclick="showTab(\'progress\')"]').classList.add('active');
    } else if (tab === 'final') {
        document.querySelector('.tab-btn[onclick="showTab(\'final\')"]').classList.add('active');
    }
    // Hide all sections
    document.getElementById('spot_report_section').style.display = 'none';
    document.getElementById('progress_report_section').style.display = 'none';
    document.getElementById('final_report_section').style.display = 'none';
    // Show selected section
    if (tab === 'spot') {
        document.getElementById('spot_report_section').style.display = 'block';
    } else if (tab === 'progress') {
        document.getElementById('progress_report_section').style.display = 'block';
    } else if (tab === 'final') {
        document.getElementById('final_report_section').style.display = 'block';
    }
}

// --- Show Modal on Success ---
function showModal(redirectUrl) {
    const modal = document.getElementById('successModal');
    modal.style.display = 'block';
    setTimeout(() => {
        window.location.href = redirectUrl;
    }, 2000);
}
<?php if (isset($success_message)) { ?>
    showModal('<?php echo isset($redirect_target) ? $redirect_target : "fire_incident_report.php"; ?>');
<?php } ?>

// --- Enable Save Button on Any Change ---
const saveBtn = document.getElementById('saveBtn');
const mainForm = document.querySelector('form[action^="view_report.php"]');
let formChanged = false;
function enableSave() {
    if (!formChanged) {
        saveBtn.disabled = false;
        formChanged = true;
    }
}
// Listen for changes in all inputs, selects, textareas
mainForm.querySelectorAll('input, select, textarea').forEach(el => {
    el.addEventListener('change', enableSave);
    el.addEventListener('input', enableSave);
});
// Listen for file input changes
['documentation_photos', 'narrative_report', 'progress_report', 'final_investigation_report'].forEach(id => {
    const fileInput = document.getElementById(id);
    if (fileInput) {
        fileInput.addEventListener('change', enableSave);
    }
});
// Listen for photo deletion (existing photos)
document.addEventListener('click', function (event) {
    if (event.target.classList.contains('delete-photo-btn')) {
        enableSave();
    }
});
// Listen for substantiating document deletion
document.getElementById('confirmDeleteBtn').addEventListener('click', enableSave);

// --- Preview Report Files ---
function previewReport(event, previewContainerId) {
    enableSave();
    const previewContainer = document.getElementById(previewContainerId);
    const section = previewContainer.closest('.report-section');
    if (section) section.style.display = 'block';
    previewContainer.innerHTML = '';
    const file = event.target.files[0];
    if (!file) return;
    const fileUrl = URL.createObjectURL(file);
    const fileExtension = file.name.split('.').pop().toLowerCase();
    if (fileExtension === 'pdf') {
        previewContainer.innerHTML = `
            <h4>Preview:</h4>
            <iframe src="${fileUrl}" width="210%" height="500px"></iframe>
        `;
    } else if (['doc', 'docx', 'txt', 'rtf'].includes(fileExtension)) {
        previewContainer.innerHTML = `
            <h4>Preview not available.</h4>
            <p><a href="${fileUrl}" target="_blank">Download to view the report.</a></p>
        `;
    } else {
        previewContainer.innerHTML = `<p>Invalid file format.</p>`;
    }
}

let pendingReportDelete = { type: null, id: null, section: null };
function deleteReportFile(reportType, reportId) {
    pendingReportDelete.type = reportType;
    pendingReportDelete.id = reportId;
    if (reportType === 'narrative_report') {
        pendingReportDelete.section = document.getElementById('spot_report_section').querySelector('.narrative-report');
    } else if (reportType === 'progress_report') {
        pendingReportDelete.section = document.getElementById('progress_report_section').querySelector('.narrative-report');
    } else if (reportType === 'final_investigation_report') {
        pendingReportDelete.section = document.getElementById('final_report_section').querySelector('.narrative-report');
    }
    document.getElementById('confirmDeleteModal').style.display = 'flex';
    document.querySelector('#confirmDeleteModal p').textContent = 'Are you sure you want to delete this file?';
}
document.getElementById('cancelDeleteBtn').onclick = function() {
    document.getElementById('confirmDeleteModal').style.display = 'none';
    pendingReportDelete = { type: null, id: null, section: null };
};
document.getElementById('confirmDeleteBtn').onclick = function() {
    if (!pendingReportDelete.type || !pendingReportDelete.id) return;
    fetch('delete_report_file.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ report_type: pendingReportDelete.type, report_id: pendingReportDelete.id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (pendingReportDelete.section) pendingReportDelete.section.style.display = 'none';
        } else {
            alert('Failed to delete file: ' + data.error);
        }
        document.getElementById('confirmDeleteModal').style.display = 'none';
        pendingReportDelete = { type: null, id: null, section: null };
    })
    .catch(error => {
        alert('Error deleting file.');
        document.getElementById('confirmDeleteModal').style.display = 'none';
        pendingReportDelete = { type: null, id: null, section: null };
    });
};

// --- Preview Images for New Uploads ---
function previewImages(event) {
    enableSave();
    const previewDiv = document.getElementById('image-previews');
    previewDiv.innerHTML = '';
    const files = Array.from(event.target.files);
    files.forEach((file, i) => {
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const wrapper = document.createElement('div');
                wrapper.style.display = 'inline-block';
                wrapper.style.position = 'relative';
                wrapper.style.margin = '5px';
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.maxWidth = '200px';
                img.style.maxHeight = '200px';
                img.style.display = 'block';
                const removeBtn = document.createElement('button');
                removeBtn.innerText = 'X';
                removeBtn.className = 'delete-photo-btn';
                removeBtn.style.position = 'absolute';
                removeBtn.style.top = '8px';
                removeBtn.style.right = '8px';
                removeBtn.style.background = '#e74c3c';
                removeBtn.style.color = '#fff';
                removeBtn.style.border = 'none';
                removeBtn.style.padding = '6px 10px';
                removeBtn.style.borderRadius = '50%';
                removeBtn.style.cursor = 'pointer';
                removeBtn.onclick = function() {
                    wrapper.remove();
                    const input = document.getElementById('documentation_photos');
                    const newFiles = Array.from(input.files).filter((_, idx) => idx !== i);
                    const dt = new DataTransfer();
                    newFiles.forEach(f => dt.items.add(f));
                    input.files = dt.files;
                    enableSave();
                };
                wrapper.appendChild(img);
                wrapper.appendChild(removeBtn);
                previewDiv.appendChild(wrapper);
            };
            reader.readAsDataURL(file);
        }
    });
}

// --- Photo Deletion Modal (Existing Photos) ---
let pendingPhotoDelete = null;
document.addEventListener('click', function (event) {
    if (event.target.classList.contains('delete-photo-btn') && event.target.hasAttribute('data-path')) {
        pendingPhotoDelete = event.target;
        document.getElementById('confirmPhotoDeleteModal').style.display = 'flex';
    }
});
document.getElementById('cancelPhotoDeleteBtn').onclick = function() {
    document.getElementById('confirmPhotoDeleteModal').style.display = 'none';
    pendingPhotoDelete = null;
};
document.getElementById('confirmPhotoDeleteBtn').onclick = function() {
    if (!pendingPhotoDelete) return;
    const photoPath = pendingPhotoDelete.getAttribute('data-path');
    const photoIndex = pendingPhotoDelete.getAttribute('data-index');
    fetch('delete_photo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ path: photoPath, index: photoIndex, report_id: <?php echo $report_id; ?> }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const photoDiv = pendingPhotoDelete.parentElement;
            photoDiv.remove();
            let existingPhotos = document.getElementById('existing_photos_input').value.split(',');
            existingPhotos = existingPhotos.filter(path => path !== photoPath);
            document.getElementById('existing_photos_input').value = existingPhotos.join(',');
            document.getElementById('confirmPhotoDeleteModal').style.display = 'none';
            pendingPhotoDelete = null;
            enableSave();
        } else {
            alert('Failed to delete photo: ' + data.error);
            document.getElementById('confirmPhotoDeleteModal').style.display = 'none';
            pendingPhotoDelete = null;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('confirmPhotoDeleteModal').style.display = 'none';
        pendingPhotoDelete = null;
    });
};

// --- Photo Modal Viewer ---
document.querySelectorAll('.scene-photo').forEach(function(img) {
    img.addEventListener('click', function() {
        document.getElementById('modalPhoto').src = img.src;
        document.getElementById('photoViewModal').style.display = 'flex';
    });
});
document.getElementById('closePhotoModal').onclick = function() {
    document.getElementById('photoViewModal').style.display = 'none';
};

// --- Initialize: Show Spot Tab by Default ---
document.addEventListener('DOMContentLoaded', function() {
    showTab('spot');
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