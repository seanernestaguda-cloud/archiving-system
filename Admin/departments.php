    <?php
// session_start();
include('connection.php');
include('auth_check.php');


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['departments']) && !isset($_POST['departments_id'])) {
    $departments = htmlspecialchars(strip_tags($_POST['departments']));

    $insertQuery = "INSERT INTO departments (departments) VALUES (?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param('s', $departments);

    if ($stmt->execute()) {
        $_SESSION['message'] = 'Department added!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error adding Department. Please try again.';
        $_SESSION['message_type'] = 'error';
    }

    $stmt->close();
    header("Location: departments.php");
    exit();
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['departments']) && isset($_POST['departments_id'])) {
    $departments = htmlspecialchars(strip_tags($_POST['departments']));
    $departments_id = $_POST['departments_id'];

    $updateQuery = "UPDATE departments SET departments = ? WHERE departments_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('si', $departments, $departments_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = 'Department updated!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error updating Departmemt. Please try again.';
        $_SESSION['message_type'] = 'error';
    }

    $stmt->close();
    header("Location: departments.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_department'])) {
    $departments_id = $_POST['departments_id'];

    $deleteQuery = "DELETE FROM departments WHERE departments_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param('i', $departments_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = 'Department deleted!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error deleting Department. Please try again.';
        $_SESSION['message_type'] = 'error';
    }

    $stmt->close();
    header("Location: departments.php");
    exit();
}


$query = "SELECT * FROM departments ORDER BY departments ASC";
$result = mysqli_query($conn, $query);
$departments = mysqli_fetch_all($result, MYSQLI_ASSOC);

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
    <link rel="stylesheet" href="reportstyle.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="icon" type="image/png" href="../REPORT.png">
    <title>Departments</title>
    <style>


/* Modal Styles */
.report-details-modal {
display: none; /* Hidden by default */
position: fixed;
top: 0;
left: 0;
width: 100%;
height: 100%;
background-color: rgba(0, 0, 0, 0.7); /* Semi-transparent background */
z-index: 1000;
padding: 20px; /* Ensures space around modal */
align-items: center; /* Center modal content vertically */
justify-content: center; /* Center modal content horizontally */
}


/* Modal Content Styles */
.modal-content {
background-color: #ffffff; /* White background for content */
color: #333333; /* Dark text for good contrast and readability */
padding: 30px; /* More padding for a formal look */
border-radius: 8px;
width: 30%; /* Adjust width for better visual balance */
max-height: 80%; /* Limit height */
overflow-y: auto; /* Allow scrolling if content overflows */
box-sizing: border-box;
position: relative; /* Position for the close button */
border: 2px solid #003D73; /* Dark teal border for a formal, professional feel */
box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1); /* Soft shadow for depth */
font-family: 'Arial', sans-serif; /* Clean, professional font */
}

/* Close Button Style */
.close-btn {
font-size: 28px;
color: #00796b; /* Teal color for consistency with the theme */
position: absolute;
top: 10px;
right: 10px;
cursor: pointer;
transition: color 0.3s ease; /* Smooth transition for hover effect */
}

.close-btn:hover {
color: #d32f2f; /* Red color on hover for a clear indication */
}

/* Modal Title (h3) Style */
.modal-content h3 {
margin-top: 0;
font-size: 20px;
color: #003D73;
font-weight: 600; /* Make the title bold */
margin-bottom: 10px; /* Space below the title */
text-align: center; /* Left-align the header */
}

/* Paragraph Style */
.modal-content p {
font-size: 16px;
line-height: 1.6;
color: #555; /* Slightly lighter text for body content */
text-align: left; /* Left-align the paragraph text */
margin-bottom: 15px; /* Add space between paragraphs */
}

/* For Details Section */
.modal-content .details-section {
margin-top: 20px;
margin-bottom: 5px;
}

/* Buttons Inside Modal (e.g., if you have buttons for actions) */
.modal-content .action-button {
background-color: #003D73;
color: white;   
padding: 12px 24px;
border: none;
border-radius: 5px;
cursor: pointer;
font-size: 16px;
transition: background-color 0.3s ease; /* Smooth background change */
}

.modal-content .action-button:hover {
background-color: #011e38;
}

/* Scrollable Modal Content */
.report-details-modal .modal-content {
max-height: 100vh; /* 80% of the viewport height */
overflow-y: auto; /* Scroll if content exceeds this height */
}


