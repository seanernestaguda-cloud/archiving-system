<?php
include('connection.php');
include('auth_check.php');

// Fetch current settings (assuming a 'settings' table with one row)
$sql = "SELECT * FROM settings LIMIT 1";
$result = $conn->query($sql);
$settings = $result ? $result->fetch_assoc() : [];

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
$stmt_user->close();
$query_barangays = "SELECT barangay_id, barangay_name FROM barangays ORDER BY barangay_name";
$result_barangays = mysqli_query($conn, $query_barangays);
if (!$result_barangays) {
    die("Error fetching barangays: " . mysqli_error($conn));
}

$query_fire_types = "SELECT fire_types_id, fire_types FROM fire_types ORDER BY fire_types";
$result_fire_types = mysqli_query($conn, $query_fire_types);
if (!$result_fire_types) {
    die("Error fetching fire_types: " . mysqli_error($conn));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
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
    $victims = implode(',', array_map('trim', preg_split('/\r\n|\r|\n/', $_POST['victims'])));
    $firefighters = implode(',', array_map('trim', preg_split('/\r\n|\r|\n/', $_POST['firefighters'])));
    $alarm_status = $_POST['alarm_status'];
    $occupancy_type = $_POST['occupancy_type'];
    $property_damage = $_POST['property_damage'];
    $fire_types = $_POST['fire_types'];
    $uploader = $_SESSION['username'];  // Assuming the uploader is the logged-in user
    $department = null;
    $dept_stmt = $conn->prepare("SELECT department FROM users WHERE username = ? LIMIT 1");
    $dept_stmt->bind_param('s', $uploader);
    $dept_stmt->execute();
    $dept_result = $dept_stmt->get_result();
    if ($dept_result && $dept_row = $dept_result->fetch_assoc()) {
        $department = !empty($dept_row['department']) ? $dept_row['department'] : 'N/A';
    }
    $dept_stmt->close();

    // Handling file uploads
    $documentation_photos = [];
    if (isset($_FILES['documentation_photos']) && !empty($_FILES['documentation_photos']['name'][0])) {
        $documentation_photos = [];
        foreach ($_FILES['documentation_photos']['tmp_name'] as $index => $tmp_name) {
            $file_name = $_FILES['documentation_photos']['name'][$index];
            $file_tmp = $_FILES['documentation_photos']['tmp_name'][$index];
            $file_error = $_FILES['documentation_photos']['error'][$index];
            $file_size = $_FILES['documentation_photos']['size'][$index];

            // Check for file errors
            if ($file_error === 0) {
                // Define the upload directory
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);  // Create the directory if it doesn't exist
                }

                // Generate a unique file name to prevent overwriting
                $unique_file_name = time() . "_" . basename($file_name);
                $upload_path = $upload_dir . $unique_file_name;

                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $documentation_photos[] = $upload_path;  // Store the file path
                }
            }
        }
    }

    // Handle narrative report upload
    $narrative_report = '';
    if (isset($_FILES['narrative_report']) && $_FILES['narrative_report']['error'] === 0) {
        $narrative_report_name = $_FILES['narrative_report']['name'];
        $narrative_report_tmp = $_FILES['narrative_report']['tmp_name'];
        $narrative_report_error = $_FILES['narrative_report']['error'];

        if ($narrative_report_error === 0) {
            $narrative_report_path = '../uploads/' . time() . "_" . basename($narrative_report_name);
            if (move_uploaded_file($narrative_report_tmp, $narrative_report_path)) {
                $narrative_report = $narrative_report_path;
            }
        }
    }

    $progress_report = '';
    if (isset($_FILES['progress_report']) && $_FILES['progress_report']['error'] === 0) {
        $progress_report_name = $_FILES['progress_report']['name'];
        $progress_report_tmp = $_FILES['progress_report']['tmp_name'];
        $progress_report_path = '../uploads/' . time() . "_progress_" . basename($progress_report_name);
        if (move_uploaded_file($progress_report_tmp, $progress_report_path)) {
            $progress_report = $progress_report_path;
        }
    }

    $final_investigation_report = '';
    if (isset($_FILES['final_investigation_report']) && $_FILES['final_investigation_report']['error'] === 0) {
        $final_investigation_report_name = $_FILES['final_investigation_report']['name'];
        $final_investigation_report_tmp = $_FILES['final_investigation_report']['tmp_name'];
        $final_investigation_report_path = '../uploads/' . time() . "_final_" . basename($final_investigation_report_name);
        if (move_uploaded_file($final_investigation_report_tmp, $final_investigation_report_path)) {
            $final_investigation_report = $final_investigation_report_path;
        }
    }


    // Save report and uploaded files to the database, now including department
    $query = "INSERT INTO fire_incident_reports (report_title, caller_name, responding_team, fire_location, street, purok, municipality, incident_date, arrival_time, fireout_time, establishment, victims, firefighters, property_damage, fire_types, alarm_status, occupancy_type, uploader, department, documentation_photos, narrative_report, progress_report, final_investigation_report)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    $documentation_photos = implode(',', $documentation_photos);  // Store multiple photo paths as a comma-separated string
    mysqli_stmt_bind_param($stmt, "sssssssssssssssssssssss", $report_title, $caller_name, $responding_team, $fire_location, $street, $purok, $municipality, $incident_date, $arrival_time, $fireout_time, $establishment, $victims, $firefighters, $property_damage, $fire_types, $alarm_status, $occupancy_type, $uploader, $department, $documentation_photos, $narrative_report, $progress_report, $final_investigation_report);
    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Report created successfully!";
        // Log activity with user_type
        $new_report_id = mysqli_insert_id($conn);
        $user_type = '';
        $type_stmt = $conn->prepare("SELECT user_type FROM users WHERE username = ? LIMIT 1");
        $type_stmt->bind_param('s', $uploader);
        $type_stmt->execute();
        $type_result = $type_stmt->get_result();
        if ($type_result && $type_row = $type_result->fetch_assoc()) {
            $user_type = $type_row['user_type'];
        }
        $type_stmt->close();
        $log_query = "INSERT INTO activity_logs (username, user_type, action, report_id, details) VALUES (?, ?, 'create', ?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $log_details = "Created report: " . $report_title;
        $log_stmt->bind_param('ssis', $uploader, $user_type, $new_report_id, $log_details);
        $log_stmt->execute();
        $log_stmt->close();
        // No immediate redirect; let JS handle modal and redirect
    } else {
        $error_message = "There was an error creating the report. Please try again.";
    }
}

