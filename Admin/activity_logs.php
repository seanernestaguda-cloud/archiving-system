<?php
include('connection.php');
include('auth_check.php');

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

$sql_dept = "SELECT department FROM users WHERE username = ? LIMIT 1";
$stmt_dept = $conn->prepare($sql_dept);
$stmt_dept->bind_param('s', $username);
$stmt_dept->execute();
$result_dept = $stmt_dept->get_result();
$department = '';
if ($result_dept && $row_dept = $result_dept->fetch_assoc()) {
    $department = $row_dept['department'];
}

$per_page = 10; // Number of logs per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Search logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = '';
$params = [];
$types = '';

if ($search !== '') {
    $search_sql = "WHERE activity_logs.username LIKE ? OR activity_logs.action LIKE ? OR activity_logs.details LIKE ? OR activity_logs.id LIKE ? OR activity_logs.report_id LIKE ? OR users.user_type LIKE ? OR users.department LIKE ?";
    $search_param = '%' . $search . '%';
    $params = [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param];
    $types = 'sssssss';

    // Get total logs count with search
    $total_reports_query = "SELECT COUNT(*) AS total FROM activity_logs LEFT JOIN users ON activity_logs.username = users.username $search_sql";
    $stmt_count = $conn->prepare($total_reports_query);
    $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $total_reports_result = $stmt_count->get_result();
    $total_reports_row = $total_reports_result->fetch_assoc();
    $total_reports = $total_reports_row['total'];
    $stmt_count->close();

    // Fetch logs for current page with search (JOIN users for department and user_type)
    $query = "SELECT activity_logs.*, users.department, users.user_type AS user_type FROM activity_logs LEFT JOIN users ON activity_logs.username = users.username $search_sql ORDER BY activity_logs.timestamp ASC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $types .= 'ii';
    $stmt_logs = $conn->prepare($query);
    $stmt_logs->bind_param($types, ...$params);
    $stmt_logs->execute();
    $result = $stmt_logs->get_result();
    $stmt_logs->close();
} else {
    // Get total logs count
    $total_reports_query = "SELECT COUNT(*) AS total FROM activity_logs";
    $total_reports_result = mysqli_query($conn, $total_reports_query);
    $total_reports_row = mysqli_fetch_assoc($total_reports_result);
    $total_reports = $total_reports_row['total'];

    // Fetch logs for current page (JOIN users for department and user_type)
    $query = "SELECT activity_logs.*, users.department, users.user_type AS user_type 
FROM activity_logs 
LEFT JOIN users ON activity_logs.username = users.username
ORDER BY activity_logs.timestamp ASC
LIMIT ? OFFSET ?";
    $stmt_logs = $conn->prepare($query);
    $stmt_logs->bind_param('ii', $per_page, $offset);
    $stmt_logs->execute();
    $result = $stmt_logs->get_result();
    $stmt_logs->close();
}

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

$sql_settings = "SELECT system_name FROM settings LIMIT 1";
$result_settings = $conn->query($sql_settings);
$system_name = 'BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM';
if ($result_settings && $row_settings = $result_settings->fetch_assoc()) {
    $system_name = $row_settings['system_name'];
}