/* Adding Hover Effect to Modal Content */
.modal-content:hover {
box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.15); /* Subtle hover effect for depth */
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

.top-controls {

display: flex;
justify-content: flex-start; /* Aligns items to the right */
align-items: center; /* Center vertically */
margin-bottom: 10px; /* Space below */
}

.create-new-button i {
margin-right: 10px;
font-size: 16px;
}

.create-new-button:hover {
background-color: #011e38; /* Darker green on hover */
transition: background-color 0.3s ease; /* Smooth transition */
}

.edit-btn{
color: white;
background-color:#003D73;
border-style: hidden;
margin: 0;                 /* Add space between buttons */
padding: 10px 12px;              /* Adjust padding as needed */
font-size: 14px;                /* Font size */
border-radius: 0px;             /* Rounded corners */
cursor: pointer;  
}

.edit-btn:hover{
background-color: #011e38;
}

.delete-btn{
color: white;
background-color:#bd000a;
border-style: hidden;
margin: 0;                 /* Add space between buttons */
padding: 10px 12px;              /* Adjust padding as needed */
font-size: 14px;                /* Font size */
border-radius: 0px;             /* Rounded corners */
cursor: pointer;  
}

.delete-btn:hover{
background-color: #810000;
}

.card h2{
margin: 10px;
}

h4{
text-align: left;
padding-bottom: 10px;
border-bottom:1px solid #444;
}
     .entries-search {
                margin: 10px;
                display: flex;
                justify-content: space-between; /* Spread left and right */
                align-items: center;
                gap: 10px;
            }

            .entries-right{
                display: flex;
                justify-content: flex-end;
                align-items: center;
            }

            .search-input-container {
            position: relative;
            display: inline-block;
            }

            .entries-right .search-input {
                width: 220px;
                padding-left: 30px;
                background-size: 16px;
            }

            .search-input {
                width: 220px;
                padding-left: 32px;
                 padding: 5px 10px;
                font-size: 14px;
                }

          .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            pointer-events: none;
            }
.section-separator.full-bleed {
    height: 1px;
    background: linear-gradient(90deg, rgba(0,0,0,0.08), rgba(0,0,0,0.18), rgba(0,0,0,0.08));
    border: none;
    margin: 12px 0 20px;
    width: calc(100% + 40px); /* expand across left+right padding (2 * 20px) */
    margin-left: -20px;        /* shift left by container padding */
    box-sizing: border-box;
}
</style>
    </head>
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
    <!-- Card for the Archive Section -->
    <div class="card">

            <section class="archive-section">
            <h3>Departments</h3>
            <p> List of Departments </p>
            <hr class="section-separator full-bleed">
            <div class="top-controls">
            <button onclick="openModal('addDepartmentModal')" class="create-new-button"><i class="fa-solid fa-circle-plus"></i>Add New Department</button>
            </div>
           <hr class="section-separator full-bleed">
        <table class = "archive-table">
      <div class="entries-right">
  <div class="search-input-container">
    <form method="GET" style="display:inline;" id="searchForm">
      <input type="search" name="search" class="search-input" placeholder="Search..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
      <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
    </form>
  </div>
</div>
        <thead>
            <tr>
                <th> Department </th>
               <th> Action </th>
            </tr>
        </thead>
            <tbody id="departmentsTableBody">
        <?php foreach ($departments as $departments): ?>
            <tr>
                <td><?php echo htmlspecialchars($departments['departments']); ?></td>
                <td class = "action-button-container">
                    <form action="departments.php" method="POST" style="display:inline;">
                        <input type="hidden" name="departments" value="<?php echo $departments['departments_id']; ?>">
                        <button type="button" onclick="confirmDelete(<?php echo $departments['departments_id']; ?>)" class="delete-btn"><i class="fa-solid fa-trash"></i></button>
                        <button type="button" onclick="openEditModal(<?php echo $departments['departments_id']; ?>, '<?php echo htmlspecialchars($departments['departments']); ?>')" class="edit-btn"><i class="fa-solid fa-pen-to-square"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>

<div id="addDepartmentModal" class="report-details-modal">
    <div class="modal-content">
    <span class="close-btn" onclick="closeModal('addDepartmentModal')">&times;</span>
        <h3>Add New Department</h3>
        <form id="addDepartment" method="POST" action="departments.php">
            <label for="departments">Department:</label>
            <input type="text" id="departments" name="departments" placeholder="Enter Department Name" required>
            <button type="submit" class="action-button">Add Department</button>
        </form>
    </div>
