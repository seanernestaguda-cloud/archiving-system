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
// Get user info and role
$username = $_SESSION['username'];
$sql_user = "SELECT avatar, user_type FROM users WHERE username = ? LIMIT 1";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("s", $username);
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
// Fetch the record to be edited based on ID from URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Prepare SQL statement to fetch the data
    $stmt = $conn->prepare("SELECT * FROM fire_safety_inspection_certificate WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    // If the record exists, fetch the data
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        echo "Record not found!";
        exit();
    }
} else {
    echo "No ID provided!";
    exit();
}

// Check if the form is submitted to update the data
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

    // File upload handling
    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    function handleFileUpload($field, $existingPath, $uploadDir)
    {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] == UPLOAD_ERR_OK) {
            $fileName = basename($_FILES[$field]['name']);
            $filePath = $uploadDir . uniqid() . '_' . $fileName;
            move_uploaded_file($_FILES[$field]['tmp_name'], $filePath);
            return $filePath;
        }
        return $existingPath;
    }

    // Get existing file paths from $row
    $application_form = handleFileUpload('application_form_file', $row['application_form'], $uploadDir);
    $proof_of_ownership = handleFileUpload('proof_of_ownership_file', $row['proof_of_ownership'], $uploadDir);
    $building_plans = handleFileUpload('building_plans_file', $row['building_plans'], $uploadDir);
    $fire_safety_inspection_checklist = handleFileUpload('fire_safety_inspection_checklist_file', $row['fire_safety_inspection_checklist'], $uploadDir);
    $fire_safety_inspection_certificate = handleFileUpload('fire_safety_inspection_certificate_file', $row['fire_safety_inspection_certificate'], $uploadDir);
    $occupancy_permit = handleFileUpload('occupancy_permit_file', $row['occupancy_permit'], $uploadDir);
    $business_permit = handleFileUpload('business_permit_file', $row['business_permit'], $uploadDir);

    // Prepare SQL statement to update the data, including file columns
    $stmt = $conn->prepare("UPDATE fire_safety_inspection_certificate 
        SET permit_name = ?, inspection_establishment = ?, owner = ?, inspection_address = ?, 
        inspection_date = ?, establishment_type = ?, inspection_purpose = ?, fire_alarms = ?, 
        fire_extinguishers = ?, emergency_exits = ?, sprinkler_systems = ?, fire_drills = ?, exit_signs = ?, 
        electrical_wiring = ?, emergency_evacuations = ?, inspected_by = ?, contact_person = ?, contact_number = ?, 
        number_of_occupants = ?, nature_of_business = ?, number_of_floors = ?, floor_area = ?, classification_of_hazards = ?, 
        building_construction = ?, possible_problems = ?, hazardous_materials = ?, application_form = ?, proof_of_ownership = ?,  building_plans = ?,
         fire_safety_inspection_checklist = ?, fire_safety_inspection_certificate = ?, occupancy_permit = ?, business_permit = ?
        WHERE id = ?");

    $stmt->bind_param(
        "sssssssssssssssssssssssssssssssssi", // 26 types: 25 's' + 1 'i'
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
        $application_form,
        $proof_of_ownership,
        $building_plans,
        $fire_safety_inspection_checklist,
        $fire_safety_inspection_certificate,
        $occupancy_permit,
        $business_permit,
        $id
    );

    // Execute the statement
    if ($stmt->execute()) {
        // Log activity
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (username, action, report_id, details) VALUES (?, 'update', ?, ?)");
        $details = "Updated Fire Safety Inspection Report: " . $permit_name;
        $log_stmt->bind_param('sis', $username, $id, $details);
        $log_stmt->execute();
        $log_stmt->close();

        $_SESSION['update_success'] = true;
        header("Location: view_permit.php?id=$id");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
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
    <title><?php echo htmlspecialchars($row['permit_name']); ?></title>
    <link rel="stylesheet" href="reportstyle.css">
    <link rel="stylesheet" href="view_permit.css">
    <link rel="stylesheet" href="permitstyle.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="icon" type="image/png" href="../REPORT.png">
    <style>
        /* Form group */
        .form-group {
            margin: 10px;
            margin-right: 10px;
        }

        .form-group label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            color: #003D73;
        }

        /* Input fields */
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            border-bottom: 1px solid #444;
        }

        textarea {
            resize: vertical;
        }

        .btn-primary {
            background-color: #003D73;
            /* BFP Blue */
            color: white;
            border: none;
            padding: 15px;
            font-size: 15px;
            cursor: pointer;
            width: 10%;
            /* Make it smaller to allow space for the cancel button */
            margin-top: 15px;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
        }

        .btn-cancel {
            background-color: #bd000a;
            /* BFP Blue */
            color: white;
            border: none;
            padding: 15px;
            font-size: 15px;
            cursor: pointer;
            width: 10%;
            /* Make it smaller to allow space for the cancel button */
            margin-top: 15px;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
        }

        .btn-primary:hover {
            background-color: #002D57;
            /* Darker Blue on hover */
        }

        .btn-cancel:hover {
            background-color: #81000a;
        }


        .download-button {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            background-color: #003D73;
            /* BFP Blue */
            color: white;
            border: none;
            padding: 10px;
            font-size: 15px;
            cursor: pointer;
            margin-top: 15px;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
        }

        .download-button:hover {
            background-color: #002D57;
        }

        .download-button i {
            font-size: 13px;
            margin-right: 10px;
        }


        /* Form Actions Container */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 5px;
        }

        .custom-file-upload {
            cursor: pointer;
            font-size: 20px;
            color: #444444;
            margin-right: 10px;
            vertical-align: middle;
        }


        .custom-file-upload i {
            margin-top: 10px;
            border: 1px solid #003d73;
            background-color: #fff;
            padding: 20px;
            border-radius: 30px;
        }

        .custom-file-upload i:hover {
            background-color: #003d73;
            color: #fff;
        }

        input[type="file"].form-control {
            display: none;
        }

        legend {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }

        /* ...existing code... */
        .btn-view,
        .btn-download,
        .btn-delete {
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


        .title-part {
            background: #003D73;
            color: white;
            padding: 15px;
            margin-top: 20px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 15px;
            border-radius: 10px;
        }

        .title-part h2 {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 20px;
            color: #ffff;
            letter-spacing: 1px;
            background-color: #003d73;
        }

        .card {
            max-width: 950px;
            /* change to desired max width (e.g. 700px for smaller) */
            width: 90%;
            /* responsive width */
            margin: 30px auto 40px;
            /* center and control vertical spacing */
            padding: 18px;
            /* inner spacing */
            box-sizing: border-box;
            border-radius: 8px;
            /* match existing look */
            background: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
        }

        .narrative-preview,
        .narrative-report,
        .permit-doc-section {
            display: block;
            width: 100%;
            max-width: auto;
            /* or a larger value, or remove this line */
            padding: 0;
            box-sizing: border-box;
        }

        .narrative-preview iframe {
            width: 100%;
            height: 300px;
            /* or your desired height */
            display: block;
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
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h2><?php echo htmlspecialchars($system_name); ?></h2>
                <div class="header-right">
                    <div class="dropdown">
                        <a href="#" class="user-icon" onclick="toggleProfileDropdown(event)">
                            <!-- Add avatar image here -->
                            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar"
                                style="width:40px;height:40px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:0px;">
                            <p><?php echo htmlspecialchars($_SESSION['username']); ?><i
                                    class="fa-solid fa-caret-down"></i></p>
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
                    <div class="form-actions">
                        <button type="submit" class="download-button"
                            onclick="window.location.href='generate_permit.php?id=<?php echo $row['id']; ?>'"> <i
                                class="fa-solid fa-download"></i>Download Report</button>
                    </div>
                    <div class="title-part">
                        <h2> Fire Safety Inspection <?php echo htmlspecialchars($row['id']); ?> </h2>
                    </div>
                    <?php
                    $can_edit = ($row['uploader'] === $username) || ($user_type === 'admin');
                    ?>
                    <form method="POST" action="view_permit.php?id=<?php echo $id; ?>" enctype="multipart/form-data">
                        <h2><?php echo htmlspecialchars($row['permit_name']); ?></h2>
                        <fieldset>
                            <legend> Inspection Details </legend>
                            <div class="form-group-container">
                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="permit_name">Title:</label>
                                    <input type="text" id="permit_name" name="permit_name" class="form-control"
                                        value="<?php echo htmlspecialchars($row['permit_name']); ?>" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>
                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="owner">Owner:</label>
                                    <input type="text" id="owner" name="owner" class="form-control"
                                        value="<?php echo htmlspecialchars($row['owner']); ?>" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>
                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="contact_person">Contact Person:</label>
                                    <input type="text" id="contact_person" name="contact_person" class="form-control"
                                        value="<?php echo htmlspecialchars($row['contact_person']); ?>" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>
                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="contact_number">Contact Number:</label>
                                    <input type="text" id="contact_number" name="contact_number" class="form-control"
                                        value="<?php echo htmlspecialchars($row['contact_number']); ?>" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>
                                <hr class="section-separator full-bleed">
                                <h4 style="text-align:center;"> Establishment Details </h4>
                                <hr class="section-separator full-bleed">
                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="inspection_establishment">Establishment Name:</label>
                                    <input type="text" id="inspection_establishment" name="inspection_establishment"
                                        class="form-control"
                                        value="<?php echo htmlspecialchars($row['inspection_establishment']); ?>"
                                        required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>

                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="inspection_address">Address:</label>
                                    <input type="text" id="inspection_address" name="inspection_address"
                                        class="form-control"
                                        value="<?php echo htmlspecialchars($row['inspection_address']); ?>" required
                                        <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>

                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="establishment_type">Establishment Type:</label>
                                    <select id="establishment_type" name="establishment_type" class="form-control"
                                        required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                        <option value="" disabled>Select Establishment Type</option>
                                        <option value="residential" <?php echo $row['establishment_type'] == 'residential' ? 'selected' : ''; ?>>Residential</option>
                                        <option value="commercial" <?php echo $row['establishment_type'] == 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                                        <option value="industrial" <?php echo $row['establishment_type'] == 'industrial' ? 'selected' : ''; ?>>Industrial</option>
                                    </select>
                                </div>
                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="inspection_purpose">Purpose of Inspection:</label>
                                    <select id="inspection_purpose" name="inspection_purpose" class="form-control"
                                        required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                        <option value="" disabled>Select Purpose</option>
                                        <option value="routine" <?php echo $row['inspection_purpose'] == 'routine' ? 'selected' : ''; ?>>Routine</option>
                                        <option value="compliance" <?php echo $row['inspection_purpose'] == 'compliance' ? 'selected' : ''; ?>>Compliance</option>
                                        <option value="complaint" <?php echo $row['inspection_purpose'] == 'complaint' ? 'selected' : ''; ?>>Complaint</option>
                                    </select>
                                </div>
                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="number_of_occupants">Number of Occupants:</label>
                                    <input type="number" id="number_of_occupants" name="number_of_occupants"
                                        class="form-control"
                                        value="<?php echo htmlspecialchars($row['number_of_occupants']); ?>" required
                                        <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>
                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="nature_of_business">Nature of Business:</label>
                                    <input type="text" id="nature_of_business" name="nature_of_business"
                                        class="form-control"
                                        value="<?php echo htmlspecialchars($row['nature_of_business']); ?>" required
                                        <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>
                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="number_of_floors">Number of Floors:</label>
                                    <input type="number" id="number_of_floors" name="number_of_floors"
                                        class="form-control"
                                        value="<?php echo htmlspecialchars($row['number_of_floors']); ?>" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>
                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="floor_area">Floor Area:</label>
                                    <input type="text" id="floor_area" name="floor_area" class="form-control"
                                        value="<?php echo htmlspecialchars($row['floor_area']); ?>" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>
                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="classification_of_hazards">Classification of Hazards:</label>
                                    <select id="classification_of_hazards" name="classification_of_hazards"
                                        class="form-control" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                        <option value="" disabled>Select Classification</option>
                                        <option value="Class_A" <?php echo $row['classification_of_hazards'] == 'Class_A' ? 'selected' : ''; ?>>Class A</option>
                                        <option value="Class_B" <?php echo $row['classification_of_hazards'] == 'Class_B' ? 'selected' : ''; ?>>Class B</option>
                                        <option value="Class_C" <?php echo $row['classification_of_hazards'] == 'Class_C' ? 'selected' : ''; ?>>Class C</option>
                                        <option value="Class_D" <?php echo $row['classification_of_hazards'] == 'Class_D' ? 'selected' : ''; ?>>Class D</option>
                                        <option value="Class_K" <?php echo $row['classification_of_hazards'] == 'Class_K' ? 'selected' : ''; ?>>Class K</option>
                                    </select>
                                </div>

                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="building_construction">Building Construction:</label>
                                    <input type="text" id="building_construction" name="building_construction"
                                        class="form-control"
                                        value="<?php echo htmlspecialchars($row['building_construction']); ?>" required
                                        <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>
                                <hr class="section-separator full-bleed">
                                <h4 style="text-align: center;"> Inspection Details </h4>
                                <hr class="section-separator full-bleed">
                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="inspection_date">Date of Inspection:</label>
                                    <input type="date" id="inspection_date" name="inspection_date" class="form-control"
                                        value="<?php echo htmlspecialchars($row['inspection_date']); ?>" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>

                                <div class="form-group" style="width: 45%; display: inline-block;">
                                    <label for="inspected_by">Inspected By:</label>
                                    <input type="text" id="inspected_by" name="inspected_by" class="form-control"
                                        value="<?php echo htmlspecialchars($row['inspected_by']); ?>" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>
                                <div class="form-group-container">
                                    <div class="form-group" style="width: 45%; display: inline-block;">
                                        <label for="possible_problems">Possible Problems during Fire:</label>
                                        <textarea id="possible_problems" name="possible_problems" rows="10" cols="30"
                                            placeholder="Possible Problems During Fire" onfocus="addFirstNumber()"
                                            oninput="autoNumber()" <?php echo !$can_edit ? 'disabled' : ''; ?>><?php echo htmlspecialchars($row['possible_problems']); ?></textarea>
                                    </div>
                                    <div class="form-group" style="width: 45%; display: inline-block;">
                                        <label for="hazardous_materials">Hazardous/Flammable Materials:</label>
                                        <textarea id="hazardous_materials" name="hazardous_materials" rows="10"
                                            cols="30" placeholder="Hazardous Materials" onfocus="addFirstNumber()"
                                            oninput="autoNumber()" <?php echo !$can_edit ? 'disabled' : ''; ?>><?php echo htmlspecialchars($row['hazardous_materials']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <br>
                        <!-- <fieldset>
        <legend> Fire Safety Measures </legend>
        <table border="1" style="width: 100%; border-collapse: collapse; text-align: center;">
            <thead>
                <tr>
                    <th>Measure</th>
                    <th>Yes</th>
                    <th>No</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $measures = [
                    'fire_alarms' => 'Fire Alarms',
                    'fire_extinguishers' => 'Fire Extinguishers',
                    'emergency_exits' => 'Emergency Exits',
                    'sprinkler_systems' => 'Sprinkler Systems',
                    'fire_drills' => 'Fire Drills',
                    'exit_signs' => 'Exit Signs',
                    'electrical_wiring' => 'Electrical Wiring (Safe)',
                    'emergency_evacuations' => 'Emergency Evacuations',
                ];
                foreach ($measures as $field => $label) {
                    $checkedYes = $row[$field] == 1 ? 'checked' : '';
                    $checkedNo = $row[$field] == 0 ? 'checked' : '';
                    ?>
                <tr>
                    <td><?php echo $label; ?></td>
                    <td><input type="radio" name="<?php echo $field; ?>" value="1" <?php echo $checkedYes; ?>></td>
                    <td><input type="radio" name="<?php echo $field; ?>" value="0" <?php echo $checkedNo; ?>></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </fieldset> -->


                        <br>
                        <fieldset>
                            <legend> Required Attachments </legend>
                            <div class="form-group-container" style="margin-bottom: 0;">
                                <label
                                    style="font-weight: bold; display: block; margin-bottom: 5px; color:#003D73;">Required
                                    Attachments</label>
                                <div class="tab-container">
                                    <button type="button" class="tab-btn"
                                        onclick="showTab('application_form_section')">Application Form (BFP)</button>
                                    <button type="button" class="tab-btn"
                                        onclick="showTab('proof_of_ownership_section')">Proof of Ownership</button>
                                    <button type="button" class="tab-btn"
                                        onclick="showTab('fire_safety_inspection_checklist_section')">Fire Safety
                                        Inspection Checklist</button>
                                    <button type="button" class="tab-btn"
                                        onclick="showTab('building_plans_section')">Building Plans</button>
                                    <button type="button" class="tab-btn"
                                        onclick="showTab('fire_safety_inspection_certificate_section')">Fire Safety
                                        Inspection Certificate</button>
                                    <button type="button" class="tab-btn"
                                        onclick="showTab('occupancy_permit_section')">Occupancy Permit</button>
                                    <button type="button" class="tab-btn"
                                        onclick="showTab('business_permit_section')">Business Permit</button>
                                </div>
                            </div>

                            <!-- Application Form -->
                            <div id="application_form_section" class="permit-doc-section" style="display:none;">
                                <div class="form-group">
                                    <div class="narrative-report">
                                        <?php if (!empty($row['application_form'])): ?>
                                            <a href="<?php echo $row['application_form']; ?>" target="_blank"
                                                class="btn-view"><i class="fa-solid fa-eye"></i></a>
                                            <a href="<?php echo $row['application_form']; ?>" download
                                                class="btn-download"><i class="fa-solid fa-download"></i></a>
                                            <button type="button" class="btn btn-delete"
                                                onclick="deleteReportFile('application_form', <?php echo $id; ?>)"><i
                                                    class="fa-solid fa-trash"></i></button>
                                        <?php else: ?>
                                        <?php endif; ?>
                                        <div id="application-preview" class="narrative-preview">
                                            <?php if (!empty($row['application_form'])): ?>
                                                <h4>Preview:</h4>
                                                <?php
                                                $file_extension = pathinfo($row['application_form'], PATHINFO_EXTENSION);
                                                if (strtolower($file_extension) === 'pdf') { ?>
                                                    <iframe src="<?php echo htmlspecialchars($row['application_form']); ?>"
                                                        width="100%" height="500px"></iframe>
                                                <?php } else { ?>
                                                    <p>Preview not available for this file type. <a
                                                            href="<?php echo htmlspecialchars($row['application_form']); ?>"
                                                            target="_blank">Download to view the file.</a></p>
                                                <?php } ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <label for="application_form">
                                        <?php echo empty($row['application_form']) ? 'Add New Application Form (BFP):' : 'Change Application Form (BFP):'; ?>
                                    </label>
                                    <label for="application_form_file" class="custom-file-upload">
                                        <?php if (empty($row['application_form'])) { ?>
                                            <i class="fa-solid fa-plus"></i>
                                        <?php } else { ?>
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        <?php } ?>
                                    </label>
                                    <input type="file" id="application_form_file" name="application_form_file"
                                        class="form-control" accept=".pdf,.doc,.docx,.txt,.rtf"
                                        onchange="previewPermitFile(event, 'application-preview')" <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>
                            </div>

                            <!-- Proof of Ownership -->
                            <div id="proof_of_ownership_section" class="permit-doc-section" style="display:none;">
                                <div class="form-group">
                                    <div class="narrative-report">
                                        <?php if (!empty($row['proof_of_ownership'])): ?>
                                            <a href="<?php echo $row['proof_of_ownership']; ?>" target="_blank"
                                                class="btn-view"><i class="fa-solid fa-eye"></i></a>
                                            <a href="<?php echo $row['proof_of_ownership']; ?>" download
                                                class="btn-download"><i class="fa-solid fa-download"></i></a>
                                            <button type="button" class="btn btn-delete"
                                                onclick="deleteReportFile('proof_of_ownership', <?php echo $id; ?>)"><i
                                                    class="fa-solid fa-trash"></i></button>
                                        <?php else: ?>
                                        <?php endif; ?>
                                        <div id="ownership-preview" class="narrative-preview">
                                            <?php if (!empty($row['proof_of_ownership'])): ?>
                                                <h4>Preview:</h4>
                                                <?php
                                                $file_extension = pathinfo($row['proof_of_ownership'], PATHINFO_EXTENSION);
                                                if (strtolower($file_extension) === 'pdf') { ?>
                                                    <iframe src="<?php echo htmlspecialchars($row['proof_of_ownership']); ?>"
                                                        width="100%" height="300px"></iframe>
                                                <?php } else { ?>
                                                    <p>Preview not available for this file type. <a
                                                            href="<?php echo htmlspecialchars($row['proof_of_ownership']); ?>"
                                                            target="_blank">Download to view the file.</a></p>
                                                <?php } ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <label for="proof_of_ownership">
                                        <?php echo empty($row['proof_of_ownership']) ? 'Add New Proof of Ownership:' : 'Change Proof of Ownership:'; ?>
                                    </label>
                                    <label for="proof_of_ownership_file" class="custom-file-upload">
                                        <?php if (empty($row['proof_of_ownership'])) { ?>
                                            <i class="fa-solid fa-plus"></i>
                                        <?php } else { ?>
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        <?php } ?>
                                    </label>
                                    <input type="file" id="proof_of_ownership_file" name="proof_of_ownership_file"
                                        class="form-control" accept=".pdf,.doc,.docx,.txt,.rtf"
                                        onchange="previewPermitFile(event, 'ownership-preview')" <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>
                            </div>

                            <!-- Building Plans -->
                            <!-- <div id="building_plans_section" class="permit-doc-section" style="display:none;">
    <div class="form-group">
        <div class="narrative-report">
            <?php if (!empty($row['building_plans'])): ?>
            <a href="<?php echo $row['building_plans']; ?>" target="_blank" class="btn-view"><i class="fa-solid fa-eye"></i></a>
            <a href="<?php echo $row['building_plans']; ?>" download class="btn-download"><i class="fa-solid fa-download"></i></a>
            <button type="button" class="btn btn-delete" onclick="deleteReportFile('building_plans', <?php echo $id; ?>)"><i class="fa-solid fa-trash"></i></button>
            <?php else: ?>
            <?php endif; ?>
            <div id="plans-preview" class="narrative-preview">
                <?php if (!empty($row['building_plans'])): ?>
                    <h4>Preview:</h4>
                    <?php
                    $file_extension = pathinfo($row['building_plans'], PATHINFO_EXTENSION);
                    if (strtolower($file_extension) === 'pdf') { ?>
                        <iframe src="<?php echo htmlspecialchars($row['building_plans']); ?>" width="100%" height="300px"></iframe>
                    <?php } else { ?>
                        <p>Preview not available for this file type. <a href="<?php echo htmlspecialchars($row['building_plans']); ?>" target="_blank">Download to view the file.</a></p>
                    <?php } ?>
                <?php endif; ?>
            </div>
        </div>
        <label for="building_plans">Change Building Plans:</label>
        <label for="building_plans_file" class="custom-file-upload"><i class="fa-solid fa-pen-to-square"></i></label>
        <input type="file" id="building_plans_file" name="building_plans_file" class="form-control"
            accept=".pdf,.doc,.docx,.txt,.rtf" onchange="previewPermitFile(event, 'plans-preview')" <?php echo !$can_edit ? 'disabled' : ''; ?>>
    </div>
</div> -->

                            <!-- Fire Safety Equipment -->
                            <div id="fire_safety_inspection_checklist_section" class="permit-doc-section"
                                style="display:none;">
                                <div class="form-group">
                                    <div class="narrative-report">
                                        <?php if (!empty($row['fire_safety_inspection_checklist'])): ?>
                                            <a href="<?php echo $row['fire_safety_inspection_checklist']; ?>"
                                                target="_blank" class="btn-view"><i class="fa-solid fa-eye"></i></a>
                                            <a href="<?php echo $row['fire_safety_inspection_checklist']; ?>" download
                                                class="btn-download"><i class="fa-solid fa-download"></i></a>
                                            <button type="button" class="btn btn-delete"
                                                onclick="deleteReportFile('fire_safety_inspection_checklist', <?php echo $id; ?>)"><i
                                                    class="fa-solid fa-trash"></i></button> <?php else: ?>
                                        <?php endif; ?>
                                        <div id="checklist-preview" class="narrative-preview">
                                            <?php if (!empty($row['fire_safety_inspection_checklist'])): ?>
                                                <h4>Preview:</h4>
                                                <?php
                                                $file_extension = pathinfo($row['fire_safety_inspection_checklist'], PATHINFO_EXTENSION);
                                                if (strtolower($file_extension) === 'pdf') { ?>
                                                    <iframe
                                                        src="<?php echo htmlspecialchars($row['fire_safety_inspection_checklist']); ?>"
                                                        width="100%" height="300px"></iframe>
                                                <?php } else { ?>
                                                    <p>Preview not available for this file type. <a
                                                            href="<?php echo htmlspecialchars($row['fire_safety_inspection_checklist']); ?>"
                                                            target="_blank">Download to view the file.</a></p>
                                                <?php } ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <label for="fire_safety_inspection_checklist">
                                        <?php echo empty($row['fire_safety_inspection_checklist']) ? 'Add New Fire Safety Inspection Checklist:' : 'Change Fire Safety Inspection Checklist:'; ?>
                                    </label>
                                    <label for="fire_safety_inspection_checklist_file" class="custom-file-upload">
                                        <?php if (empty($row['fire_safety_inspection_checklist'])) { ?>
                                            <i class="fa-solid fa-plus"></i>
                                        <?php } else { ?>
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        <?php } ?>
                                    </label>
                                    <input type="file" id="fire_safety_inspection_checklist_file"
                                        name="fire_safety_inspection_checklist_file" class="form-control"
                                        accept=".pdf,.doc,.docx,.txt,.rtf"
                                        onchange="previewPermitFile(event, 'checklist-preview')" <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>
                            </div>


                            <!-- Building Plans -->
                            <div id="building_plans_section" class="permit-doc-section" style="display:none;">
                                <div class="form-group">
                                    <div class="narrative-report">
                                        <?php if (!empty($row['building_plans'])): ?>
                                            <a href="<?php echo htmlspecialchars($row['building_plans']); ?>"
                                                target="_blank" class="btn-view"><i class="fa-solid fa-eye"></i></a>
                                            <a href="<?php echo htmlspecialchars($row['building_plans']); ?>" download
                                                class="btn-download"><i class="fa-solid fa-download"></i></a>
                                            <button type="button" class="btn btn-delete"
                                                onclick="deleteReportFile('building_plans', <?php echo $id; ?>)"><i
                                                    class="fa-solid fa-trash"></i></button>
                                        <?php else: ?>
                                        <?php endif; ?>
                                        <div id="plans-preview" class="narrative-preview">
                                            <?php if (!empty($row['building_plans'])): ?>
                                                <h4>Preview:</h4>
                                                <?php
                                                $file_extension = pathinfo($row['building_plans'], PATHINFO_EXTENSION);
                                                if (strtolower($file_extension) === 'pdf') { ?>
                                                    <iframe src="<?php echo htmlspecialchars($row['building_plans']); ?>"
                                                        width="100%" height="300px"></iframe>
                                                <?php } else { ?>
                                                    <p>Preview not available for this file type. <a
                                                            href="<?php echo htmlspecialchars($row['building_plans']); ?>"
                                                            target="_blank">Download to view the file.</a></p>
                                                <?php } ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <label for="building_plans_file">
                                        <?php echo empty($row['building_plans']) ? 'Add New Building Plans:' : 'Change Building Plans:'; ?>
                                    </label>
                                    <label for="building_plans_file" class="custom-file-upload">
                                        <?php if (empty($row['building_plans'])) { ?>
                                            <i class="fa-solid fa-plus"></i>
                                        <?php } else { ?>
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        <?php } ?>
                                    </label>
                                    <input type="file" id="building_plans_file" name="building_plans_file"
                                        class="form-control" accept=".pdf,.doc,.docx,.txt,.rtf"
                                        onchange="previewPermitFile(event, 'plans-preview')" <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>
                            </div>

                            <!-- Fire Safety Inspection Certificate -->
                            <div id="fire_safety_inspection_certificate_section" class="permit-doc-section"
                                style="display:none;">
                                <div class="form-group">
                                    <div class="narrative-report">
                                        <?php if (!empty($row['fire_safety_inspection_certificate'])): ?>
                                            <a href="<?php echo htmlspecialchars($row['fire_safety_inspection_certificate']); ?>"
                                                target="_blank" class="btn-view"><i class="fa-solid fa-eye"></i></a>
                                            <a href="<?php echo htmlspecialchars($row['fire_safety_inspection_certificate']); ?>"
                                                download class="btn-download"><i class="fa-solid fa-download"></i></a>
                                            <button type="button" class="btn btn-delete"
                                                onclick="deleteReportFile('fire_safety_inspection_certificate', <?php echo $id; ?>)"><i
                                                    class="fa-solid fa-trash"></i></button>
                                        <?php else: ?>
                                        <?php endif; ?>
                                        <div id="certificate-preview" class="narrative-preview">
                                            <?php if (!empty($row['fire_safety_inspection_certificate'])): ?>
                                                <h4>Preview:</h4>
                                                <?php
                                                $file_extension = pathinfo($row['fire_safety_inspection_certificate'], PATHINFO_EXTENSION);
                                                if (strtolower($file_extension) === 'pdf') { ?>
                                                    <iframe
                                                        src="<?php echo htmlspecialchars($row['fire_safety_inspection_certificate']); ?>"
                                                        width="100%" height="300px"></iframe>
                                                <?php } else { ?>
                                                    <p>Preview not available for this file type. <a
                                                            href="<?php echo htmlspecialchars($row['fire_safety_inspection_certificate']); ?>"
                                                            target="_blank">Download to view the file.</a></p>
                                                <?php } ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <label for="fire_safety_inspection_certificate_file">
                                        <?php echo empty($row['fire_safety_inspection_certificate']) ? 'Add New Fire Safety Inspection Certificate:' : 'Change Fire Safety Inspection Certificate:'; ?>
                                    </label>
                                    <label for="fire_safety_inspection_certificate_file" class="custom-file-upload">
                                        <?php if (empty($row['fire_safety_inspection_certificate'])) { ?>
                                            <i class="fa-solid fa-plus"></i>
                                        <?php } else { ?>
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        <?php } ?>
                                    </label>
                                    <input type="file" id="fire_safety_inspection_certificate_file"
                                        name="fire_safety_inspection_certificate_file" class="form-control"
                                        accept=".pdf,.doc,.docx,.txt,.rtf"
                                        onchange="previewPermitFile(event, 'certificate-preview')" <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>
                            </div>

                            <!-- Occupancy Permit -->
                            <div id="occupancy_permit_section" class="permit-doc-section" style="display:none;">
                                <div class="form-group">
                                    <div class="narrative-report">
                                        <?php if (!empty($row['occupancy_permit'])): ?>
                                            <a href="<?php echo htmlspecialchars($row['occupancy_permit']); ?>"
                                                target="_blank" class="btn-view"><i class="fa-solid fa-eye"></i></a>
                                            <a href="<?php echo htmlspecialchars($row['occupancy_permit']); ?>" download
                                                class="btn-download"><i class="fa-solid fa-download"></i></a>
                                            <button type="button" class="btn btn-delete"
                                                onclick="deleteReportFile('occupancy_permit', <?php echo $id; ?>)"><i
                                                    class="fa-solid fa-trash"></i></button> <?php else: ?>
                                        <?php endif; ?>
                                        <div id="occupancy-preview" class="narrative-preview">
                                            <?php if (!empty($row['occupancy_permit'])): ?>
                                                <h4>Preview:</h4>
                                                <?php
                                                $file_extension = pathinfo($row['occupancy_permit'], PATHINFO_EXTENSION);
                                                if (strtolower($file_extension) === 'pdf') { ?>
                                                    <iframe src="<?php echo htmlspecialchars($row['occupancy_permit']); ?>"
                                                        width="100%" height="300px"></iframe>
                                                <?php } else { ?>
                                                    <p>Preview not available for this file type. <a
                                                            href="<?php echo htmlspecialchars($row['occupancy_permit']); ?>"
                                                            target="_blank">Download to view the file.</a></p>
                                                <?php } ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <label for="occupancy_permit">
                                        <?php echo empty($row['occupancy_permit']) ? 'Add New Occupancy Permit:' : 'Change Occupancy Permit:'; ?>
                                    </label>
                                    <label for="occupancy_permit_file" class="custom-file-upload">
                                        <?php if (empty($row['occupancy_permit'])) { ?>
                                            <i class="fa-solid fa-plus"></i>
                                        <?php } else { ?>
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        <?php } ?>
                                    </label>
                                    <input type="file" id="occupancy_permit_file" name="occupancy_permit_file"
                                        class="form-control" accept=".pdf,.doc,.docx,.txt,.rtf"
                                        onchange="previewPermitFile(event, 'occupancy-preview')" <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>
                            </div>

                            <!-- Business Permit -->
                            <div id="business_permit_section" class="permit-doc-section" style="display:none;">
                                <div class="form-group">
                                    <div class="narrative-report">
                                        <?php if (!empty($row['business_permit'])): ?>
                                            <a href="<?php echo htmlspecialchars($row['business_permit']); ?>"
                                                target="_blank" class="btn-view"><i class="fa-solid fa-eye"></i></a>
                                            <a href="<?php echo htmlspecialchars($row['business_permit']); ?>" download
                                                class="btn-download"><i class="fa-solid fa-download"></i></a>
                                            <button type="button" class="btn btn-delete"
                                                onclick="deleteReportFile('business_permit', <?php echo $id; ?>)"><i
                                                    class="fa-solid fa-trash"></i></button>
                                        <?php else: ?>
                                        <?php endif; ?>
                                        <div id="business-preview" class="narrative-preview">
                                            <?php if (!empty($row['business_permit'])): ?>
                                                <h4>Preview:</h4>
                                                <?php
                                                $file_extension = pathinfo($row['business_permit'], PATHINFO_EXTENSION);
                                                if (strtolower($file_extension) === 'pdf') { ?>
                                                    <iframe src="<?php echo htmlspecialchars($row['business_permit']); ?>"
                                                        width="100%" height="300px"></iframe>
                                                <?php } else { ?>
                                                    <p>Preview not available for this file type. <a
                                                            href="<?php echo htmlspecialchars($row['business_permit']); ?>"
                                                            target="_blank">Download to view the file.</a></p>
                                                <?php } ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <label for="business_permit">
                                        <?php echo empty($row['business_permit']) ? 'Add New Business Permit:' : 'Change Business Permit:'; ?>
                                    </label>
                                    <label for="business_permit_file" class="custom-file-upload">
                                        <?php if (empty($row['business_permit'])) { ?>
                                            <i class="fa-solid fa-plus"></i>
                                        <?php } else { ?>
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        <?php } ?>
                                    </label>
                                    <input type="file" id="business_permit_file" name="business_permit_file"
                                        class="form-control" accept=".pdf,.doc,.docx,.txt,.rtf"
                                        onchange="previewPermitFile(event, 'business-preview')" <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                </div>
                            </div>
                        </fieldset>
                        </td>
                        </tr>
                        </table>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" <?php echo !$can_edit ? 'disabled style="opacity:0.6;cursor:not-allowed;"' : ''; ?>>Save</button>
                            <a href="my_fire_safety_reports.php" class="btn btn-cancel">Cancel</a>
                        </div>

                    </form>

                    <div id="confirmDeleteModal" class="confirm-delete-modal" style="display:none;">
                        <div class="modal-content">
                            <h3>Confirm Delete?</h3>
                            <hr>
                            <p> Are you sure you want to delete this Photo? </p>
                            <button id="confirmDeleteBtn" class="confirm-btn">Delete</button>
                            <button id="cancelDeleteBtn" class="cancel-btn">Cancel</button>
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
                            <p>Report updated successfully!</p>
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
                        // Show modal if update was successful
                        window.onload = function () {
                            if (<?php echo isset($_SESSION['update_success']) && $_SESSION['update_success'] ? 'true' : 'false'; ?>) {
                                // Show success modal
                                document.getElementById('successModal').style.display = 'block';

                                // Remove success session after showing the modal
                                <?php unset($_SESSION['update_success']); ?>

                                // Hide the modal after 2 seconds and redirect
                                setTimeout(function () {
                                    document.getElementById('successModal').style.display = 'none';
                                    window.location.href = 'fire_safety_inspection_certificate.php'; // Redirect after 2 seconds
                                }, 2000); // 2000 milliseconds = 2 seconds
                            }

                            // Close the modal when the user clicks on the "x"
                            document.getElementById('closeModal').onclick = function () {
                                document.getElementById('successModal').style.display = 'none';
                            }

                            // Close the modal when the user clicks anywhere outside of the modal content
                            window.onclick = function (event) {
                                if (event.target == document.getElementById('successModal')) {
                                    document.getElementById('successModal').style.display = 'none';
                                }
                            }
                        }

                        function previewPermitFile(event, previewContainerId) {
                            const previewContainer = document.getElementById(previewContainerId);
                            if (!previewContainer) return;

                            // Show the parent section if hidden
                            let section = previewContainer;
                            // Go up until you find the .permit-doc-section
                            while (section && !section.classList.contains('permit-doc-section')) {
                                section = section.parentElement;
                            }
                            if (section && section.style.display === 'none') {
                                section.style.display = 'block';
                            }

                            previewContainer.innerHTML = ''; // Clear previous preview

                            const file = event.target.files[0];
                            if (!file) return;

                            const fileUrl = URL.createObjectURL(file);
                            const fileExtension = file.name.split('.').pop().toLowerCase();

                            if (fileExtension === 'pdf') {
                                previewContainer.innerHTML = `
            <h4>Preview:</h4>
            <iframe src="${fileUrl}" width="100%" height="500px"></iframe>
        `;
                            } else if (['doc', 'docx', 'txt', 'rtf'].includes(fileExtension)) {
                                previewContainer.innerHTML = `
            <h4>Preview not available.</h4>
            <p><a href="${fileUrl}" target="_blank">Download to view the file.</a></p>
        `;
                            } else {
                                previewContainer.innerHTML = `<p>Invalid file format.</p>`;
                            }
                        }

                        function showPermitDocSection() {
                            var docType = document.getElementById('permit_doc_type').value;
                            var sections = document.querySelectorAll('.permit-doc-section');
                            sections.forEach(function (section) {
                                section.style.display = 'none';
                            });
                            if (docType) {
                                document.getElementById(docType).style.display = 'block';
                            }
                        }

                        function deleteReportFile(field, id) {
                            // Show the confirm delete modal
                            document.getElementById('confirmDeleteModal').style.display = 'flex';

                            // When confirm is clicked
                            document.getElementById('confirmDeleteBtn').onclick = function () {
                                // Hide modal
                                document.getElementById('confirmDeleteModal').style.display = 'none';
                                // Redirect to PHP script to handle deletion (adjust the script name/path as needed)
                                window.location.href = `delete_permit_file.php?id=${id}&field=${field}`;
                            };

                            // When cancel is clicked
                            document.getElementById('cancelDeleteBtn').onclick = function () {
                                document.getElementById('confirmDeleteModal').style.display = 'none';
                            };
                        }

                        document.addEventListener('DOMContentLoaded', function () {
                            const form = document.querySelector('form');
                            const saveBtn = document.querySelector('button[type="submit"].btn-primary');

                            // Store initial values for all fields except permit_doc_type
                            const initialValues = {};
                            form.querySelectorAll('input, select, textarea').forEach(input => {
                                if (input.name && input.name !== 'permit_doc_type' && input.type !== 'file') {
                                    initialValues[input.name] = input.value;
                                }
                            });

                            // Disable the button initially
                            saveBtn.disabled = true;
                            saveBtn.style.opacity = "0.6";
                            saveBtn.style.cursor = "not-allowed";

                            function isFormChanged() {
                                let changed = false;
                                form.querySelectorAll('input, select, textarea').forEach(input => {
                                    if (input.name && input.name !== 'permit_doc_type' && input.type !== 'file') {
                                        if (input.value !== initialValues[input.name]) {
                                            changed = true;
                                        }
                                    }
                                });
                                // Check file inputs separately
                                form.querySelectorAll('input[type="file"]').forEach(input => {
                                    if (input.files.length > 0) changed = true;
                                });
                                return changed;
                            }

                            form.addEventListener('input', function (e) {
                                // Ignore changes to permit_doc_type
                                if (e.target.name === 'permit_doc_type') return;
                                if (isFormChanged()) {
                                    saveBtn.disabled = false;
                                    saveBtn.style.opacity = "1";
                                    saveBtn.style.cursor = "pointer";
                                } else {
                                    saveBtn.disabled = true;
                                    saveBtn.style.opacity = "0.6";
                                    saveBtn.style.cursor = "not-allowed";
                                }
                            });

                            // Also listen for file changes
                            form.querySelectorAll('input[type="file"]').forEach(input => {
                                input.addEventListener('change', function () {
                                    if (isFormChanged()) {
                                        saveBtn.disabled = false;
                                        saveBtn.style.opacity = "1";
                                        saveBtn.style.cursor = "pointer";
                                    } else {
                                        saveBtn.disabled = true;
                                        saveBtn.style.opacity = "0.6";
                                        saveBtn.style.cursor = "not-allowed";
                                    }
                                });
                            });
                        });

                        function showTab(sectionId) {
                            // Hide all sections
                            document.querySelectorAll('.permit-doc-section').forEach(function (section) {
                                section.style.display = 'none';
                            });
                            // Remove active from all tab buttons
                            document.querySelectorAll('.tab-btn').forEach(function (btn) {
                                btn.classList.remove('active');
                            });
                            // Show selected section
                            document.getElementById(sectionId).style.display = 'block';
                            // Set active tab
                            document.querySelectorAll('.tab-btn').forEach(function (btn) {
                                if (btn.getAttribute('onclick').includes(sectionId)) {
                                    btn.classList.add('active');
                                }
                            });
                        }
                        // Optionally, show the first tab by default
                        document.addEventListener('DOMContentLoaded', function () {
                            showTab('application_form_section');
                        });
                    </script>


</body>

</html>
<script src="../js/archivescript.js"></script>