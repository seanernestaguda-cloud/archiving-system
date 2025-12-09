<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: userlogin.php"); // Redirect to login if not logged in
    exit();
}

include('connection.php'); // Include database connection

// Check if 'id' is provided in the query string
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "Invalid archive selected.";
    header("Location: myarchives.php");
    exit();
}

$id = intval($_GET['id']); // Get the archive ID and sanitize input

// Fetch the archive metadata (report details) from the database
$query = "SELECT id, report_title, fire_location, incident_date, establishment, victims, property_damage, fire_origin, fire_cause, uploader FROM archives WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// Check if the archive exists
if ($result->num_rows === 0) {
    $_SESSION['message'] = "Archive not found.";
    header("Location: myarchives.php");
    exit();
}

$archive = $result->fetch_assoc(); // Fetch the metadata
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="userarchivestyle.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <title>View Archive</title>
    <style> /* Basic Reset */


/* Header Styles */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color:#ffa700;
    padding: 20px;
}

.header h2 {
    font-size: 15px;
    margin: 0;
}

.header-right {
    display: flex;
    align-items: center;
}

.user-icon {
    text-decoration: none;
    color: black;
    display: flex;
    align-items: center;
}

.user-icon p {
    margin-left: 10px;
    font-size: 15px;
}

.fa-caret-down{
    margin: 5px;
}

.user-icon p {
    font-weight: bold;
    color: black;
    margin-left: 10px;
    font-size: 18px;
}

.user-icon i{
    color: black;
}

.card {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    padding: 20px;
    margin: 20px;
    overflow: hidden;
    border-top: solid 3px #003D73;
}

/* Dropdown Menu */
.dropdown-content {
    display: none;
    position: absolute;
    right: 20px;
    background-color: #fff;
    border: 1px solid #ccc;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    z-index: 1;
}

.dropdown-content a {
    color: #333;
    padding: 10px;
    text-decoration: none;
    display: block;
}

.dropdown-content a:hover {
    background-color: #f1f1f1;
}

.dropdown-content.show {
    display: block;
}

/* Archive Details Section */
.archive-details {
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
}

.archive-details p {
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 10px;
}

.archive-details strong {
    color: #333;
}

/* Back Button */
.btn-back {
    display: inline-block;
    padding: 10px 15px;
    background-color: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-top: 20px;
    font-size: 16px;
}

.btn-back:hover {
    background-color: #2980b9;
}

/* Responsive Design */
@media (max-width: 768px) {
    .main-content {
        margin-left: 220px;
    }

    .header h2 {
        font-size: 20px;
    }

    .archive-details p {
        font-size: 14px;
    }

    .btn-back {
        font-size: 14px;
    }
}
</style>
</head>
<body>
<div class="dashboard">
<aside class="sidebar">
    <nav>
        <ul>
            <li><a href="userdashboard.php"><i class="fa-solid fa-gauge"></i> <span>Dashboard</span></a></li>
            <li class = "archive-text"><p> Manage Archives</p></li>
            <li><a href="myarchives.php"><i class="fa-solid fa-box-archive"></i><span> Archives </span></a></li>
            <li class = "archive-text"><p> Reports</p></li>
            <li><a href="fire_incident_report.php"><i class="fa-solid fa-box-archive"></i><span> Fire Incident Report</span></a></li>
            <li><a href="myprofile.php"><i class="fa-solid fa-user"></i><span> My Profile </span></a></li>
            <li><a href="settings.php"><i class="fa-solid fa-gear"></i><span> Settings </span></a></li>
        </ul>
    </nav>
</aside>

        <div class="main-content">
        <header class="header">
    <button id="toggleSidebar" class="toggle-sidebar-btn">
                    <i class="fa-solid fa-bars"></i> <!-- Sidebar toggle icon -->
                </button>
        <h2>BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM</h2>
        <div class="header-right">
            <div class="dropdown">
                <a href="#" class="user-icon" onclick="toggleProfileDropdown(event)">
                    <i class="fas fa-user-circle" style="font-size: 40px;"></i>
                    <p><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                </a>
                <div id="profileDropdown" class="dropdown-content">
                    <a href="viewProfile.php"><i class="fa-solid fa-user"></i> View Profile</a>
                    <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>
            <div class = "card">
            <h2>View Archive Report Metadata</h2>
            <hr>
            <div class="archive-details">
                <!-- Report Metadata Display -->
                <p><strong>Report Title:</strong> <?php echo htmlspecialchars($archive['report_title'] ?? 'No report title provided.'); ?></p>
                <p><strong>Location of Fire:</strong> <?php echo htmlspecialchars($archive['fire_location'] ?? 'Location not provided.'); ?></p>
                <p><strong>Fire Date:</strong> <?php echo $archive['incident_date'] ? date('m-d-Y h:i:s:A', strtotime($archive['incident_date'])) : 'Date not available'; ?></p>
                <p><strong>Establishment:</strong> <?php echo htmlspecialchars($archive['establishment'] ?? 'Establishment not available.'); ?></p>
                <p><strong>Victims:</strong> <?php echo htmlspecialchars($archive['victims'] ?? 'No victims provided.'); ?></p>
                <p><strong>Damage to Property:</strong> <?php echo htmlspecialchars($archive['property_damage'] ?? 'Establishment not available.'); ?></p>
                <p><strong>Origin of Fire:</strong> <?php echo htmlspecialchars($archive['fire_origin'] ?? 'Origin not provided.'); ?></p>
                <p><strong>cause of Fire:</strong> <?php echo htmlspecialchars($archive['fire_cause'] ?? 'Cause not provided.'); ?></p>
                <p><strong>Report Author:</strong> <?php echo htmlspecialchars($archive['uploader'] ?? 'Author not available.'); ?></p>
            </div>
            
            <a href="myarchives.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Archives</a>
        </div>
    </div>
    </div>
</body>
</html>

<script src = "../js/archivescript.js"></script>
<script>
    // Toggle profile dropdown visibility
    function toggleDropdown(event) {
        event.preventDefault();
        document.getElementById("profileDropdown").classList.toggle("show");
    }
</script>
