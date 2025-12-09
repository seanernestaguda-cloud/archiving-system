<?php
// session_start();
include('connection.php');
include('auth_check.php');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql_settings = "SELECT system_name FROM settings LIMIT 1";
$result_settings = $conn->query($sql_settings);
$system_name = 'BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM';
if ($result_settings && $row_settings = $result_settings->fetch_assoc()) {
    $system_name = $row_settings['system_name'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['fire_types'], $_POST['description']) && !isset($_POST['fire_types_id'])) {
    $fire_types = htmlspecialchars(strip_tags($_POST['fire_types']));
    $description = htmlspecialchars(strip_tags($_POST['description']));

    $insertQuery = "INSERT INTO fire_types (fire_types, description) VALUES (?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param('ss', $fire_types, $description);

    if ($stmt->execute()) {
        $_SESSION['message'] = 'Fire type added successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error adding Fire Type. Please try again.';
        $_SESSION['message_type'] = 'error';
    }

    $stmt->close();
    header("Location: fire_types.php");
    exit();
}

$username = $_SESSION['username'];
$avatar = '../avatars/default_avatar.png';

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT avatar FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result_user = $stmt->get_result();

if ($result_user && $row_user = $result_user->fetch_assoc()) {
    if (!empty($row_user['avatar']) && file_exists('../avatars/' . $row_user['avatar'])) {
        $avatar = '../avatars/' . $row_user['avatar'];
    }
}
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['fire_types'], $_POST['description'], $_POST['fire_types_id'])) {
    $fire_types = htmlspecialchars(strip_tags($_POST['fire_types']));
    $description = htmlspecialchars(strip_tags($_POST['description']));
    $fire_types_id = $_POST['fire_types_id'];

    $updateQuery = "UPDATE fire_types SET fire_types = ?, description = ? WHERE fire_types_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('ssi', $fire_types, $description, $fire_types_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = 'Fire type updated successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error updating Fire Type. Please try again.';
        $_SESSION['message_type'] = 'error';
    }

    $stmt->close();
    header("Location: fire_types.php");
    exit();
}

// Delete Fire Type
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_fire_type'])) {
    $fire_types_id = $_POST['fire_types_id'];

    $deleteQuery = "DELETE FROM fire_types WHERE fire_types_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param('i', $fire_types_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = 'Fire type deleted successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error deleting Fire Type. Please try again.';
        $_SESSION['message_type'] = 'error';
    }

    $stmt->close();
    header("Location: fire_types.php");
    exit();
}

// Fetch Fire Types
$query = "SELECT * FROM fire_types ORDER BY fire_types";
$result = mysqli_query($conn, $query);
$fire_types = mysqli_fetch_all($result, MYSQLI_ASSOC);

$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

$count_query = "SELECT COUNT(*) as total FROM fire_types";
$count_result = mysqli_query($conn, $count_query);
$total_reports = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_reports / $per_page);

