<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: adminlogin.php"); // Redirect to login if not logged in
    exit();
}

// Include database connection
include 'connection.php';

$query = "SELECT id, avatar, first_name, last_name, username, department, user_type, status FROM users";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="reportstyle.css">
    <link rel="stylesheet" href="manageuser.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Users</title>
    <style>
        
.modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
    z-index: 1000; /* Make sure it stays on top */
    display: flex; /* Use Flexbox */
    align-items: center; /* Vertically center */
    justify-content: center; /* Horizontally center */
}

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 300px;
            text-align: center;
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

        /* Dropdown CSS */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none; /* Hide the dropdown by default */
            position: absolute;
            background-color: white;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
        }

        .dropdown-content.show {
            display: block; /* Show the dropdown when it has the 'show' class */
        }

        .dropdown-content a {
            color: black;
            padding: 8px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #ddd;
        }

        /* Notification CSS */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4CAF50; /* Green */
            color: white;
            padding: 15px;
            border-radius: 5px;
            z-index: 1000;
            display: none; /* Hide by default */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.5s; /* Fade-in effect */
        }

        .notification .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 16px;
            margin-left: 15px;
            cursor: pointer;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>

<div class="dashboard">
 <aside class="sidebar">
        <nav>
            <ul>
                <li class = "archive-text"><h4>BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM</h4></li>
                <li><a href="admindashboard.php"><i class="fa-solid fa-gauge"></i> <span>Dashboard</span></a></li>
                <li class = "archive-text"><p>Archives</p></li>
                <li><a href="fire_types.php"><i class="fa-solid fa-fire-flame-curved"></i><span> Causes of Fire </span></a></li>
                <li><a href="barangay_list.php"><i class="fa-solid fa-building"></i><span> Barangay List </span></a></li>
                <li><a href="archives.php"><i class="fa-solid fa-fire"></i><span> Archives </span></a></li>
            
                <li class="report-dropdown">
                    <a href="#" class="report-dropdown-toggle">
                        <i class="fa-solid fa-box-archive"></i>
                        <span>Reports</span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                    <ul class="report-dropdown-content">
                        <li><a href="monthly_reports.php"><i class="fa-regular fa-calendar"></i> Monthly Reports</a></li>
                        <li><a href="barangay_reports.php"><i class="fa-solid fa-city"></i> Barangay Reports </a></li>
                        <li><a href="reports_per_barangay.php"><i class="fa-solid fa-chart-column"></i> Reports per Barangay</a></li>
                        <li><a href="monthly_reports_chart.php"><i class="fa-solid fa-chart-column"></i> Reports per Month </a></li>
                        <li><a href="year_to_year_comparison.php"><i class="fa-regular fa-calendar-days"></i> Year to Year Comparison </a></li>
                    </ul>
                </li>
                
                <li class="archive-text"><span>Maintenance</span></li>
                <li><a href="departments.php"><i class="fas fa-users"></i><span> Department List </span></a></li>
                <li><a href="manageuser.php"><i class="fas fa-users"></i><span> Manage Users </span></a></li>
                <li><a href="setting.php"><i class="fa-solid fa-gear"></i> <span>Settings</span></a></li>
            </ul>
        </nav>
    </aside>
    <div class="main-content">
        <header class="header">
            <h2>BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM</h2>
            <div class="dropdown">
                <a href="#" class="user-icon" onclick="toggleDropdown(event, 'profileDropdown')">
                    <i class="fas fa-user-circle" style="font-size: 40px;"></i>
                    <p><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                </a>
                <div id="profileDropdown" class="dropdown-content">
                    <a href="viewProfile.php"><i class="fa-solid fa-user"></i> View Profile</a>
                    <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </header>

        <!-- Notification div -->
        <div id="notification" class="notification">
            <span id="notification-message">
                <?php
                    if (isset($_SESSION['message'])) {
                        echo $_SESSION['message'];
                        unset($_SESSION['message']); // Clear the message after displaying
                    }
                ?>
            </span>
            <button class="close-btn" onclick="closeNotification()">&times;</button>
        </div>

        <h2>List of Users</h2>
        <hr>

        <table>
            <thead>
            <div class="entries-search">
                        <label for="">Show<select name="DataTables_Table_0_length" aria-controls="DataTables_Table_0" class="custom-select custom-select-sm form-control form-control-sm">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        Entries
                        </label>
                        <label for="Search"> Search:
                        <input type="search" class="search-input" placeholder="Search...">
                        </label>
                    </div>
                <tr>
                    <th>Avatar</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>User Type</th> <!-- Ensure this header exists -->
                    <th>Department</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