$sql_settings = "SELECT system_name FROM settings LIMIT 1";
$result_settings = $conn->query($sql_settings);
$system_name = 'BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM';
if ($result_settings && $row_settings = $result_settings->fetch_assoc()) {
    $system_name = $row_settings['system_name'];
}

mysqli_close($conn);
?>





<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Fire Incident Report</title>
    <link rel="stylesheet" href="reportstyle.css">
    <link rel="stylesheet" href="permitstyle.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="icon" type="image/png" href="../REPORT.png">
</head>
<style>
    .header {
        position: fixed;
        z-index: 1000;
    }

    /* Required field asterisk */
    .required {
        color: red;
        margin-left: 2px;
        font-weight: bold;
    }

    /* Required text next to asterisk */
    .required-text {
        color: red;
        font-size: 13px;
        margin-left: 2px;
        font-weight: normal;
        display: inline;
        transition: opacity 0.2s;
    }

    .required-text.filled {
        display: none;
    }

    /* Title */
    .form-header {
        background: #003D73;
        color: white;
        padding: 15px;
        margin: 0px;
        margin-bottom: 20px;
        text-align: center;
        font-size: 15px;
        border-radius: 10px;
    }

    /* Form group */
    .form-group {
        margin: 10px;
    }

    /* Label styling */
    label {
        color: #003D73;
        display: block;
        margin-bottom: 5px;
    }

    /* Input fields */
    input[type="text"],
    input[type="datetime-local"],
    input[type="time"],
    select,
    textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
        border-bottom: 1px solid #444;
    }

    button[type="submit"],
    button[type="button"].btn-primary {
        background-color: #003D73;
        /* BFP Blue */
        color: white;
        border: none;
        padding: 15px;
        font-size: 16px;
        border-radius: 4px;
        cursor: pointer;
        width: 10%;
        /* Make it smaller to allow space for the cancel button */
        margin-top: 15px;
        text-align: center;
        text-decoration: none;
    }

    .btn-cancel {
        background-color: #bd000a;
        /* BFP Blue */
        color: white;
        border: none;
        padding: 15px;
        font-size: 16px;
        border-radius: 4px;
        cursor: pointer;
        width: 10%;
        /* Make it smaller to allow space for the cancel button */
        margin-top: 15px;
        text-align: center;
        text-decoration: none;
    }

    button[type="submit"]:hover,
    button[type="button"].btn-primary:hover {
        background-color: #002D57;
        /* Darker Blue on hover */
    }

    .btn-cancel:hover {
        background-color: #81000a;
    }


    .form-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 5px;
    }

    /* Alert Message */
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
        font-size: 16px;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
    }

    .container {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-top: solid 5px #003D73;
        padding: 20px;
        margin: 80px 20px 40px 20px;
        overflow: hidden;
    }

    legend {
        text-align: center;
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 10px;
    }

    input[type="file"] {
        display: none;
    }

    /* Style the icon label */
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

    .file-icon-label i {
        background-color: #4444;
        padding: 20px;
        border-radius: 8px;
    }

    .file-icon-label i:hover {
        background-color: #a1a1a1ff;
    }


    .remove-photo-btn {
        position: absolute;
        top: 2px;
        right: 2px;
        background: #bd000a;
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        font-size: 16px;
        cursor: pointer;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0.8;
        transition: opacity 0.2s;
    }

    .remove-photo-btn:hover {
        opacity: 1;
        background: #81000a;
    }

    .custom-file-upload {
        border: 1px dashed #ddd;
        border-radius: 8px;
        padding: 24px;
        background: #fafafa;
        text-align: center;
        width: 100%;
        max-width: 400px;
        margin: 0 auto 20px auto;
        position: relative;
    }

    .drop-area {
        display: block;
        cursor: pointer;
        padding: 24px 0;
    }

    .upload-icon {
        font-size: 32px;
        color: #003D73;
        margin-bottom: 8px;
        display: block;
    }

    .upload-btn {
        background: #003D73;
        color: #fff;
        border: none;
        border-radius: 4px;
        padding: 10px 24px;
        margin-top: 12px;
        cursor: pointer;
    }

    .upload-btn:hover {
        background: #002D57;
    }

    .max-size-info {
        font-size: 14px;
        color: #555;
        margin-top: 10px;
    }

    #file-list-photos,
    #file-preview-narrative,
    #file-preview-progress,
    #file-preview-final {
        margin-top: 10px;
        font-size: 14px;
        color: #003D73;
    }

    .drop-area.dragover {
        background: #e3f2fd;
        border-color: #003D73;
    }

    .card {
        max-width: 900px;
        /* change to desired max width (e.g. 700px for smaller) */
        width: 90%;
        /* responsive width */
        margin: 90px auto 40px;
        /* center and control vertical spacing */
        padding: 18px;
        /* inner spacing */
        box-sizing: border-box;
        border-radius: 8px;
        /* match existing look */
        background: #fff;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
    }

    .form-step {
        display: none;
    }

    .form-step:first-child {
        display: block;
    }

    /* Stepper styles */
    .stepper-container {
        width: 100%;
        margin-bottom: 30px;
        display: flex;
        justify-content: center;
    }

    .stepper {
        display: flex;
        align-items: center;
        gap: 0;
    }

    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 120px;
    }

    .circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #e0e0e0;
        color: #003D73;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
        margin-bottom: 6px;
        border: 2px solid #e0e0e0;
        transition: background 0.2s, color 0.2s, border 0.2s;
    }

    .label {
        font-size: 14px;
        color: #888;
        text-align: center;
    }

    .line {
        width: 60px;
        height: 3px;
        background: #e0e0e0;
        margin: 0 4px;
        border-radius: 2px;
    }

    .stepper-container {
        width: 100%;
        margin-bottom: 30px;
        display: flex;
        justify-content: center;
    }

    .stepper {
        display: flex;
        align-items: center;
        gap: 0;
    }

    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 120px;
    }

    .circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #e0e0e0;
        color: #003D73;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
        margin-bottom: 6px;
        border: 2px solid #e0e0e0;
        transition: background 0.2s, color 0.2s, border 0.2s;
    }

    .label {
        font-size: 14px;
        color: #888;
        text-align: center;
    }

    .line {
        width: 60px;
        height: 3px;
        background: #e0e0e0;
        margin: 0 4px;
        border-radius: 2px;
    }

    .step.active .circle,
    .step.completed .circle {
        background: #003D73;
        color: #fff;
        border: 2px solid #003D73;
    }

    .step.active .label,
    .step.completed .label {
        color: #003D73;
    }

    .step.completed .circle {
        background: #fff;
        color: #003D73;
        border: 2px solid #003D73;
        position: relative;
    }

    .step.completed .circle::after {
        content: '✔';
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #003D73;
        font-size: 18px;
    }

    .step.completed .circle {
        background: #fff;
    }

    .step.completed .circle>* {
        display: none;
    }

    .step.completed .circle::after {
        display: block;
    }

    .line.active {
        background: #003D73;
    }