$stmt->close();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="reportstyle.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="icon" type="image/png" href="../REPORT.png">
    <title>Activity Logs</title>
    <style>
        .header {
            position: fixed;
            z-index: 1000;
        }

        .entries-search {
            margin: 10px;
            display: flex;
            justify-content: right;
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

        .pagination-btn {
            display: inline-block;
            padding: 6px 14px;
            margin: 0 2px;
            background: #f0f0f0;
            color: #003D73;
            border-radius: 4px;
            text-decoration: none;
            border: 1px solid #ccc;
            font-weight: bold;
        }

        .pagination-btn.active,
        .pagination-btn:hover {
            background: #003D73;
            color: #fff;
            border-color: #003D73;
        }

        .action-delete {
            color: #d90429;
            font-weight: bold;
        }

        .action-update {
            color: #003D73;
            font-weight: bold;
        }

        .action-create {
            color: #38b000;
            font-weight: bold;
        }

        .action-download {
            color: #ffa700;
            font-weight: bold;
        }

        .action-restore {
            color: #003D73;
            font-weight: bold;
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

        .archive-table th,
        .archive-table td {
            padding: 17px 10px;
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
                    <h3>Activity Logs</h3>
                    <hr class="section-separator full-bleed">
                    <div class="entries-search">
                        <div class="entries-right">
                            <div class="search-input-container">
                                <form method="GET" style="display:inline;" id="searchForm">
                                    <input type="search" name="search" class="search-input" placeholder="Search..."
                                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                    <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                                </form>
                            </div>
                        </div>
                    </div>
                    <table class="archive-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Username</th>
                                <th>User Type</th>
                                <th>Department</th>
                                <th>Action</th>
                                <th>Report ID</th> <!-- Unified ID column -->
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['timestamp']); ?></td>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td><?php echo isset($row['user_type']) && $row['user_type'] !== '' ? htmlspecialchars($row['user_type']) : '<span style="color:#888;">N/A</span>'; ?>
                                        </td>
                                        <td><?php echo isset($row['department']) && $row['department'] !== '' ? htmlspecialchars($row['department']) : '<span style="color:#888;">N/A</span>'; ?>
                                        </td>
                                        <td class="<?php
                                                    $action = strtolower($row['action']);
                                                    if ($action === 'delete')
                                                        echo 'action-delete';
                                                    elseif ($action === 'update')
                                                        echo 'action-update';
                                                    elseif ($action === 'create')
                                                        echo 'action-create';
                                                    elseif ($action === 'download')
                                                        echo 'action-download';
                                                    elseif ($action === 'restore')
                                                        echo 'action-restore';
                                                    ?>"><?php echo htmlspecialchars($row['action']); ?></td>
                                        <td>
                                            <?php
                                            if (!empty($row['id'])) {
                                                echo htmlspecialchars($row['id']);
                                            } elseif (!empty($row['report_id'])) {
                                                echo htmlspecialchars($row['report_id']);
                                            } else {
                                                echo '';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['details']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;">No records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                </section>
                </table>
                <?php
                $total_pages = ceil($total_reports / $per_page);
                if ($total_pages > 1): ?>
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
                    document.addEventListener('DOMContentLoaded', function() {
                        const searchInput = document.querySelector('.search-input');
                        const logsTableBody = document.getElementById('logsTableBody');
                        const searchForm = document.getElementById('searchForm');
                        const paginationContainer = document.getElementById('paginationContainer');
                        let debounceTimer;

                        function fetchLogs(searchValue) {
                            const xhr = new XMLHttpRequest();
                            xhr.open('GET', 'activity_logs_search.php?search=' + encodeURIComponent(searchValue), true);
                            xhr.onload = function() {
                                if (xhr.status === 200) {
                                    logsTableBody.innerHTML = xhr.responseText;
                                    if (paginationContainer) {
                                        if (searchValue.trim() !== '') {
                                            paginationContainer.style.display = 'none';
                                        } else {
                                            paginationContainer.style.display = '';
                                        }
                                    }
                                }
                            };
                            xhr.send();
                        }

                        if (searchInput && logsTableBody) {
                            // Fetch logs on initial page load
                            fetchLogs(searchInput.value);
                            searchInput.addEventListener('input', function() {
                                clearTimeout(debounceTimer);
                                debounceTimer = setTimeout(function() {
                                    fetchLogs(searchInput.value);
                                }, 0); // 500ms debounce
                            });
                        }

                        if (searchForm) {
                            searchForm.addEventListener('submit', function(e) {
                                e.preventDefault();
                                fetchLogs(searchInput.value);
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


                <div id="logoutModal" class="confirm-delete-modal">
                    <div class="modal-content">
                        <h3 style="margin-bottom:10px;">Confirm Logout?</h3>
                        <hr>
                        <p style="margin-bottom:24px;">Are you sure you want to logout?</p>
                        <button id="confirmLogout" class="confirm-btn">Logout</button>
                        <button id="cancelLogout" class="cancel-btn">Cancel</button>
                    </div>
                </div>
</body>

</html>

<script src="../js/archivescript.js"></script>