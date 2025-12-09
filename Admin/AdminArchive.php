<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: adminlogin.php"); // Redirect to login if not logged in
    exit();
}

include('connection.php');

// Retrieve department filter if set
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : '';

// Prepare the SQL query based on filter
$sql = "SELECT * FROM archives";

if ($departmentFilter != '') {
    $sql .= " WHERE department = ?";
}

// Execute the query and fetch results
$stmt = $conn->prepare($sql);
if ($departmentFilter != '') {
    $stmt->bind_param("s", $departmentFilter);  // 's' for string parameter (department)
}
$stmt->execute();
$result = $stmt->get_result();

// Handling form submission for creating archive
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $report_title = $_POST['report_title'];
    $fire_location = $_POST['fire_location'];
    $incident_date = $_POST['incident_date'];
    $establishment = $_POST['establishment'];
    $victims = $_POST['victims'];
    $property_damage = $_POST['property_damage'];
    $fire_origin = $_POST['fire_origin'];
    $fire_cause = $_POST['fire_cause'];
    $department = $_SESSION['department'];  // Assuming the uploader is the logged-in user
    $uploader = $_SESSION['username'];  // Assuming the uploader is the logged-in user

    // Insert data into the fire_incident_reports table
    $query = "INSERT INTO fire_incident_reports (report_title, fire_location, incident_date, establishment, victims, property_damage, fire_origin, fire_cause, department, uploader)
              VALUES ('$report_title', '$fire_location', '$incident_date', '$establishment', '$victims', '$property_damage', '$fire_origin', '$fire_cause','$department', '$uploader')";

    if (mysqli_query($conn, $query)) {
        // Insert the report into the archives table
        $archive_query = "INSERT INTO archives (report_title, fire_location, incident_date, establishment, victims, property_damage, fire_origin, fire_cause, department, uploader)
                          VALUES ('$report_title', '$fire_location', '$incident_date', '$establishment', '$victims', '$property_damage', '$fire_origin', '$fire_cause','$department', '$uploader')";

        if (mysqli_query($conn, $archive_query)) {
            $_SESSION['success_message'] = "Report created and archived successfully!";
        } else {
            $_SESSION['error_message'] = "Error archiving report: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error_message'] = "Error creating report: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Admin/archivestyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Archive</title>
    <style>
.card {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    padding: 20px;
    margin: 20px;
    overflow: hidden;
}


        .filter{
            margin: 0px ;
            padding: 5px;
            background-color: #f9f9f9;
            border-radius: 8px;
            display: flex;
            align-items: center;
        }

        .filter label {
            font-weight: bold;
            margin-right: 10px;
        }

        .filter select{
            padding: 8px;
            font-size: 14px;
            border-radius: 4px;
            border: 1px solid #ccc;
            cursor: pointer;
        }

        .filter select:focus{
            border-color: #007bff;
        }



        .dropdown-btn i.fa-chevron-down {
            margin-left: 50px; /* Push the chevron to the right */
            transition: transform 0.3s ease;
        }

        .dropdown-btn.active i.fa-chevron-down {
            transform: rotate(180deg); /* Rotate the chevron when active */
        }

        .fa-chevron-down{
            font-size: 10px;
        }    


    /* Success and error message styles */
    .message {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        padding: 15px;
        border-radius: 5px;
        font-size: 16px;
        z-index: 9999;
        opacity: 0; /* Initially hidden */
        animation: fadeInOut 4s ease forwards; /* Animation added */
    }

    .success {
        background-color: #4CAF50;
        color: white;
    }

    .error {
        background-color: #f44336;
        color: white;
    }

    /* Fade-in and fade-out animation */
    @keyframes fadeInOut {
        0% {
            opacity: 0;
        }
        20% {
            opacity: 1; /* Fade in */
        }
        80% {
            opacity: 1; /* Stay visible */
        }
        100% {
            opacity: 0; /* Fade out */
        }
    }

    /* Dropdown and profile styles */
    .dropdown-content {
        display: none;
        position: absolute;
        background-color: #f1f1f1;
        min-width: 160px;
        box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
        z-index: 1;
    }

    .dropdown-content a {
        color: black;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
    }

    .dropdown-content a:hover {
        background-color: #ddd;
    }

    .dropdown {
        position: relative;
        display: inline-block;
    }

    /* Style for the buttons */
    .dropdown button, .action-btn {
        background-color: #4CAF50;
        color: white;
        padding: 10px 20px;
        border: none;
        cursor: pointer;
    }

    .dropdown button:hover, .action-btn:hover {
        background-color: #3e8e41;
        padding: 10px 20px;
    }

    /* Show the dropdown when active */
.dropdown-content.show {
    display: block;
}

            /* Modal Styles */
    .modal {
        display: none; /* Hidden by default */
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5); /* Black background with opacity */
        justify-content: center;
        align-items: center;
        }

        .modal-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    width: 500px;
    max-width: 100%;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    position: relative;
}