</style>

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
            <div class="form-header">
                <h2>Create Fire Incident Report</h2>
            </div>
            <?php if (isset($success_message)) { ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showSuccessModal("<?php echo $success_message; ?>", true);
                    });
                </script>
            <?php } ?>
            <?php if (isset($error_message)) { ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php } ?>
            <!-- Fire Incident Report Form -->
            <div class="stepper-container">
                <div class="stepper">
                    <div class="step" id="stepper-1">
                        <div class="circle">1</div>
                        <div class="label">Fill up the form</div>
                    </div>
                    <div class="line"></div>
                    <div class="step" id="stepper-2">
                        <div class="circle">2</div>
                        <div class="label">Add photos of the scene</div>
                    </div>
                    <div class="line"></div>
                    <div class="step" id="stepper-3">
                        <div class="circle">3</div>
                        <div class="label">Upload Documents</div>
                    </div>
                    <div class="line"></div>
                    <div class="step" id="stepper-4">
                        <div class="circle">4</div>
                        <div class="label">Confirm and Submit</div>
                    </div>
                </div>
            </div>
            <form method="POST" action="create_fire_incident_report.php" enctype="multipart/form-data" id="fireIncidentForm">

                <!-- STEP 1: Incident Details -->
                <div class="form-step" id="step-1">
                    <fieldset>
                        <legend>Incident Details</legend>
                        <!-- ...all your incident detail fields here... -->
                        <div class="form-group" style="width: 45%; display: inline-block;">
                            <label for="report_title">Report Title <span class="required">*</span><span class="required-text"> required</span></label>
                            <input type="text" id="report_title" name="report_title" required placeholder="Report Name">
                        </div>
                        <div class="form-group" style="width: 45%; display: inline-block;">
                            <label for="caller_name">Name of the Caller <span class="required">*</span><span class="required-text"> required</span></label>
                            <input type="text" id="caller_name" name="caller_name" required placeholder="Caller Name">
                        </div>
                        <div class="form-group-container"></div>
                        <div class="form-group" style="width: 45%; display: inline-block;">
                            <label for="responding_team">Responding Team <span class="required">*</span><span class="required-text"> required</span></label>
                            <input type="text" id="responding_team" name="responding_team" class="form-control" placeholder="Responding Team" required>
                        </div>

                        <div class="form-group" style="width: 45%; display: inline-block;">
                            <label for="establishment">Establishment Burned <span class="required">*</span><span class="required-text"> required</span></label>
                            <input type="text" id="establishment" name="establishment" class="form-control" placeholder="Name of the Establishment" required>
                        </div>
                        <hr class="section-separator full-bleed">
                        <h4 style="text-align: center;"> Fire Location </h4>
                        <hr class="section-separator full-bleed">
                        <div class="form-group" style="width: 45%; display: inline-block;">
                            <label for="street">Street <span class="required">*</span><span class="required-text"> required</span></label>
                            <input type="text" id="street" name="street" class="form-control" placeholder="street" required>
                        </div>
                        <div class="form-group" style="width: 45%; display: inline-block;">
                            <label for="purok">Purok <span class="required">*</span><span class="required-text"> required</span></label>
                            <input type="text" id="purok" name="purok" class="form-control" placeholder="purok" required>
                        </div>
                        <div class="form-group-container">
                            <div class="form-group" style="width: 45%; display: inline-block;">
                                <label for="fire_location">Barangay <span class="required">*</span><span class="required-text"> required</span></label>
                                <select id="fire_location" name="fire_location" class="form-control" required>
                                    <option value="" disabled selected>Select Barangay</option>
                                    <?php while ($row = mysqli_fetch_assoc($result_barangays)) { ?>
                                        <option value="<?php echo $row['barangay_name']; ?>"><?php echo $row['barangay_name']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>



                            <div class="form-group" style="width: 45%; display: inline-block;">
                                <label for="municipality">Municipality <span class="required">*</span><span class="required-text"> required</span></label>
                                <input type="text" id="municipality" name="municipality" class="form-control" placeholder="municipality" required>
                            </div>
                            <hr class="section-separator full-bleed">
                            <h4 style="text-align: center;"> Time and Date </h4>
                            <hr class="section-separator full-bleed">
                            <div class="form-group-container">
                                <div class="form-group" style="width: 30%; display: inline-block;">
                                    <label for="incident_date">Time and Date Reported <span class="required">*</span><span class="required-text"> required</span></label>
                                    <input type="datetime-local" id="incident_date" name="incident_date" class="form-control" placeholder="Date" required>
                                </div>

                                <div class="form-group" style="width: 30%; display: inline-block;">
                                    <label for="arrival_time">Time of Arrival <span class="required">*</span><span class="required-text"> required</span></label>
                                    <input type="time" id="arrival_time" name="arrival_time" class="form-control" placeholder="Time of Arrival" required>
                                </div>

                                <div class="form-group" style="width: 30%; display: inline-block;">
                                    <label for="fireout_time">Time of Fire Out <span class="required">*</span><span class="required-text"> required</span></label>
                                    <input type="time" id="fireout_time" name="fireout_time" class="form-control" placeholder="Time of Fire Out" required>
                                </div>
                                <hr class="section-separator full-bleed">
                                <div class="form-group-container">
                                    <div class="form-group" style="width: 45%; display: inline-block;">
                                        <label for="alarm_status">Alarm Status <span class="required">*</span><span class="required-text"> required</span></label>
                                        <select id="alarm_status" name="alarm_status" class="form-control" required>
                                            <option value="" disabled selected>Select Alarm Status</option>
                                            <option value="1st Alarm">1st Alarm</option>
                                            <option value="2nd Alarm">2nd Alarm</option>
                                            <option value="3rd Alarm">3rd Alarm</option>
                                            <option value="4th Alarm">4th Alarm</option>
                                            <option value="5th Alarm">5th Alarm</option>
                                        </select>
                                    </div>

                                    <div class="form-group" style="width: 45%; display: inline-block;">
                                        <label for="occupancy_type">Type of Occupancy <span class="required">*</span><span class="required-text"> required</span></label>
                                        <select id="occupancy_type" name="occupancy_type" class="form-control" required>
                                            <option value="" disabled selected>Select Type of Occupancy</option>
                                            <option value="Residential">Residential</option>
                                            <option value="Commercial">Commercial</option>
                                            <option value="Industrial">Industrial</option>
                                            <option value="Institutional">Institutional</option>
                                            <option value="Vehicular">Vehicular</option>
                                            <option value="Others">Others</option>
                                        </select>
                                    </div>
                                </div>


                                <div class="form-group-container">
                                    <div class="form-group" style="width: 45%; display: inline-block;">
                                        <label for="property_damage"> Estimated Damage to Property (₱) <span class="required">*</span><span class="required-text"> required</span></label>
                                        <input type="text" id="property_damage" name="property_damage" class="form-control" placeholder="Amount of Damage to Property" required></i>
                                    </div>

                                    <div class="form-group" style="width: 45%; display: inline-block;">
                                        <label for="fire_type">Cause of Fire</label>
                                        <select name="fire_types" id="fire_type">
                                            <option value="">Select Cause of Fire</option>
                                            <?php while ($row = mysqli_fetch_assoc($result_fire_types)): ?>
                                                <option value="<?php echo $row['fire_types']; ?>">
                                                    <?php echo htmlspecialchars($row['fire_types']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <hr class="section-separator full-bleed">
                                    <h4 style="text-align: center;"> Injured/Casualties </h4>
                                    <hr class="section-separator full-bleed">
                                    <div class="form-group" style="width: 45%; display: inline-block;">
                                        <label for="victims">Civilians<br>
                                            <textarea id="victims" name="victims" rows="10" cols="30" placeholder="Enter each victim on a new line" onfocus="addFirstNumber()" oninput="autoNumber()" style="border-bottom: 1px solid #444;"></textarea><br><br>
                                    </div>

                                    <div class="form-group" style="width: 45%; display: inline-block;">
                                        <label for="firefighters">Firefighters<br>
                                            <textarea id="firefighters" name="firefighters" rows="10" cols="30" placeholder="Enter each firefighter on a new line" onfocus="addFirstNumber()" oninput="autoNumber()" style="border-bottom: 1px solid #444;"></textarea><br><br>
                                    </div>
                    </fieldset>
                    <div class="form-actions">
                        <a href="my_fire_incident_reports.php" class="btn btn-cancel">Cancel</a>
                        <button type="button" class="btn btn-primary" onclick="nextStep(2)">Next</button>
                    </div>
                </div>

                <!-- STEP 2: Photos -->
                <div class="form-step" id="step-2" style="display:none;">
                    <fieldset>
                        <legend>Photos of the Scene</legend>
                        <!-- ...photo upload fields... -->
                        <div class="form-group">
                            <label for="documentation_photos" style="font-weight:bold;">Add Photos of the Scene</label>
                            <hr class="section-separator full-bleed">
                            <div class="custom-file-upload" id="customFileUploadPhotos">
                                <div class="drop-area" id="dropAreaPhotos">
                                    <span class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                                    <span>Drop images here, or click below!</span>
                                    <input type="file" id="documentation_photos" name="documentation_photos[]" multiple accept="image/*" style="display:none;">
                                </div>
                                <button type="button" class="upload-btn" onclick="document.getElementById('documentation_photos').click();">Upload</button>
                                <div class="max-size-info">You can upload images up to a maximum of 2 GB.</div>
                                <div id="file-list-photos"></div>
                            </div>
                        </div>
                    </fieldset>
                    <div class="form-actions">
                        <button type="button" class="btn btn-cancel" onclick="prevStep(1)">Previous</button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(3)">Next</button>
                    </div>
                </div>

                <!-- STEP 3: Required Attachments -->
                <div class="form-step" id="step-3" style="display:none;">
                    <fieldset>
                        <legend> Required Attachments</legend>
                        <!-- Substantiating Documents Dropdown -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight:bold;">Select Attachment</label>
                            <div class="tab-container">
                                <button type="button" class="tab-btn" onclick="showTab('spot')">Spot Investigation Report</button>
                                <button type="button" class="tab-btn" onclick="showTab('progress')">Progress Investigation Report</button>
                                <button type="button" class="tab-btn" onclick="showTab('final')">Final Investigation Report</button>
                            </div>
                        </div>


                        <!-- Spot Investigation Report Upload -->
                        <div id="spot_report_input" class="form-group tab-content" style="display:none;">
                            <label for="narrative_report">Upload Spot Investigation Report:</label>
                            <div class="custom-file-upload" id="customFileUploadSpot">
                                <div class="drop-area" id="dropAreaSpot">
                                    <span class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                                    <span>Drop file here, or click below!</span>
                                    <input type="file" id="narrative_report" name="narrative_report" accept=".pdf,.doc,.docx,.txt,.rtf" style="display:none;" onchange="previewReport(event, 'file-preview-narrative')">
                                </div>
                                <button type="button" class="upload-btn" onclick="document.getElementById('narrative_report').click();">Upload</button>
                                <div class="max-size-info">You can upload files up to a maximum of 2 GB.</div>
                                <div id="file-preview-narrative"></div>
                            </div>
                        </div>

                        <!-- Progress Investigation Report Upload -->
                        <div id="progress_report_input" class="form-group tab-content" style="display:none;">
                            <label for="progress_report">Upload Progress Investigation Report:</label>
                            <div class="custom-file-upload" id="customFileUploadProgress">
                                <div class="drop-area" id="dropAreaProgress">
                                    <span class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                                    <span>Drop file here, or click below!</span>
                                    <input type="file" id="progress_report" name="progress_report" accept=".pdf,.doc,.docx,.txt,.rtf" style="display:none;" onchange="previewReport(event, 'file-preview-progress')">
                                </div>
                                <button type="button" class="upload-btn" onclick="document.getElementById('progress_report').click();">Upload</button>
                                <div class="max-size-info">You can upload files up to a maximum of 2 GB.</div>
                                <div id="file-preview-progress"></div>
                            </div>
                        </div>

                        <!-- Final Investigation Report Upload -->
                        <div id="final_report_input" class="form-group tab-content" style="display:none;">
                            <label for="final_investigation_report">Upload Final Investigation Report:</label>
                            <div class="custom-file-upload" id="customFileUploadFinal">
                                <div class="drop-area" id="dropAreaFinal">
                                    <span class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                                    <span>Drop file here, or click below!</span>
                                    <input type="file" id="final_investigation_report" name="final_investigation_report" accept=".pdf,.doc,.docx,.txt,.rtf" style="display:none;" onchange="previewReport(event, 'file-preview-final')">
                                </div>
                                <button type="button" class="upload-btn" onclick="document.getElementById('final_investigation_report').click();">Upload</button>
                                <div class="max-size-info">You can upload files up to a maximum of 2 GB.</div>
                                <div id="file-preview-final"></div>
                            </div>
                        </div>
                    </fieldset>
                    <div class="form-actions">
                        <button type="button" class="btn btn-cancel" onclick="prevStep(2)">Previous</button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(4)">Next</button>
                    </div>
                </div>
                <div class="form-step" id="step-4" style="display:none;">
                    <fieldset>
                        <legend>Confirm and Submit</legend>
                        <p style="text-align: center;">Please review all information before submitting your report.</p>
                        <div id="summary" style="margin: 20px 0; padding: 20px; background: #f7f7f7; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
                            <!-- Summary will be injected here -->
                        </div>
                    </fieldset>
                    <div class="form-actions">
                        <button type="button" class="btn btn-cancel" onclick="prevStep(3)">Previous</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
        </div>
        </form>

    </div>
    </div>
    <div id="successModal" class="success-modal" style="display: none;">
        <div class="success-modal-content">
            <i class="fa-regular fa-circle-check"></i>
            <h2>Success!</h2>
            <p id="successMessage"></p>
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
    <div id="successModal" class="success-modal">
        <div class="success-modal-content">
            <i class="fa-regular fa-circle-check"></i>
            <h2>Success!</h2>
            <p id="successMessage"></p>
        </div>
    </div>

    <script>
        // Update nextStep/prevStep for 4 steps
        function nextStep(step) {
            // Only validate required fields when moving from step 1 to step 2
            if (step === 2) {
                var step1Fields = document.querySelectorAll('#step-1 [required]');
                let valid = true;
                step1Fields.forEach(function(field) {
                    if (!field.value.trim()) {
                        valid = false;
                    }
                });
                if (!valid) {
                    // Show browser's native validation message for the first invalid field
                    for (let field of step1Fields) {
                        if (!field.value.trim()) {
                            field.reportValidity();
                            break;
                        }
                    }
                    return;
                }
            }
            document.querySelectorAll('.form-step').forEach(div => div.style.display = 'none');
            document.getElementById('step-' + step).style.display = 'block';
            updateStepper(step);
            if (step === 4) {
                showSummary();
            }
        }

        // Show summary of all entered details in step 4
        function showSummary() {
            var summaryDiv = document.getElementById('summary');
            var fields = [{
                    label: 'Report Title',
                    id: 'report_title'
                },
                {
                    label: 'Name of the Caller',
                    id: 'caller_name'
                },
                {
                    label: 'Responding Team',
                    id: 'responding_team'
                },
                {
                    label: 'Establishment Burned',
                    id: 'establishment'
                },
                {
                    label: 'Street',
                    id: 'street'
                },
                {
                    label: 'Purok',
                    id: 'purok'
                },
                {
                    label: 'Barangay',
                    id: 'fire_location'
                },
                {
                    label: 'Municipality',
                    id: 'municipality'
                },
                {
                    label: 'Time and Date Reported',
                    id: 'incident_date'
                },
                {
                    label: 'Time of Arrival',
                    id: 'arrival_time'
                },
                {
                    label: 'Time of Fire Out',
                    id: 'fireout_time'
                },
                {
                    label: 'Alarm Status',
                    id: 'alarm_status'
                },
                {
                    label: 'Type of Occupancy',
                    id: 'occupancy_type'
                },
                {
                    label: 'Estimated Damage to Property (₱)',
                    id: 'property_damage'
                },
                {
                    label: 'Cause of Fire',
                    id: 'fire_type'
                },
                {
                    label: 'Civilians',
                    id: 'victims'
                },
                {
                    label: 'Firefighters',
                    id: 'firefighters'
                }
            ];
            var html = '<h3 style="text-align:center;">Summary of Entered Details</h3><table style="width:100%;border-collapse:collapse;">';
            fields.forEach(function(field) {
                var input = document.getElementById(field.id);
                var value = '';
                if (input) {
                    if (input.tagName === 'SELECT') {
                        value = input.options[input.selectedIndex] ? input.options[input.selectedIndex].text : '';
                    } else if (input.tagName === 'TEXTAREA') {
                        value = input.value.replace(/\n/g, '<br>');
                    } else {
                        value = input.value;
                    }
                }
                html += '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;width:35%;color:#003D73;">' + field.label + '</td>' +
                    '<td style="padding:8px;border-bottom:1px solid #eee;">' + (value ? value : '<span style="color:#bd000a;">N/A</span>') + '</td></tr>';
            });

            // Photos
            var photoInput = document.getElementById('documentation_photos');
            var photoNames = [];
            if (photoInput && photoInput.files && photoInput.files.length > 0) {
                for (var i = 0; i < photoInput.files.length; i++) {
                    photoNames.push(photoInput.files[i].name);
                }
            }
            html += '<tr><td colspan="2" style="padding:12px 8px;font-weight:bold;color:#003D73;background:#f0f8ff;">Photos of the Scene</td></tr>';
            html += '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;width:35%;color:#003D73;">Photos</td>' +
                '<td style="padding:8px;border-bottom:1px solid #eee;">' + (photoNames.length ? photoNames.join('<br>') : '<span style="color:#bd000a;">No photos selected</span>') + '</td></tr>';

            // Attachments
            var attachments = [{
                    label: 'Spot Investigation Report',
                    id: 'narrative_report'
                },
                {
                    label: 'Progress Investigation Report',
                    id: 'progress_report'
                },
                {
                    label: 'Final Investigation Report',
                    id: 'final_investigation_report'
                }
            ];
            html += '<tr><td colspan="2" style="padding:12px 8px;font-weight:bold;color:#003D73;background:#f0f8ff;">Attachments</td></tr>';
            attachments.forEach(function(att) {
                var input = document.getElementById(att.id);
                var fileName = '';
                if (input && input.files && input.files.length > 0) {
                    fileName = input.files[0].name;
                }
                html += '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;width:35%;color:#003D73;">' + att.label + '</td>' +
                    '<td style="padding:8px;border-bottom:1px solid #eee;">' + (fileName ? fileName : '<span style="color:#bd000a;">No file selected</span>') + '</td></tr>';
            });
            html += '</table>';
            summaryDiv.innerHTML = html;
        }

        // Hide 'required' text when field is filled
        function updateRequiredTextVisibility() {
            document.querySelectorAll('#step-1 [required]').forEach(function(field) {
                var label = field.closest('.form-group')?.querySelector('label') || field.closest('div')?.querySelector('label');
                if (!label) return;
                var reqText = label.querySelector('.required-text');
                if (!reqText) return;
                if (field.value.trim()) {
                    reqText.classList.add('filled');
                } else {
                    reqText.classList.remove('filled');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initial check
            updateRequiredTextVisibility();
            // Listen for input on all required fields in step 1
            document.querySelectorAll('#step-1 [required]').forEach(function(field) {
                field.addEventListener('input', updateRequiredTextVisibility);
                field.addEventListener('change', updateRequiredTextVisibility);
            });
        });

        function prevStep(step) {
            document.querySelectorAll('.form-step').forEach(div => div.style.display = 'none');
            document.getElementById('step-' + step).style.display = 'block';
            updateStepper(step);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateStepper(1);
        });
        // Documentation Photos Drag & Drop
        const dropAreaPhotos = document.getElementById('dropAreaPhotos');
        const fileInputPhotos = document.getElementById('documentation_photos');
        const fileListPhotos = document.getElementById('file-list-photos');
        let selectedPhotos = [];

        dropAreaPhotos.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropAreaPhotos.classList.add('dragover');
        });
        dropAreaPhotos.addEventListener('dragleave', function(e) {
            dropAreaPhotos.classList.remove('dragover');
        });
        dropAreaPhotos.addEventListener('drop', function(e) {
            e.preventDefault();
            dropAreaPhotos.classList.remove('dragover');
            handlePhotoFiles(e.dataTransfer.files);
        });
        fileInputPhotos.addEventListener('change', function() {
            handlePhotoFiles(fileInputPhotos.files);
        });

        function handlePhotoFiles(files) {
            for (const file of files) {
                if (
                    file.type.startsWith('image/') &&
                    !selectedPhotos.some(f => f.name === file.name && f.size === file.size)
                ) {
                    selectedPhotos.push(file);
                }
            }
            updatePhotoList();
        }

        function updatePhotoList() {
            fileListPhotos.innerHTML = '';
            selectedPhotos.forEach((file, idx) => {
                const div = document.createElement('div');
                div.style.position = 'relative';
                div.style.display = 'inline-block';
                div.style.margin = '5px';
                const reader = new FileReader();
                reader.onload = function(e) {
                    div.innerHTML = `
                <img src="${e.target.result}" style="max-width:120px;max-height:120px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.15);">
                <button type="button" class="remove-photo-btn" style="position:absolute;top:2px;right:2px;" onclick="removePhoto(${idx})">&times;</button>
            `;
                };
                reader.readAsDataURL(file);
                fileListPhotos.appendChild(div);
            });
        }

        window.removePhoto = function(idx) {
            selectedPhotos.splice(idx, 1);
            updatePhotoList();
        };

        // Before submitting, update the file input with the selected files
        document.querySelector('form').addEventListener('submit', function(e) {
            const dataTransfer = new DataTransfer();
            selectedPhotos.forEach(file => dataTransfer.items.add(file));
            document.getElementById('documentation_photos').files = dataTransfer.files;
        });

        // Drag & Drop for Spot, Progress, Final Reports
        function setupDropArea(dropAreaId, inputId, previewId) {
            const dropArea = document.getElementById(dropAreaId);
            const fileInput = document.getElementById(inputId);
            dropArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                dropArea.classList.add('dragover');
            });
            dropArea.addEventListener('dragleave', function(e) {
                dropArea.classList.remove('dragover');
            });
            dropArea.addEventListener('drop', function(e) {
                e.preventDefault();
                dropArea.classList.remove('dragover');
                fileInput.files = e.dataTransfer.files;
                previewReport({
                    target: fileInput
                }, previewId);
            });
        }

        setupDropArea('dropAreaSpot', 'narrative_report', 'file-preview-narrative');
        setupDropArea('dropAreaProgress', 'progress_report', 'file-preview-progress');
        setupDropArea('dropAreaFinal', 'final_investigation_report', 'file-preview-final');
    </script>
