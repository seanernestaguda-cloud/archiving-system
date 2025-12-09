<?php
// session_start();

// // Check if the user is logged in
// if (!isset($_SESSION['username'])) {
//     header("Location: adminlogin.php");
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

// Ensure department is set in session
if (!isset($_SESSION['department']) || empty($_SESSION['department'])) {
    $sql_dept = "SELECT department FROM users WHERE username = ? LIMIT 1";
    $stmt_dept = $conn->prepare($sql_dept);
    $stmt_dept->bind_param("s", $username);
    $stmt_dept->execute();
    $result_dept = $stmt_dept->get_result();
    if ($result_dept && $row_dept = $result_dept->fetch_assoc()) {
        $_SESSION['department'] = $row_dept['department'];
    }
    $stmt_dept->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $permit_name = $_POST['permit_name'];
    $inspection_establishment = $_POST['inspection_establishment'];
    $owner = $_POST['owner'];
    $inspection_address = $_POST['inspection_address'];
    $inspection_date = $_POST['inspection_date'];
    $establishment_type = $_POST['establishment_type'];
    $inspection_purpose = $_POST['inspection_purpose'];
    $fire_alarms = isset($_POST['fire_alarms']) ? $_POST['fire_alarms'] : 0;
    $fire_extinguishers = isset($_POST['fire_extinguishers']) ? $_POST['fire_extinguishers'] : 0;
    $emergency_exits = isset($_POST['emergency_exits']) ? $_POST['emergency_exits'] : 0;
    $sprinkler_systems = isset($_POST['sprinkler_systems']) ? $_POST['sprinkler_systems'] : 0;
    $fire_drills = isset($_POST['fire_drills']) ? $_POST['fire_drills'] : 0;
    $exit_signs = isset($_POST['exit_signs']) ? $_POST['exit_signs'] : 0;
    $electrical_wiring = isset($_POST['electrical_wiring']) ? $_POST['electrical_wiring'] : 0;
    $emergency_evacuations = isset($_POST['emergency_evacuations']) ? $_POST['emergency_evacuations'] : 0;
    $inspected_by = $_POST['inspected_by'];

    // Add missing fields from the form
    $contact_person = $_POST['contact_person'];
    $contact_number = $_POST['contact_number'];
    $number_of_occupants = $_POST['number_of_occupants'];
    $nature_of_business = $_POST['nature_of_business'];
    $number_of_floors = $_POST['number_of_floors'];
    $floor_area = $_POST['floor_area'];
    $classification_of_hazards = $_POST['classification_of_hazards'];
    $building_construction = $_POST['building_construction'];
    $possible_problems = $_POST['possible_problems'];
    $hazardous_materials = $_POST['hazardous_materials'];

    $upload_dir = '../uploads/'; // Make sure this directory exists and is writable
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $attachments = [
        'application_form',
        'proof_of_ownership',
        'fire_safety_inspection_checklist',
        'building_plans',
        'fire_safety_inspection_certificate',
        'occupancy_permit',
        'business_permit'
    ];
    $file_paths = [];
    foreach ($attachments as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] == UPLOAD_ERR_OK) {
            $filename = date('YmdHis') . '_' . basename($_FILES[$field]['name']);
            $target = $upload_dir . $filename;
            if (move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
                $file_paths[$field] = $target;
            } else {
                $file_paths[$field] = null;
            }
        } else {
            $file_paths[$field] = null;
        }
    }

    // Prepare an SQL statement to insert the data
    $stmt = $conn->prepare("INSERT INTO fire_safety_inspection_certificate 
    (permit_name, inspection_establishment, owner, inspection_address, inspection_date, establishment_type, inspection_purpose, 
    fire_alarms, fire_extinguishers, emergency_exits, sprinkler_systems, fire_drills, exit_signs, electrical_wiring, emergency_evacuations, inspected_by,
    contact_person, contact_number, number_of_occupants, nature_of_business, number_of_floors, floor_area, classification_of_hazards, building_construction, possible_problems, hazardous_materials,
    application_form, proof_of_ownership, fire_safety_inspection_checklist, building_plans, fire_safety_inspection_certificate, occupancy_permit, business_permit, uploader, department)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"); // 35 placeholders

    // Insert default value for department if missing
    $department = isset($_SESSION['department']) && !empty($_SESSION['department']) ? $_SESSION['department'] : 'N/A';
    $stmt->bind_param(
        "sssssssssssssssssssssssssssssssssss", // 35 "s"
        $permit_name,
        $inspection_establishment,
        $owner,
        $inspection_address,
        $inspection_date,
        $establishment_type,
        $inspection_purpose,
        $fire_alarms,
        $fire_extinguishers,
        $emergency_exits,
        $sprinkler_systems,
        $fire_drills,
        $exit_signs,
        $electrical_wiring,
        $emergency_evacuations,
        $inspected_by,
        $contact_person,
        $contact_number,
        $number_of_occupants,
        $nature_of_business,
        $number_of_floors,
        $floor_area,
        $classification_of_hazards,
        $building_construction,
        $possible_problems,
        $hazardous_materials,
        $file_paths['application_form'],
        $file_paths['proof_of_ownership'],
        $file_paths['fire_safety_inspection_checklist'],
        $file_paths['building_plans'],
        $file_paths['fire_safety_inspection_certificate'],
        $file_paths['occupancy_permit'],
        $file_paths['business_permit'],
        $username,
        $department
    );

    // Execute the statement
    if (mysqli_stmt_execute($stmt)) {
        // Log activity
        $report_id = $conn->insert_id;
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (username, action, report_id, details) VALUES (?, 'create', ?, ?)");
        $details = "Created Fire Safety Inspection Report: " . $permit_name;
        $log_stmt->bind_param('sis', $username, $report_id, $details);
        $log_stmt->execute();
        $log_stmt->close();

        $_SESSION['success_message'] = "Report created successfully!";
        // No immediate redirection
    } else {
        $_SESSION['error_message'] = "There was an error creating the report. Please try again.";
    }
    // Close the prepared statement and connection
    $stmt->close();
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Fire Safety Inspection Certificate</title>
    <link rel="stylesheet" href="reportstyle.css">
    <link rel="stylesheet" href="permitstyle.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="icon" type="image/png" href="../REPORT.png">
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
            opacity: 1;
        }

        .required-text.filled {
            display: none;
        }

        /* Error highlight for required fields */
        .input-error {
            border-color: #bd000a !important;
            background: #fff0f0;
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
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        /* Input fields */
        input[type="text"],
        input[type="date"],
        input[type="number"],
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

        .form-step {
            display: none;
        }

        .form-step:first-child {
            display: block;
        }

        /* Stepper styles (reuse from previous answer) */
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

        .step.completed .circle>* {
            display: none;
        }

        .line.active {
            background: #003D73;
        }

        .card {
            max-width: 950px;
            /* change to desired max width (e.g. 700px for smaller) */
            width: 90%;
            /* responsive width */
            margin: 80px auto 40px;
            /* center and control vertical spacing */
            padding: 18px;
            /* inner spacing */
            box-sizing: border-box;
            border-radius: 8px;
            /* match existing look */
            background: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
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
                <h2>Fire Safety Inspection Report<h2>
            </div>
            <!-- <?php
                    // Use session variable for success and error messages
                    if (isset($_SESSION['success_message'])) {
                        $success_message = $_SESSION['success_message'];
                        echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>';
                    }
                    if (isset($_SESSION['error_message'])) {
                        $error_message = $_SESSION['error_message'];
                        echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
                    }
                    ?> -->

            <div class="stepper-container">
                <div class="stepper">
                    <div class="step" id="stepper-1">
                        <div class="circle">1</div>
                        <div class="label">Fill up the form</div>
                    </div>
                    <div class="line"></div>
                    <div class="step" id="stepper-2">
                        <div class="circle">2</div>
                        <div class="label">Upload Documents</div>
                    </div>
                    <div class="line"></div>
                    <div class="step" id="stepper-3">
                        <div class="circle">3</div>
                        <div class="label">Confirm and Submit</div>
                    </div>
                </div>
            </div>
            <form method="POST" action="create_fire_safety_inspection_certificate.php" enctype="multipart/form-data">
                <div class="form-step" id="step-1">
                    <fieldset>
                        <legend> Inspection Details </legend>
                        <div class="form-group-container">
                            <div class="form-group" style="width: 45%; display: inline-block;">
                                <label for="permit_name">Title: <span class="required">*</span><span class="required-text"> required</span></label>
                                <input type="text" id="permit_name" name="permit_name" class="form-control" placeholder="Report Name" required>
                            </div>


                            <div class="form-group" style="width: 45%; display: inline-block;">
                                <label for="owner">Owner of the Establishment: <span class="required">*</span><span class="required-text"> required</span></label>
                                <input type="text" id="owner" name="owner" class="form-control" placeholder="Name of the Owner" required>
                            </div>

                            <div class="form-group" style="width: 45%; display: inline-block;">
                                <label for="contact_person">Contact Person: <span class="required">*</span><span class="required-text"> required</span></label>
                                <input type="text" id="contact_person" name="contact_person" class="form-control" placeholder="Name of the Contact Person" required>
                            </div>

                            <div class="form-group" style="width: 45%; display: inline-block;">
                                <label for="contact_number">Contact Number: <span class="required">*</span><span class="required-text"> required</span></label>
                                <input type="number" id="contact_number" name="contact_number" class="form-control" placeholder="Contact Number" required>
                            </div>
                            <hr class="section-separator full-bleed">
                            <h4 style="text-align: center;"> Establishment Details </h4>
                            <hr class="section-separator full-bleed">
                            <div class="form-group" style="width: 45%; display: inline-block;">
                                <label for="inspection_establishment">Establishment Name: <span class="required">*</span><span class="required-text"> required</span></label>
                                <input type="text" id="inspection_establishment" name="inspection_establishment" class="form-control" placeholder="Establishment" required>
                            </div>

                            <div class="form-group" style="width:45%; display: inline-block;">
                                <label for="establishment_type">Establishment Type: <span class="required">*</span><span class="required-text"> required</span></label>
                                <select id="establishment_type" name="establishment_type" class="form-control" required>
                                    <option value="" disabled selected>Select Establishment Type</option>
                                    <option value="residential">Residential</option>
                                    <option value="commercial">Commercial</option>
                                    <option value="industrial">Industrial</option>
                                    <option value="educational">Educational</option>
                                    <option value="healthcare">Healthcare Faclity</option>
                                    <option value="entertainment">Entertainment Venue</option>
                                    <option value="government">Government Office</option>
                                </select>
                            </div>

                            <div class="form-group-container"></div>

                            <div class="form-group" style="width: 45%; display: inline-block;">
                                <label for="inspection_address">Address of the Establishment: <span class="required">*</span><span class="required-text"> required</span></label>
                                <input type="text" id="inspection_address" name="inspection_address" class="form-control" placeholder="Address of the Establishment" required>
                            </div>



                            <div class="form-group" style="width: 45%; display: inline-block;">
                                <label for="number_of_occupants">Number of Occupants: <span class="required">*</span><span class="required-text"> required</span></label>
                                <input type="number" id="number_of_occupants" name="number_of_occupants" class="form-control" placeholder="Number of Occupants" required>
                            </div>

                            <div class="form-group" style="width: 45%; display: inline-block;">
                                <label for="nature_of_business">Nature of Business: <span class="required">*</span><span class="required-text"> required</span></label>
                                <input type="text" id="nature_of_business" name="nature_of_business" class="form-control" placeholder="Nature of Business" required>
                            </div>

                            <div class="form-group" style="width: 45%; display: inline-block;">
                                <label for="number_of_floors">Number of Floors: <span class="required">*</span><span class="required-text"> required</span></label>
                                <input type="number" id="number_of_floors" name="number_of_floors" class="form-control" placeholder="Number of Floors" required>
                            </div>

                            <div class="form-group" style="width: 45%; display: inline-block;">
                                <label for="floor_area">Floor Area: <span class="required">*</span><span class="required-text"> required</span></label>
                                <input type="text" id="floor_area" name="floor_area" class="form-control" placeholder="Floor Area" required>
                            </div>


                            <div class="form-group" style="width: 45%; display: inline-block;">
                                <label for="building_construction">Building Construction: <span class="required">*</span><span class="required-text"> required</span></label>
                                <input type="text" id="building_construction" name="building_construction" class="form-control" placeholder="Building Construction" required>
                            </div>
                            <hr class="section-separator full-bleed">
                            <h4 style="text-align: center;"> Inspection Details </h4>
                            <hr class="section-separator full-bleed">

                            <div class="form-group-container">
                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="inspection_purpose">Purpose of Inspection: <span class="required">*</span><span class="required-text"> required</span></label>
                                    <select id="inspection_purpose" name="inspection_purpose" class="form-control" required>
                                        <option value="" disabled selected>Select Purpose</option>
                                        <option value="routine">Routine</option>
                                        <option value="compliance">Compliance</option>
                                        <option value="complaint">Complaint</option>
                                    </select>
                                </div>
                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="classification_of_hazards">Classification of Hazards: <span class="required">*</span><span class="required-text"> required</span></label>
                                    <select id="classification_of_hazards" name="classification_of_hazards" class="form-control" required>
                                        <option value="" disabled selected>Select Classification</option>
                                        <option value="Class_A">Class A: These fires involve ordinary combustibles like wood, paper, cloth, and some plastics.</option>
                                        <option value="Class_B">Class B: These fires involve flammable liquids and gases, such as gasoline, oil, alcohol, and propane.</option>
                                        <option value="Class_C">Class C: These fires involve energized electrical equipment.</option>
                                        <option value="Class_D">Class D: These fires involve combustible metals.</option>
                                        <option value="Class_K">Class K: These fires involve cooking oils and fats.</option>
                                    </select>
                                </div>
                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="inspection_date">Date of Inspection: <span class="required">*</span><span class="required-text"> required</span></label>
                                    <input type="date" id="inspection_date" name="inspection_date" class="form-control" placeholder="Date" required>
                                </div>
                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="inspected_by">Inspected By: <span class="required">*</span><span class="required-text"> required</span></label>
                                    <input type="text" id="inspected_by" name="inspected_by" class="form-control" placeholder="Name of Inspector" required>
                                </div>
                            </div>
                            <div class="form-group" style="width: 45%; display: inline-block;">
                                <label for="possible_problems">Possible Problems to be encountered during fire:<span class="required">*</span><span class="required-text"> required</span></label>
                                <textarea id="possible_problems" name="possible_problems" rows="10" cols="30" placeholder="Possible Problems During Fire" onfocus="addFirstNumber()" oninput="autoNumber()" required></textarea><br><br>
                            </div>
                            <div class="form-group" style="width: 45%; display: inline-block;">
                                <label for="hazardous_materials">Hazardous/Flammable Materials:<span class="required">*</span><span class="required-text"> required</span></label>
                                <textarea id="hazardous_materials" name="hazardous_materials" rows="10" cols="30" placeholder="Hazardous Materials" onfocus="addFirstNumber()" oninput="autoNumber()" required></textarea><br><br>
                            </div>
                        </div>
                    </fieldset>
                    <div class="form-actions">
                        <a href="my_fire_safety_reports.php" class="btn btn-cancel">Cancel</a>
                        <button type="button" class="btn btn-primary" onclick="nextStep(2)">Next</button>
                    </div>
                </div>

                <!-- STEP 3: Required Attachments -->
                <div class="form-step" id="step-2" style="display:none;">
                    <fieldset>
                        <legend>Required Attachments</legend>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Required Attachments</label>
                            <hr class="section-separator full-bleed">
                            <div class="tab-container">
                                <button type="button" class="tab-btn" onclick="showTab('application_form')">Application Form (BFP)</button>
                                <button type="button" class="tab-btn" onclick="showTab('proof_of_ownership')">Proof of Ownership</button>
                                <button type="button" class="tab-btn" onclick="showTab('fire_safety_inspection_checklist')">Fire Safety Inspection Checklist</button>
                                <button type="button" class="tab-btn" onclick="showTab('building_plans')">Building Plans</button>
                                <button type="button" class="tab-btn" onclick="showTab('fire_safety_inspection_certificate')">Fire Safety Inspection Certificate (FSIC)</button>
                                <button type="button" class="tab-btn" onclick="showTab('occupancy_permit')">Occupancy Permit</button>
                                <button type="button" class="tab-btn" onclick="showTab('business_permit')">Business Permit</button>
                            </div>
                        </div>

                        <!-- Application Form Upload -->
                        <div id="application_form_input" class="form-group tab-content" style="display:none;">
                            <label for="application_form">Upload Application Form (BFP):</label>
                            <div class="custom-file-upload" id="customFileUploadApplication">
                                <div class="drop-area" id="dropAreaApplication">
                                    <span class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                                    <span>Drop file here, or click below!</span>
                                    <input type="file" id="application_form" name="application_form" accept=".pdf,.doc,.docx,.txt,.rtf" style="display:none;" onchange="previewReport(event, 'file-preview-application')">
                                </div>
                                <button type="button" class="upload-btn" onclick="document.getElementById('application_form').click();">Upload</button>
                                <div class="max-size-info">You can upload files up to a maximum of 2 GB.</div>
                                <div id="file-preview-application"></div>
                            </div>
                        </div>

                        <!-- Proof of Ownership Upload -->
                        <div id="proof_of_ownership_input" class="form-group tab-content" style="display:none;">
                            <label for="proof_of_ownership">Upload Proof of Ownership:</label>
                            <div class="custom-file-upload" id="customFileUploadOwnership">
                                <div class="drop-area" id="dropAreaOwnership">
                                    <span class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                                    <span>Drop file here, or click below!</span>
                                    <input type="file" id="proof_of_ownership" name="proof_of_ownership" accept=".pdf,.doc,.docx,.txt,.rtf" style="display:none;" onchange="previewReport(event, 'file-preview-ownership')">
                                </div>
                                <button type="button" class="upload-btn" onclick="document.getElementById('proof_of_ownership').click();">Upload</button>
                                <div class="max-size-info">You can upload files up to a maximum of 2 GB.</div>
                                <div id="file-preview-ownership"></div>
                            </div>
                        </div>


                        <!-- Fire Safety Equipment Upload -->
                        <div id="fire_safety_inspection_checklist_input" class="form-group tab-content" style="display:none;">
                            <label for="fire_safety_inspection_checklist">Upload Fire Safety Inspection Checklist:</label>
                            <div class="custom-file-upload" id="customFileUploadChecklist">
                                <div class="drop-area" id="dropAreaChecklist">
                                    <span class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                                    <span>Drop file here, or click below!</span>
                                    <input type="file" id="fire_safety_inspection_checklist" name="fire_safety_inspection_checklist" accept=".pdf,.doc,.docx,.txt,.rtf" style="display:none;" onchange="previewReport(event, 'file-preview-checklist')">
                                </div>
                                <button type="button" class="upload-btn" onclick="document.getElementById('fire_safety_inspection_checklist').click();">Upload</button>
                                <div class="max-size-info">You can upload files up to a maximum of 2 GB.</div>
                                <div id="file-preview-checklist"></div>
                            </div>
                        </div>

                        <!-- Mayor's Permit Upload removed -->

                        <!-- Building Plans Upload -->
                        <div id="building_plans_input" class="form-group tab-content" style="display:none;">
                            <label for="building_plans">Upload Building Plans:</label>
                            <div class="custom-file-upload" id="customFileUploadBuildingPlans">
                                <div class="drop-area" id="dropAreaBuildingPlans">
                                    <span class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                                    <span>Drop file here, or click below!</span>
                                    <input type="file" id="building_plans" name="building_plans" accept=".pdf,.doc,.docx,.txt,.rtf" style="display:none;" onchange="previewReport(event, 'file-preview-building-plans')">
                                </div>
                                <button type="button" class="upload-btn" onclick="document.getElementById('building_plans').click();">Upload</button>
                                <div class="max-size-info">You can upload files up to a maximum of 2 GB.</div>
                                <div id="file-preview-building-plans"></div>
                            </div>
                        </div>

                        <!-- Fire Safety Inspection Certificate Upload -->
                        <div id="fire_safety_inspection_certificate_input" class="form-group tab-content" style="display:none;">
                            <label for="fire_safety_inspection_certificate">Upload Fire Safety Inspection Certificate:</label>
                            <div class="custom-file-upload" id="customFileUploadCertificate">
                                <div class="drop-area" id="dropAreaCertificate">
                                    <span class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                                    <span>Drop file here, or click below!</span>
                                    <input type="file" id="fire_safety_inspection_certificate" name="fire_safety_inspection_certificate" accept=".pdf,.doc,.docx,.txt,.rtf" style="display:none;" onchange="previewReport(event, 'file-preview-certificate')">
                                </div>
                                <button type="button" class="upload-btn" onclick="document.getElementById('fire_safety_inspection_certificate').click();">Upload</button>
                                <div class="max-size-info">You can upload files up to a maximum of 2 GB.</div>
                                <div id="file-preview-certificate"></div>
                            </div>
                        </div>

                        <!-- Occupancy Permit Upload -->
                        <div id="occupancy_permit_input" class="form-group tab-content" style="display:none;">
                            <label for="occupancy_permit">Upload Occupancy Permit:</label>
                            <div class="custom-file-upload" id="customFileUploadOccupancy">
                                <div class="drop-area" id="dropAreaOccupancy">
                                    <span class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                                    <span>Drop file here, or click below!</span>
                                    <input type="file" id="occupancy_permit" name="occupancy_permit" accept=".pdf,.doc,.docx,.txt,.rtf" style="display:none;" onchange="previewReport(event, 'file-preview-occupancy')">
                                </div>
                                <button type="button" class="upload-btn" onclick="document.getElementById('occupancy_permit').click();">Upload</button>
                                <div class="max-size-info">You can upload files up to a maximum of 2 GB.</div>
                                <div id="file-preview-occupancy"></div>
                            </div>
                        </div>

                        <!-- Business Permit Upload -->
                        <div id="business_permit_input" class="form-group tab-content" style="display:none;">
                            <label for="business_permit">Upload Business Permit:</label>
                            <div class="custom-file-upload" id="customFileUploadBusiness">
                                <div class="drop-area" id="dropAreaBusiness">
                                    <span class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                                    <span>Drop file here, or click below!</span>
                                    <input type="file" id="business_permit" name="business_permit" accept=".pdf,.doc,.docx,.txt,.rtf" style="display:none;" onchange="previewReport(event, 'file-preview-business')">
                                </div>
                                <button type="button" class="upload-btn" onclick="document.getElementById('business_permit').click();">Upload</button>
                                <div class="max-size-info">You can upload files up to a maximum of 2 GB.</div>
                                <div id="file-preview-business"></div>
                            </div>
                        </div>
                    </fieldset>
                    <div class="form-actions">
                        <button type="button" class="btn btn-cancel" onclick="prevStep(1)">Previous</button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(3)">Next</button>
                    </div>
                </div>

                <!-- STEP 4: Confirm and Submit -->
                <div class="form-step" id="step-3" style="display:none;">
                    <fieldset>
                        <legend>Confirm and Submit</legend>
                        <p style="text-align:center;">Please review all information before submitting your application.</p>
                        <div id="summary" style="margin: 20px 0; padding: 20px; background: #f7f7f7; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
                            <!-- Summary will be injected here -->
                        </div>
                    </fieldset>
                    <div class="form-actions">
                        <button type="button" class="btn btn-cancel" onclick="prevStep(2)">Previous</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
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

    </div>

    <!-- Add this JS before </body> -->
    <script>
        function updateStepper(step) {
            for (let i = 1; i <= 3; i++) {
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
        // Use browser-native required field validation for step 1
        function nextStep(step) {
            if (step === 2) {
                var step1 = document.getElementById('step-1');
                var requiredFields = step1.querySelectorAll('[required]');
                for (var i = 0; i < requiredFields.length; i++) {
                    if (!requiredFields[i].checkValidity()) {
                        requiredFields[i].reportValidity();
                        requiredFields[i].focus();
                        return;
                    }
                }
            }
            document.querySelectorAll('.form-step').forEach(div => div.style.display = 'none');
            document.getElementById('step-' + step).style.display = 'block';
            updateStepper(step);
            if (step === 3) {
                showSummary();
            }
        }

        // Show summary of all entered details in step 3
        function showSummary() {
            var summaryDiv = document.getElementById('summary');
            var fields = [{
                    label: 'Title',
                    id: 'permit_name'
                },
                {
                    label: 'Owner of the Establishment',
                    id: 'owner'
                },
                {
                    label: 'Contact Person',
                    id: 'contact_person'
                },
                {
                    label: 'Contact Number',
                    id: 'contact_number'
                },
                {
                    label: 'Establishment Name',
                    id: 'inspection_establishment'
                },
                {
                    label: 'Establishment Type',
                    id: 'establishment_type'
                },
                {
                    label: 'Address of the Establishment',
                    id: 'inspection_address'
                },
                {
                    label: 'Number of Occupants',
                    id: 'number_of_occupants'
                },
                {
                    label: 'Nature of Business',
                    id: 'nature_of_business'
                },
                {
                    label: 'Number of Floors',
                    id: 'number_of_floors'
                },
                {
                    label: 'Floor Area',
                    id: 'floor_area'
                },
                {
                    label: 'Building Construction',
                    id: 'building_construction'
                },
                {
                    label: 'Inspection Date',
                    id: 'inspection_date'
                },
                {
                    label: 'Inspection Purpose',
                    id: 'inspection_purpose'
                },
                {
                    label: 'Inspected By',
                    id: 'inspected_by'
                },
                {
                    label: 'Classification of Hazards',
                    id: 'classification_of_hazards'
                },
                {
                    label: 'Date of Inspection',
                    id: 'inspection_date'
                },
                {
                    label: 'Possible Problems',
                    id: 'possible_problems'
                },
                {
                    label: 'Hazardous/Flammable Materials',
                    id: 'hazardous_materials'
                }
            ];
            var html = '<h3 style="text-align:center;">Summary of Entered Details</h3><table style="width:100%;border-collapse:collapse;">';
            fields.forEach(function(field) {
                var input = document.getElementById(field.id);
                var value = '';
                if (input) {
                    if (input.tagName === 'SELECT') {
                        value = input.options[input.selectedIndex] ? input.options[input.selectedIndex].text : '';
                    } else {
                        value = input.value;
                    }
                }
                html += '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;width:35%;color:#003D73;">' + field.label + '</td>' +
                    '<td style="padding:8px;border-bottom:1px solid #eee;">' + (value ? value : '<span style="color:#bd000a;">Not provided</span>') + '</td></tr>';
            });

            // Attachments
            var attachments = [{
                    label: 'Application Form (BFP)',
                    id: 'application_form'
                },
                {
                    label: 'Proof of Ownership',
                    id: 'proof_of_ownership'
                },
                {
                    label: 'Fire Safety Inspection Checklist',
                    id: 'fire_safety_inspection_checklist'
                },
                {
                    label: 'Building Plans',
                    id: 'building_plans'
                },
                {
                    label: 'Fire Safety Inspection Certificate (FSIC)',
                    id: 'fire_safety_inspection_certificate'
                },
                {
                    label: 'Occupancy Permit',
                    id: 'occupancy_permit'
                },
                {
                    label: 'Business Permit',
                    id: 'business_permit'
                }
            ];
            html += '<tr><td colspan="2" style="text-align:center;padding:12px 8px;font-weight:bold;color:#003D73;background:#f0f8ff;">Attachments</td></tr>';
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

        function prevStep(step) {
            document.querySelectorAll('.form-step').forEach(div => div.style.display = 'none');
            document.getElementById('step-' + step).style.display = 'block';
            updateStepper(step);
        }
        document.addEventListener('DOMContentLoaded', function() {
            updateStepper(1);
            // Hide 'required' text when field is filled
            function updateRequiredTextVisibility() {
                document.querySelectorAll('#step-1 [required]').forEach(function(field) {
                    var reqText = field.parentElement.querySelector('.required-text');
                    if (reqText) {
                        if (field.value.trim()) {
                            reqText.style.opacity = '0';
                        } else {
                            reqText.style.opacity = '1';
                        }
                    }
                });
            }
            updateRequiredTextVisibility();
            document.querySelectorAll('#step-1 [required]').forEach(function(field) {
                field.addEventListener('input', updateRequiredTextVisibility);
                field.addEventListener('change', updateRequiredTextVisibility);
            });
        });

        function showTab(type) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(div => div.style.display = 'none');
            // Show the selected tab
            document.getElementById(type + '_input').style.display = 'block';
            // Highlight active tab
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector('.tab-btn[onclick="showTab(\'' + type + '\')"]').classList.add('active');
        }

        // Drag & Drop for each substantiating document
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

        // Setup drop areas for each document
        setupDropArea('dropAreaApplication', 'application_form', 'file-preview-application');
        setupDropArea('dropAreaOwnership', 'proof_of_ownership', 'file-preview-ownership');
        setupDropArea('dropAreaChecklist', 'fire_safety_inspection_checklist', 'file-preview-checklist');
        // Mayor's Permit drop area removed
        setupDropArea('dropAreaBuildingPlans', 'building_plans', 'file-preview-building-plans');
        setupDropArea('dropAreaCertificate', 'fire_safety_inspection_certificate', 'file-preview-certificate');
        setupDropArea('dropAreaOccupancy', 'occupancy_permit', 'file-preview-occupancy');
        setupDropArea('dropAreaBusiness', 'business_permit', 'file-preview-business');

        // Preview function for substantiating documents
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
        let lineCount = 1; // Keeps track of the current line number

        function addFirstNumber() {
            var textarea = document.getElementById('victims');
            if (textarea.value.trim() === '') {
                textarea.value = '1. ';
            }
            lineCount = 1; // Reset line count when focus is gained
        }

        function autoNumber() {
            var textarea = document.getElementById('victims');
            var lines = textarea.value.split('\n');

            // Iterate through each line to add numbers
            for (let i = 0; i < lines.length; i++) {
                // If a line is empty, skip adding a number
                if (lines[i].trim() !== '') {
                    lines[i] = (i + 1) + '. ' + lines[i].replace(/^\d+\.\s*/, ''); // Add number and clean existing numbers
                }
            }

            // Join the lines back together and update the textarea
            textarea.value = lines.join('\n');
        }

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
        function showSuccessModal(message) {
            document.getElementById('successMessage').textContent = message;
            document.getElementById('successModal').style.display = "block";

            // Redirect after 2 seconds
            setTimeout(() => {
                window.location.href = "my_fire_safety_reports.php"; // Change URL as needed
            }, 2000);
        }

        // Function to close the modal
        function closeModal() {
            document.getElementById('successModal').style.display = "none";
        }


        // Trigger the modal if a success message is set
        <?php if (isset($_SESSION['success_message'])): ?>
            showSuccessModal("<?php echo addslashes($_SESSION['success_message']); ?>");
            <?php unset($_SESSION['success_message']); // Clear the session message 
            ?>
        <?php endif; ?>

        // Check for error message
        <?php if (isset($_SESSION['error_message'])): ?>
            alert("<?php echo addslashes($_SESSION['error_message']); ?>");
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        function displayFileName(input) {
            const spanId = "file-name-" + input.id;
            const fileNameSpan = document.getElementById(spanId);
            if (!fileNameSpan) return;
            if (input.files.length > 1) {
                fileNameSpan.textContent = `${input.files.length} files selected`;
            } else if (input.files.length === 1) {
                fileNameSpan.textContent = input.files[0].name;
            } else {
                fileNameSpan.textContent = "No file chosen";
            }
        }

        // Preview function for attachments
        function previewAttachment(input) {
            const previewId = "preview-" + input.id;
            const previewDiv = document.getElementById(previewId);
            previewDiv.innerHTML = ""; // Clear previous preview

            if (input.files && input.files[0]) {
                const file = input.files[0];
                const fileType = file.type;

                if (fileType.startsWith("image/")) {
                    // Image preview
                    const img = document.createElement("img");
                    img.src = URL.createObjectURL(file);
                    img.src = e.target.result;
                    img.style.maxWidth = '200px';
                    img.style.maxHeight = '200px';
                    img.style.borderRadius = '8px';
                    img.style.boxShadow = '0 2px 6px rgba(0,0,0,0.15)';
                    img.onload = function() {
                        URL.revokeObjectURL(img.src);
                    };
                    previewDiv.appendChild(img);
                } else if (fileType === "application/pdf") {
                    // PDF preview
                    const pdf = document.createElement("embed");
                    pdf.src = URL.createObjectURL(file);
                    pdf.type = "application/pdf";
                    pdf.width = "100%";
                    pdf.height = "400px";
                    previewDiv.appendChild(pdf);
                } else {
                    // Generic file preview
                    const info = document.createElement("span");
                    info.textContent = "Selected file: " + file.name;
                    previewDiv.appendChild(info);
                }
            }
        }

        function toggleAttachmentInputs() {
            var type = document.getElementById('attachment_type').value;
            var fields = [
                "application_form",
                "proof_of_ownership",
                "fire_safety_inspection_checklist",
                "building_plans",
                "fire_safety_inspection_certificate",
                "occupancy_permit",
                "business_permit"
            ];
            fields.forEach(function(field) {
                document.getElementById(field + "_input").style.display = "none";
            });
            if (type) {
                document.getElementById(type + "_input").style.display = "block";
            }
        }

        function displayCustomFileName(input, field) {
            const fileNameDiv = document.getElementById('customFileName_' + field);
            if (input.files.length > 0) {
                fileNameDiv.textContent = input.files[0].name;
            } else {
                fileNameDiv.textContent = "No file chosen";
            }
        }

        function handleDrop(event, field) {
            event.preventDefault();
            event.currentTarget.classList.remove('dragover');
            const input = document.getElementById(field);
            input.files = event.dataTransfer.files;
            displayCustomFileName(input, field);
            previewAttachment(input);
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
        // Close modal when clicking outside of it
    </script>
</body>

</html>
<script src="../js/archivescript.js"></script>