</div>

<div id="editDepartmentModal" class="report-details-modal">
    <div class="modal-content">
    <span class="close-btn" onclick="closeModal('editDepartmentModal')">&times;</span>
        <h3>Edit Department Name</h3>
        <form id="editDepartmentForm" method="POST" action="departments.php">
        <input type="hidden" id="edit_departments_id" name="departments_id">

            <label for="edit_departments">Department:</label>
            <input type="text" id="edit_departments" name="departments" required>
            <button type="submit" class="action-btn">Save Changes</button>
        </form>
    </div>
</div>

 <!-- Confirm Delete Modal -->
 <div id="confirmDeleteModal" class="confirm-delete-modal">
            <div class="modal-content">
                <h3> Confirm Delete? </h3>
                <hr>
                <p>Are you sure you want to delete this department?</p>
                <form action="departments.php" method="POST" id="deleteForm">
                    <input type="hidden" name="departments_id" id="deleteDepartmentId">
                    <button type="submit" name="delete_department" class="confirm-btn">Yes, Delete</button>
                    <button type="button" onclick="cancelDelete()" class="cancel-btn">Cancel</button>
                </form>
            </div>
        </div>
        
<div id="successModal" class="success-modal">
    <div class="success-modal-content">
        <i class="fa-regular fa-circle-check"></i> <h2>Success!</h2>
        <p id="successMessage"></p>
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

window.onclick = function(event) {
    const modal = document.getElementById('addDepartmentModal');
    const editModal = document.getElementById('editDepartmentModal');
    const deleteModal = document.getElementById('confirmDeleteModal');

    if (event.target === modal || event.target === editModal || event.target === deleteModal) {
        closeModal();
    }
};

// Function to open the edit modal with existing data
function openEditModal(DepartmentId, DepartmentName) {
    document.getElementById('edit_departments_id').value = DepartmentId;
    document.getElementById('edit_departments').value = DepartmentName;
    document.getElementById('editDepartmentModal').style.display = 'flex';
}

// Function to open the add modal
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

// Function to close the modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Function to confirm delete
function confirmDelete(DepartmentId) {
    const deleteForm = document.getElementById('deleteForm');
    const deleteDepartmentId = document.getElementById('deleteDepartmentId');

    deleteDepartmentId.value = DepartmentId;
    document.getElementById('confirmDeleteModal').style.display = 'flex';
}

// Function to cancel the deletion
function cancelDelete() {
    document.getElementById('confirmDeleteModal').style.display = 'none';
}

   
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
    // Check if there's a session message and show modal if it's a success
    const message = '<?php echo isset($_SESSION['message']) ? $_SESSION['message'] : ''; ?>';
    const messageType = '<?php echo isset($_SESSION['message_type']) ? $_SESSION['message_type'] : ''; ?>';

    if (message && messageType === 'success') {
        document.getElementById('successMessage').textContent = message;
        openSuccessModal();
        // Clear session message after showing
        <?php unset($_SESSION['message']); ?>
        <?php unset($_SESSION['message_type']); ?>
    }
});

// Open success message modal
function openSuccessModal() {
    document.getElementById('successModal').style.display = 'block';

    // Automatically close the modal after 2 seconds
    setTimeout(() => {
        closeSuccessModal();
    }, 2000); // 2000 milliseconds = 2 seconds
}

// Close success message modal
function closeSuccessModal() {
    document.getElementById('successModal').style.display = 'none';
}
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search-input');
    const departmentsTableBody = document.getElementById('departmentsTableBody');
    const searchForm = document.getElementById('searchForm');

    function fetchDepartments(query) {
        if (query === '') {
            window.location.href = window.location.pathname + window.location.search.replace(/([?&])search=[^&]*/g, '');
        } else {
            fetch(`departments_ajax.php?search=${encodeURIComponent(query)}`)
                .then(response => response.text())
                .then(html => {
                    departmentsTableBody.innerHTML = html;
                });
        }
    }

    if (searchInput && departmentsTableBody) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                fetchDepartments(searchInput.value);
            }, 0); // instant update
        });
    }

    if (searchForm && searchInput && departmentsTableBody) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            fetchDepartments(searchInput.value);
        });
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

    <script src = "../js/archivescript.js">
    </script>