.modal-content h3 {
    margin-bottom: 10px;
    text-align: center;
    font-size: 24px;
    color: #333;
}

.modal-content label {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 5px;
    display: block;
}

.modal-content select,
.modal-content input[type="file"],
.modal-content textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    background-color: #f9f9f9;
    transition: border-color 0.3s;
}

.modal-content select:focus,
.modal-content input[type="file"]:focus,
.modal-content textarea:focus {
    border-color: #4CAF50;
    outline: none;
}

.modal-content textarea {
    resize: none;
    height: 100px;
}

.modal-btn {
    display: flex;
    justify-content: space-between;
    width: 100%;
    gap: 10px;
}

.modal-btn button {
    width: 48%;
    padding: 10px;
    font-size: 16px;
    font-weight: bold;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.modal-btn button[type="submit"] {
    background-color: #4CAF50;
}

.modal-btn button[type="submit"]:hover {
    background-color: #3e8e41;
}

.modal-btn button[type="button"] {
    background-color: #f44336;
}

.modal-btn button[type="button"]:hover {
    background-color: #d32f2f;
}

.custom-file {
    position: relative;
    display: inline-block;
    width: 100%;
    cursor: pointer;
}

.custom-file input[type="file"] {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    opacity: 0; /* Hide the default file input */
    cursor: pointer;
}

.custom-file-label {
    display: block;
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f9f9f9;
    font-size: 14px;
    text-align: center;
    color: #555;
    transition: background-color 0.3s;
    cursor: pointer;
}

.custom-file-label:hover {
    background-color: #f0f0f0;
}

.custom-file-label::after {
    content: "Choose File";
}
.close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    position: absolute;
    top: 10px;
    right: 25px;
}

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
</style>
</head>
<body>
        <!-- Display success or error message -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="message success">
            <?php echo $_SESSION['success_message']; ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php elseif (isset($_SESSION['error_message'])): ?>
        <div class="message error">
            <?php echo $_SESSION['error_message']; ?>
            <?php unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <aside class="sidebar">
        <nav>
            <ul>
                <li><a href="admindashboard.php"><i class="fa-solid fa-gauge"></i> <span>Dashboard</span></a></li>
                <li class = "archive-text"><p>Archives</p></li>
                <li><a href="fire_types.php"><i class="fa-solid fa-fire-flame-curved"></i><span> Fire Types </span></a></li>
                <li><a href="barangay_list.php"><i class="fa-solid fa-building"></i><span> Barangay List </span></a></li>
                <li><a href="fire_incident_report.php"><i class="fa-solid fa-fire"></i><span> Fire Incident</span></a></li>
                <li><a href="fire_safety_inspection_certificate.php"><i class="fa-solid fa-fire-extinguisher"></i><span> Fire Safety</span></a></li>
                <li><a href="monthly_reports.php"><i class="fa-solid fa-box-archive"></i><span> Monthly Reports </span></a></li>
                <li><a href="reports_per_barangay.php"><i class="fa-solid fa-box-archive"></i><span> Reports Per Barangay</span></a></li>
                <li class="archive-text"><span>Maintenance</span></li>
                <li><a href="manageuser.php"><i class="fas fa-users"></i><span> Manage Users </span></a></li>
                <li><a href="setting.php"><i class="fa-solid fa-gear"></i> <span>Settings</span></a></li>
            </ul>
        </nav>
    </aside>

        <div class="main-content">
            <!-- Header with profile dropdown -->
            <header class="header">
            <button id="toggleSidebar" class="toggle-sidebar-btn">
                    <i class="fa-solid fa-bars"></i> <!-- Sidebar toggle icon -->
                </button>
                <h2>BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM</h2>

                <!-- Profile dropdown -->
                <div class="dropdown">
                    <a href="#" class="user-icon" onclick="toggleDropdown(event)">
                        <i class="fas fa-user-circle" style="font-size: 40px;"></i>
                        <p><?php echo $_SESSION['username']; ?></p> <!-- Display the logged-in username -->
                        <i class="fa-solid fa-caret-down"></i>
                    </a>
                    <div id="profileDropdown" class="dropdown-content">
                        <a href="viewProfile.php"><i class="fa-solid fa-user"></i> View Profile</a>
                        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </div>
                </div>
            </header>

            <!-- Notification Message -->
                    <?php if (!empty($message)): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
     

            <div id="notification" class="notification">
                <span id="notification-message"><?php echo $message; ?></span>
                <button class="close-btn" onclick="closeNotification()">&times;</button>
            </div>
            <?php endif; ?>

         <!-- Card for the Archive Section -->
  <div class="card">