</body>

</html>
<script src="../js/archivescript.js"></script>
<script src="../js/createreport.js"></script>
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

    // Function to show the modal
    // Function to show the modal
    function showSuccessModal(message, redirectToMyReports = false) {
        document.getElementById('successMessage').textContent = message;
        document.getElementById('successModal').style.display = "block";
        setTimeout(() => {
            window.location.href = redirectToMyReports ? "my_fire_incident_reports.php" : "fire_incident_report.php";
        }, 2000);
    }

    // Function to close the modal
    function closeModal() {
        document.getElementById('successModal').style.display = "none";
    }

    // Trigger the modal if a success message is set
    <?php if (isset($_SESSION['success_message'])): ?>
        showSuccessModal("<?php echo $_SESSION['success_message']; ?>");
        <?php unset($_SESSION['success_message']); // Clear the session message 
        ?>
    <?php endif; ?>

    // Check for error message
    <?php if (isset($_SESSION['error_message'])): ?>
        alert("<?php echo $_SESSION['error_message']; ?>");
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    function toggleReportInputs() {
        // Get the selected report type
        var reportType = document.getElementById('report_type').value;

        // Hide all report input fields
        document.getElementById('spot_report_input').style.display = 'none';
        document.getElementById('progress_report_input').style.display = 'none';
        document.getElementById('final_report_input').style.display = 'none';

        // Show the corresponding input field based on the selected report type
        if (reportType === 'spot') {
            document.getElementById('spot_report_input').style.display = 'block';
        } else if (reportType === 'progress') {
            document.getElementById('progress_report_input').style.display = 'block';
        } else if (reportType === 'final') {
            document.getElementById('final_report_input').style.display = 'block';
        }
    }

    function previewReport(event, previewContainerId) {
        const previewContainer = document.getElementById(previewContainerId);
        previewContainer.innerHTML = ''; // Clear previous preview

        const file = event.target.files[0];
        if (!file) return;

        const fileUrl = URL.createObjectURL(file);
        const fileExtension = file.name.split('.').pop().toLowerCase();

        if (fileExtension === 'pdf') {
            previewContainer.innerHTML = `
            <h4>Preview:</h4>
            <iframe src="${fileUrl}" width="100%" height="400px"></iframe>
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

    function showTab(type) {
        document.getElementById('spot_report_input').style.display = (type === 'spot') ? 'block' : 'none';
        document.getElementById('progress_report_input').style.display = (type === 'progress') ? 'block' : 'none';
        document.getElementById('final_report_input').style.display = (type === 'final') ? 'block' : 'none';
        // Optionally, highlight the active tab
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelector('.tab-btn[onclick="showTab(\'' + type + '\')"]').classList.add('active');
    }

    function updateStepper(step) {
        for (let i = 1; i <= 4; i++) {
            const stepElem = document.getElementById('stepper-' + i);
            stepElem.classList.remove('active', 'completed');
            if (i < step) stepElem.classList.add('completed');
            else if (i === step) stepElem.classList.add('active');
        }
        // Update lines
        document.querySelectorAll('.stepper .line').forEach((line, idx) => {
            if (idx < step - 1) line.classList.add('active');
            else line.classList.remove('active');
        });
    }

    // ...existing code...
    function prevStep(step) {
        document.querySelectorAll('.form-step').forEach(div => div.style.display = 'none');
        document.getElementById('step-' + step).style.display = 'block';
        updateStepper(step);
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateStepper(1);
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
</script>