<?php
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $avatar = $row['avatar'] ?: 'default-avatar.png';
            $fullName = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
            $username = htmlspecialchars($row['username']);
            $user_type = htmlspecialchars($row['user_type']);
            $department = htmlspecialchars($row['department']);
            $status = htmlspecialchars($row['status']);
            $userId = htmlspecialchars($row['id']);

            echo "<tr>
                <td><img src='uploads/$avatar' alt='Avatar' width='50' height='50'></td>
                <td>$fullName</td>
                <td>$username</td>
                <td>$user_type</td>
                <td>$department</td>
                <td>$status</td>
                <td>
                    <div class='dropdown'>
                        <button class='dropdown-btn' onclick='toggleDropdown(event, \"actionDropdown$userId\")'>Action<i class='fa-solid fa-caret-down'></i></button>
                        <div id='actionDropdown$userId' class='dropdown-content'>";


            echo "      <a href='view_user.php?id=$userId'><i class='fa-solid fa-eye'></i> view</a>
                        <a href='#' onclick='confirmDelete($userId)'><i class='fas fa-trash'></i> Delete</a>
                        </div>
                    </div>
                </td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='7'>No users found.</td></tr>";
    }
?>
</tbody>
        </table>
    </div>
</div>

<div id="confirmationModal" class="confirm-delete-modal">
    <div class="modal-content">
        <h3>Confirmation</h3>
        <hr>
        <span class="close" onclick="closeModal()">&times;</span>
        <p>Are you sure you want to delete this user?</p>
        <hr>
        <button id="confirmDelete">Confirm</button>
        <button onclick="closeModal()">Cancel</button>
    </div>
</div>

<script src = "../js/reportscript.js"></script>
<script src = "../js/archivescript.js"></script>
<script>
    function toggleDropdown(event, dropdownId) {
        event.preventDefault();
        const dropdown = document.getElementById(dropdownId);
        dropdown.classList.toggle("show");
    }

    window.onclick = function(event) {
        // Close the dropdowns if the user clicks outside of them
        const dropdowns = document.querySelectorAll('.dropdown-content');
        dropdowns.forEach(dropdown => {
            if (!event.target.matches('.user-icon') && !event.target.matches('.user-icon *') &&
                !event.target.matches('.dropdown-btn') && !event.target.matches('.dropdown-btn *')) {
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        });
    }

    let userIdToDelete = null;

    function confirmDelete(userId) {
        userIdToDelete = userId;
        document.getElementById("confirmationModal").style.display = "flex";
    }

    function closeModal() {
        document.getElementById("confirmationModal").style.display = "none";
    }

    document.getElementById("confirmDelete").onclick = function() {
        if (userIdToDelete) {
            window.location.href = 'delete_user.php?id=' + userIdToDelete;
        }
    };

    // Show the notification if it exists
    window.onload = function() {
        const notification = document.getElementById('notification');
        const message = document.getElementById('notification-message');
        
        if (message.innerText.trim() !== '') {
            notification.style.display = 'block'; // Show the notification
            setTimeout(() => {
                notification.style.display = 'none'; // Hide after 3 seconds
            }, 3000);
        }
    };

    function closeNotification() {
        document.getElementById('notification').style.display = 'none'; // Close notification on button click
    }
</script>
</body>
</html>