<section class="archive-section">
    <h2>List of Archives</h2>
    <hr>
    <div class="filter">          
        <form action="myarchives.php" method="GET">
            <label for="department">Filter by Department:</label>
            <select name="department" onchange="this.form.submit()">
                <option value="">Select Department</option>
                <?php
                foreach ($departmentsFromDB as $department) {
                    echo "<option value='" . htmlspecialchars($department) . "' " . ($departmentFilter === $department ? 'selected' : '') . ">" . htmlspecialchars($department) . "</option>";
                }
                ?>
            </select>
        </form>
    </div>

    <div class="entries-search">
        <label>
            Show
            <select name="entries" class="custom-select">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            Entries
        </label>
        <form action="myarchives.php" method="GET">
            <label>
                Search:
                <input type="search" class="search-input" placeholder="Search..." oninput="filterArchives()">
            </label>
        </form>
    </div>

    <table class="archive-table">
<thead>
<tr>
    <th>Report ID</th>
    <th>Report Name</th>
    <th>Report Type</th>
    <th>Department</th>
    <th>Date Archived</th>
    <th>Uploader</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>
<?php
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['report_title']) . "</td>";
    echo "<td>" . htmlspecialchars($row['fileType']) . "</td>";
    echo "<td>" . htmlspecialchars($row['department']) . "</td>";
    echo "<td>" . htmlspecialchars($row['date_archived']) . "</td>";
    echo "<td>" . htmlspecialchars($row['uploader']) . "</td>";
    echo "<td>
    <div class='action-dropdown'>
        <button class='action-btn'>Actions <i class='fa fa-chevron-down'></i></button>
        <div class='dropdown-content'>
            <a href='viewArchive.php?id=" . htmlspecialchars($row['id']) . "'>View</a>
            <a href='update_report.php?id=" . htmlspecialchars($row['id']) . "'>Edit</a>
            <a href='deleteArchive.php?id=" . htmlspecialchars($row['id']) . "'>Delete</a>
        </div>
    </div>
</td>";        
    echo "</tr>";
}
?>
</tbody>
</table>

        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3> Confirmation </h3>
            <hr>
            <p>Are you sure you want to delete this archive?</p>
            <hr>
            <div class="modal-actions">
                <button id="confirmDelete" class="modal-btn">Confirm</button>
                <button id="cancelDelete" class="modal-btn">Cancel</button>
            </div>
        </div>
    </div>

    <script src = "../js/archivescript.js"></script>
    <script>
    // Function to toggle the dropdown visibility
document.addEventListener("DOMContentLoaded", function () {
    const dropdownButtons = document.querySelectorAll('.action-btn');

    dropdownButtons.forEach(button => {
        button.addEventListener('click', function () {
            const dropdownContent = this.nextElementSibling;
            dropdownContent.classList.toggle('show');
            const allDropdowns = document.querySelectorAll('.dropdown-content');
            allDropdowns.forEach(dropdown => {
                if (dropdown !== dropdownContent) {
                    dropdown.classList.remove('show');
                }
            });
        });
    });
});

</script>

</body>
</html>