$query = "SELECT * FROM fire_types ORDER BY fire_types LIMIT $per_page OFFSET $offset";
$result = mysqli_query($conn, $query);
$fire_types = mysqli_fetch_all($result, MYSQLI_ASSOC);
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
    <title>Causes of Fire</title>
    <style>
        .entries-search {
            margin: 10px;
            display: flex;
            justify-content: space-between;
            /* Spread left and right */
            align-items: center;
            gap: 10px;
        }

        .entries-right {
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
            background: linear-gradient(90deg, rgba(0, 0, 0, 0.08), rgba(0, 0, 0, 0.18), rgba(0, 0, 0, 0.08));
            border: none;
            margin: 12px 0 20px;
            width: calc(100% + 40px);
            /* expand across left+right padding (2 * 20px) */
            margin-left: -20px;
            /* shift left by container padding */
            box-sizing: border-box;
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
            <!-- Card for the Archive Section -->
            <div class="card">

                <section class="archive-section">
                    <h3>Causes of Fire Classifications</h3>
                    <p> List of Fire Causes </p>
                    <hr class="section-separator full-bleed">
                    <div class="top-controls">
                        <button onclick="openModal('addFireTypesModal')" class="create-new-button">
                            <i class="fa-solid fa-circle-plus"></i>Add New Fire Cause</button>
                    </div>
                    <hr class="section-separator full-bleed">

                    <div class="entries-right">
                        <div class="search-input-container">
                            <form method="GET" style="display:inline;" id="searchForm" onsubmit="return false;">
                                <input type="search" name="search" class="search-input" placeholder="Search..."
                                    autocomplete="off">
                                <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                            </form>
                        </div>
                    </div>

                    <table class="archive-table">
                        <thead>
                            <tr>
                                <th>Fire Cause</th>
                                <th>Description</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="fireTypesTableBody">
                            <?php foreach ($fire_types as $fire_type): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fire_type['fire_types']); ?></td>
                                    <td><?php echo htmlspecialchars($fire_type['description']); ?></td>
                                    <td class="action-button-container">
                                        <form action="fire_types.php" method="POST" style="display:flex;">
                                            <input type="hidden" name="fire_types_id"
                                                value="<?php echo $fire_type['fire_types_id']; ?>">
                                            <button type="button"
                                                onclick="confirmDelete(<?php echo $fire_type['fire_types_id']; ?>)"
                                                class="delete-btn"><i class="fa-solid fa-trash"></i></button>
                                            <button type="button"
                                                onclick="openEditModal(<?php echo $fire_type['fire_types_id']; ?>, '<?php echo htmlspecialchars($fire_type['fire_types']); ?>', '<?php echo htmlspecialchars($fire_type['description']); ?>')"
                                                class="edit-btn"><i class="fa-solid fa-pen-to-square"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($total_pages > 1): ?>
                        <div id="paginationContainer" class="pagination" style="margin: 20px 0; text-align: center;">
                            <?php if ($page > 1): ?>
                                <a href="?<?php
                                $params = $_GET;
                                $params['page'] = $page - 1;
                                echo http_build_query($params);
                                ?>" class="pagination-btn">&laquo; Prev</a>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?<?php
                                $params = $_GET;
                                $params['page'] = $i;
                                echo http_build_query($params);
                                ?>" class="pagination-btn<?php if ($i == $page)
                                    echo ' active'; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            <?php if ($page < $total_pages): ?>
                                <a href="?<?php
                                $params = $_GET;
                                $params['page'] = $page + 1;
                                echo http_build_query($params);
                                ?>" class="pagination-btn">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div id="addFireTypesModal" class="report-details-modal">
                        <div class="modal-content">
                            <span class="close-btn" onclick="closeModal('addFireTypesModal')">&times;</span>
                            <h3>Add New Fire Cause</h3>
                            <form id="addFireTypes" method="POST" action="fire_types.php">
                                <label for="fire_types">Cause of fire:</label>
                                <input type="text" id="fire_types" name="fire_types" placeholder="Enter Fire Type"
                                    required>

                                <label for="description">Description:</label>
                                <textarea id="description" name="description" placeholder="Enter Description"
                                    required></textarea>

                                <button type="submit" class="action-button">Save</button>
                            </form>
                        </div>
                    </div>

                    <div id="editFireTypesModal" class="report-details-modal">
                        <div class="modal-content">
                            <span class="close-btn" onclick="closeModal('editFireTypesModal')">&times;</span>
                            <h3>Edit Fire Type</h3>
                            <form id="editFireTypeForm" method="POST" action="fire_types.php">
                                <input type="hidden" id="edit_fire_types_id" name="fire_types_id">

                                <label for="edit_fire_types">Fire Type:</label>
                                <input type="text" id="edit_fire_types" name="fire_types" required>

                                <label for="edit_description">Description:</label>
                                <textarea id="edit_description" name="description" required rows="10"
                                    cols="30"></textarea>

                                <button type="submit" class="action-btn">Save Changes</button>
                            </form>
                        </div>
                    </div>

                    <!-- Confirm Delete Modal -->
                    <div id="confirmDeleteModal" class="confirm-delete-modal">
                        <div class="modal-content">
                            <h3>Confirm Delete?</h3>
                            <hr>
                            <p> Are you sure you want to delete?</p>
                            <form action="fire_types.php" method="POST" id="deleteForm">
                                <input type="hidden" name="fire_types_id" id="deleteFireTypeId">
                                <button type="submit" name="delete_fire_type" class="confirm-btn">Confirm</button>
                                <button type="button" onclick="cancelDelete()" class="cancel-btn">Cancel</button>
                            </form>
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
                        // Close modal when clicking outside of it
                        window.onclick = function (event) {
                            const modal = document.getElementById('addFireTypesModal');
                            const editModal = document.getElementById('editFireTypesModal');
                            const deleteModal = document.getElementById('confirmDeleteModal');

                            if (event.target === modal || event.target === editModal || event.target === deleteModal) {
                                closeModal();
                            }
                        };

                        function openEditModal(fireTypesId, fireTypesName, fireTypesDescription) {
                            document.getElementById('edit_fire_types_id').value = fireTypesId;
                            document.getElementById('edit_fire_types').value = fireTypesName;
                            document.getElementById('edit_description').value = fireTypesDescription;
                            document.getElementById('editFireTypesModal').style.display = 'flex';
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
                        function confirmDelete(fireTypesId) {
                            const deleteForm = document.getElementById('deleteForm');
                            const deleteFireTypeId = document.getElementById('deleteFireTypeId');

                            deleteFireTypeId.value = fireTypesId;
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

                        document.addEventListener('DOMContentLoaded', function () {
                            const searchInput = document.querySelector('.search-input');
                            const tableBody = document.getElementById('fireTypesTableBody');
                            const paginationContainer = document.getElementById('paginationContainer');

                            if (searchInput && tableBody) {
                                let searchTimeout;
                                searchInput.addEventListener('input', function () {
                                    clearTimeout(searchTimeout);
                                    searchTimeout = setTimeout(function () {
                                        const query = searchInput.value;
                                        if (query.trim() === '') {
                                            // If search is cleared, reload the page to restore pagination
                                            window.location.href = 'fire_types.php';
                                        } else {
                                            fetch('fire_types_ajax.php?search=' + encodeURIComponent(query))
                                                .then(response => response.text())
                                                .then(html => {
                                                    tableBody.innerHTML = html;
                                                    if (paginationContainer) {
                                                        paginationContainer.style.display = 'none';
                                                    }
                                                });
                                        }
                                    }, 200); // Debounce for 200ms
                                });
                            }
                        });
                    </script>

</body>

</html>
<script src="../js/archivescript.js"></script>