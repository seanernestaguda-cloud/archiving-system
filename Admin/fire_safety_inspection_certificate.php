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

require('../FPDF/vendor/autoload.php');

$allowed_sort = [
    'permit_id' => 'id',
    'permit_name' => 'permit_name',
    'inspection_date' => 'inspection_date',
    'inspection_establishment' => 'inspection_establishment'
];
$sort_by = isset($_GET['sort_by']) && isset($allowed_sort[$_GET['sort_by']]) ? $allowed_sort[$_GET['sort_by']] : 'id';

$where_clauses[] = "deleted_at IS NULL";
$params = [];
$param_types = '';

if (!empty($_GET['start_month'])) {
    $start = $_GET['start_month'] . '-01';
    $where_clauses[] = "inspection_date >= ?";
    $params[] = $start;
    $param_types .= 's';
}
if (!empty($_GET['end_month'])) {
    $end = date('Y-m-t', strtotime($_GET['end_month'] . '-01'));
    $where_clauses[] = "inspection_date <= ?";
    $params[] = $end;
    $param_types .= 's';
}

$where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// --- Avatar query (safe) ---
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

// --- Pagination ---
$per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;
$offset = ($page - 1) * $per_page;

// --- Count total records (safe) ---
$count_query = "SELECT COUNT(*) FROM fire_safety_inspection_certificate $where_sql";
if ($where_clauses) {
    $stmt_count = $conn->prepare($count_query);
    if ($param_types) {
        $stmt_count->bind_param($param_types, ...$params);
    }
    $stmt_count->execute();
    $stmt_count->bind_result($total_permits);
    $stmt_count->fetch();
    $stmt_count->close();
} else {
    $result_count = mysqli_query($conn, $count_query);
    $total_permits = mysqli_fetch_row($result_count)[0];
}

// --- Main query (safe) ---
$query = "SELECT * FROM fire_safety_inspection_certificate $where_sql ORDER BY $sort_by ASC LIMIT ? OFFSET ?";
if ($where_clauses) {
    $stmt = $conn->prepare($query);
    $full_param_types = $param_types . 'ii';
    $params_with_limit = array_merge($params, [$per_page, $offset]);
    $stmt->bind_param($full_param_types, ...$params_with_limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $permits = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $permits = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
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
    <title>Fire Safety Inspection Certificate</title>
    <style>
        .header {
            position: fixed;
            z-index: 1000;
        }

        .entries-search {
            margin: 10px;
            display: flex;
            justify-content: space-between;
            /* Spread left and right */
            align-items: center;
            gap: 10px;
        }

        .entries-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .entries-right {
            display: flex;
            align-items: center;
        }

        .entries-search label {
            font-size: 14px;
        }

        .entries-search select,
        .entries-search input {
            padding: 5px 10px;
            font-size: 14px;
        }

        .entries-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .select-multi-btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: white;
            color: black;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
            border-radius: 8px;
        }

        .entries-right .search-input {
            width: 220px;
            padding-left: 30px;
            background-size: 16px;
        }

        .search-input-container {
            position: relative;
            display: inline-block;
        }

        .search-input {
            width: 220px;
            padding-left: 32px;
            /* Make space for the icon */
        }

        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            pointer-events: none;
        }

        .select-multi-btn .fa-check-square {
            color: blue;
        }

        .select-multi-btn .fa-trash {
            color: red;
        }

        .select-multi-btn .fa-download {
            color: green;
        }

        .select-multi-btn:hover {
            background-color: #f0f0f0;
        }

        .select-multi-btn.active,
        .select-multi-btn:active {
            background-color: #f0f0f0;
        }

        .no-caret-select {
            border: none;
        }

        .fa-arrow-up-wide-short {
            color: #ffa700;
        }

        .filter-multi-btn {
            padding: 8px 16px;
            background-color: #bd000a;
            color: white;
            border: none;
            border-radius: 8px;
        }

        .filter-multi-btn:hover {
            background-color: #a80000;
        }

        .clear-filter-multi-btn {
            padding: 8px 16px;
            background-color: #70E000;
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
        }

        .clear-filter-multi-btn:hover {
            background-color: #38b000;
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

                    <h3>Fire Safety Inspection</h3>
                    <p>List of Fire Safety Inspection Reports</p>
                    <br>
                    <p id="totalPermitsCount" style="font-weight:bold; color:#003D73; margin-bottom:10px;">Total
                        Reports: <?php echo number_format($total_permits); ?></p>
                    <!-- <hr class="section-separator full-bleed">
                  <div class="top-controls">
            <button onclick="window.location.href='create_fire_safety_inspection_certificate.php'" class="create-new-button"><i class="fa-solid fa-circle-plus"></i> Create New</button>
            </div> -->
                    <hr class="section-separator full-bleed">

                    <div class="entries-search">
                        <!-- entries-left controls -->
                        <div class="entries-left">
                            <button id="toggleSelectBtn" class="select-multi-btn" onclick="toggleSelectMode()">
                                <i class="fa-solid fa-check-square"></i> Select
                            </button>
                            <button id="deleteSelectedBtn" class="select-multi-btn" style="display:none;"
                                onclick="deleteSelectedPermits()">
                                <i class="fa-solid fa-trash"></i>
                                <label for="">Delete Selected</label>
                            </button>
                            <button id="downloadSelectedBtn" class="select-multi-btn" style="display:none;"
                                onclick="downloadSelectedPermits()">
                                <i class="fa-solid fa-download"></i>
                                <label for="">Download Selected</label>
                            </button>
                            <div style="position: relative;">
                                <button type="button" id="sortIconBtn" class="select-multi-btn"
                                    onclick="toggleSortMenu()" style="padding: 8px;">
                                    <i class="fa-solid fa-arrow-up-wide-short"></i>
                                    <i class="fa-solid fa-caret-down"></i>
                                </button>
                                <div id="sortMenu"
                                    style="display:none; position:absolute; left:0; top:110%; background:white; border:1px solid #ccc; border-radius:6px; z-index:10; min-width:120px;">
                                    <a href="?sort_by=permit_id" class="select-multi-btn"
                                        style="width:100%; text-align:left; border-radius:0; border-bottom:1px solid #eee; text-decoration: none;">ID</a>
                                    <a href="?sort_by=permit_name" class="select-multi-btn"
                                        style="width:100%; text-align:left; border-radius:0; border-bottom:1px solid #eee; text-decoration: none;">Title</a>
                                    <a href="?sort_by=inspection_date" class="select-multi-btn"
                                        style="width:100%; text-align:left; border-radius:0; border-bottom:1px solid #eee; text-decoration: none;">Time
                                        & Date</a>
                                    <a href="?sort_by=inspection_establishment" class="select-multi-btn"
                                        style="width:100%; text-align:left; border-radius:0; text-decoration: none;">Establishment</a>
                                </div>
                            </div>
                            <button id="toggleMonthFilterBtn" class="select-multi-btn" type="button"
                                onclick="toggleMonthFilter()">
                                <i style="color:#0096FF;" class="fa-solid fa-calendar"></i>
                            </button>
                            <div id="monthFilterContainer" style="display:none;">
                                <form action="fire_safety_inspection_certificate.php" method="GET"
                                    style="display: flex; gap: 8px; align-items: center;">
                                    <label>
                                        <input type="month" name="start_month"
                                            value="<?php echo isset($_GET['start_month']) ? htmlspecialchars($_GET['start_month']) : ''; ?>">
                                    </label>
                                    <span>to</span>
                                    <label>
                                        <input type="month" name="end_month"
                                            value="<?php echo isset($_GET['end_month']) ? htmlspecialchars($_GET['end_month']) : ''; ?>">
                                    </label>
                                    <button type="submit" class="filter-multi-btn">Filter</button>
                                    <a href="fire_safety_inspection_certificate.php"
                                        class="clear-filter-multi-btn">Clear Filter</a>
                                </form>
                            </div>
                            <form action="export_fire_safety_inspection_certificate.php" method="GET"
                                style="display:inline;">
                                <input type="hidden" name="start_month"
                                    value="<?php echo isset($_GET['start_month']) ? htmlspecialchars($_GET['start_month']) : ''; ?>">
                                <input type="hidden" name="end_month"
                                    value="<?php echo isset($_GET['end_month']) ? htmlspecialchars($_GET['end_month']) : ''; ?>">
                                <input type="hidden" name="sort_by"
                                    value="<?php echo isset($_GET['sort_by']) ? htmlspecialchars($_GET['sort_by']) : ''; ?>">
                                <button type="submit" class="select-multi-btn">
                                    <i class="fa-solid fa-file-excel" style="color: green;"></i>
                                    <label for="">.csv</label>
                                </button>
                            </form>
                            <button type="button" class="select-multi-btn" id="printTableBtn"
                                onclick="printPermitsTable()" style="margin-left: 4px;">
                                <i class="fa-solid fa-print" style="color: #003D73;"></i>
                                <label for="">Print</label>
                            </button>
                        </div>
                        <!-- entries-right (search) -->
                        <div class="entries-right">
                            <div class="search-input-container">
                                <input type="search" class="search-input" placeholder="Search..." value="" />
                                <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                            </div>
                        </div>
                    </div>
                    <table class="archive-table">
                        <tr>
                            <th class="select-checkbox-header" style="display:none;">
                                <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)" />
                            </th>
                            <th> Inspection ID </th>
                            <th> Title</th>
                            <th>Establishment Name</th>
                            <th>Establishment Type</th>
                            <th>Owner of the Establishment</th>
                            <th>Purpose</th>
                            <th>Address</th>
                            <th>Date of Inspection</th>
                            <th>Uploader</th>
                            <th>Date Created</th>
                            <!-- <th>Department</th> -->
                            <th>Status</th>
                            <th> Action </th>
                        </tr>
                        </thead>
                        <tbody id="permitsTableBody">
                            <?php if (count($permits) === 0): ?>
                                <tr>
                                    <td colspan="12" style="text-align:center;">No reports found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($permits as $row): ?>
                                    <tr id="report-row<?php echo $row['id']; ?>">
                                        <td class="select-checkbox-cell" style="display:none;">
                                            <input type="checkbox" class="select-item"
                                                value="<?php echo htmlspecialchars($row['id']); ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['permit_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['inspection_establishment']); ?></td>
                                        <td><?php echo htmlspecialchars($row['establishment_type']); ?></td>
                                        <td><?php echo htmlspecialchars($row['owner']); ?></td>
                                        <td><?php echo htmlspecialchars($row['inspection_purpose']); ?></td>
                                        <td><?php echo htmlspecialchars($row['inspection_address']); ?></td>
                                        <td><?php echo htmlspecialchars(date("Y-m-d", strtotime($row['inspection_date']))) ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['uploader']); ?></td>
                                        <td><?php echo htmlspecialchars(date("Y-m-d", strtotime($row['inspection_date']))); ?>
                                        </td>
                                        <!-- <td><?php echo (isset($row['department']) && trim($row['department']) !== '') ? htmlspecialchars($row['department']) : 'N/A'; ?> -->
                                        <td>
                                            <?php
                                            // List all required fields from your create form
                                            $required_fields = [
                                                $row['permit_name'],
                                                $row['inspection_establishment'],
                                                $row['owner'],
                                                $row['inspection_address'],
                                                $row['inspection_date'],
                                                $row['establishment_type'],
                                                $row['inspection_purpose'],
                                                $row['fire_alarms'],
                                                $row['fire_extinguishers'],
                                                $row['emergency_exits'],
                                                $row['sprinkler_systems'],
                                                $row['fire_drills'],
                                                $row['exit_signs'],
                                                $row['electrical_wiring'],
                                                $row['emergency_evacuations'],
                                                $row['inspected_by'],
                                                $row['contact_person'],
                                                $row['contact_number'],
                                                $row['number_of_occupants'],
                                                $row['nature_of_business'],
                                                $row['number_of_floors'],
                                                $row['floor_area'],
                                                $row['classification_of_hazards'],
                                                $row['building_construction'],
                                                $row['possible_problems'],
                                                $row['hazardous_materials'],
                                                $row['application_form'],
                                                $row['proof_of_ownership'],
                                                $row['fire_safety_inspection_checklist'],
                                                $row['fire_safety_inspection_certificate'],
                                                $row['building_plans'],
                                                $row['occupancy_permit'],
                                                $row['business_permit'],
                                            ];

                                            // Check if all required fields are filled (not empty or just spaces)
                                            $is_complete = true;
                                            foreach ($required_fields as $field) {
                                                if (!isset($field) || trim($field) === '' || $field === ', , , ') {
                                                    $is_complete = false;
                                                    break;
                                                }
                                            }
                                            echo $is_complete ? '<span style="color:green;">Complete</span>' : '<span style="color:orange;">In Progress</span>';
                                            ?>
                                        </td>

                                        <td class="action-button-container">
                                            <button class="view-btn"
                                                onclick="window.location.href='permit_details.php?id=<?php echo $row['id']; ?>'">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            <button class="delete-btn" onclick="deletePermit(<?php echo $row['id']; ?>)">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                            <button class="download-btn"
                                                onclick="window.location.href='generate_permit.php?id=<?php echo $row['id']; ?>'">
                                                <i class="fa-solid fa-download"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>

                    </table>

                    <?php
                    $total_pages = ceil($total_permits / $per_page);
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
                </section>
            </div>
        </div>

        <div id="ConfirmDeleteModal" class="confirm-delete-modal">
            <div class="modal-content">
                <h3>Confirm Delete</h3>
                <hr>
                <p> Are you sure you want to delete? </p>
                <button class="confirm-btn" id="confirmDeleteBtn">Confirm</button>
                <button class="cancel-btn" onclick="closeDeleteConfirmation()">Cancel</button>
            </div>
        </div>

        <div id="successModal" class="success-modal">
            <div class="success-modal-content">
                <i class="fa-regular fa-circle-check"></i>
                <h2>Success!</h2>
                <p id="successMessage"> Report deleted successfully!</p>
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

        <script>
            // Print Table Button handler
            function printPermitsTable() {
                const table = document.querySelector('.archive-table');
                if (!table) return;
                // Clone the table for printing and remove the Action column by header text
                const clone = table.cloneNode(true);
                // Find the index of the Action column
                const headerRow = clone.querySelector('tr');
                let actionColIndex = -1;
                if (headerRow) {
                    Array.from(headerRow.children).forEach((th, idx) => {
                        if (th.textContent.trim().toLowerCase() === 'action') {
                            actionColIndex = idx;
                        }
                    });
                }
                // Remove the Action header cell
                if (actionColIndex !== -1 && headerRow) {
                    headerRow.removeChild(headerRow.children[actionColIndex]);
                }
                // Remove the Action cell from each body row
                clone.querySelectorAll('tbody tr').forEach(row => {
                    if (actionColIndex !== -1 && row.children.length > actionColIndex) {
                        row.removeChild(row.children[actionColIndex]);
                    }
                });
                const printWindow = window.open('', '', 'height=700,width=1200');
                printWindow.document.write('<html><head><title>Print Fire Safety Inspection Table</title>');
                printWindow.document.write('<link rel="stylesheet" href="reportstyle.css">');
                printWindow.document.write('<style>body{font-family:sans-serif;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #ccc;padding:8px;} th{background:#003D73;color:#fff;} }</style>');
                printWindow.document.write('</head><body >');
                printWindow.document.write('<h2>Fire Safety Inspection Reports</h2>');
                printWindow.document.write(clone.outerHTML);
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.focus();
                printWindow.print();
            }
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

            function toggleSelectMode() {
                // Toggle visibility of checkbox columns
                const header = document.querySelector('.select-checkbox-header');
                const cells = document.querySelectorAll('.select-checkbox-cell');
                const deleteBtn = document.getElementById('deleteSelectedBtn');
                const downloadBtn = document.getElementById('downloadSelectedBtn');
                const isVisible = header && header.style.display !== 'none';

                if (header) {
                    if (isVisible) {
                        header.style.display = 'none';
                        cells.forEach(cell => cell.style.display = 'none');
                        deleteBtn.style.display = 'none';
                        downloadBtn.style.display = 'none';
                    } else {
                        header.style.display = '';
                        cells.forEach(cell => cell.style.display = '');
                        deleteBtn.style.display = '';
                        downloadBtn.style.display = '';
                    }
                }
            }

            // "Select All" functionality
            function toggleSelectAll(source) {
                const checkboxes = document.querySelectorAll('.select-item');
                checkboxes.forEach(cb => cb.checked = source.checked);
            }

            let selectedToDelete = []; // Make sure this is global

            function deletePermit(id) {
                selectedToDelete = [id];
                document.getElementById('ConfirmDeleteModal').style.display = 'flex';

                setConfirmDeleteHandler(singleDeleteHandler);
            }

            function deleteSelectedPermits() {
                selectedToDelete = Array.from(document.querySelectorAll('.select-item:checked')).map(cb => cb.value);
                if (selectedToDelete.length === 0) {
                    alert('No permits selected.');
                    return;
                }
                document.getElementById('ConfirmDeleteModal').style.display = 'flex';

                setConfirmDeleteHandler(multiDeleteHandler);
            }

            function setConfirmDeleteHandler(handler) {
                const btn = document.getElementById('confirmDeleteBtn');
                // Remove previous handlers
                btn.onclick = null;
                btn.onclick = handler;
            }

            function singleDeleteHandler() {
                fetch('delete_selected_permits.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        permit_ids: selectedToDelete
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            refreshPermitsTable();
                            openSuccessModal();
                        } else {
                            alert('Error deleting permit.');
                        }
                        closeDeleteConfirmation();
                    });
            }

            function multiDeleteHandler() {
                fetch('delete_selected_permits.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        permit_ids: selectedToDelete
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            refreshPermitsTable();
                            openSuccessModal();
                        } else {
                            alert('Error deleting permits.');
                        }
                        closeDeleteConfirmation();
                    });
            };

            function closeDeleteConfirmation() {
                document.getElementById('ConfirmDeleteModal').style.display = 'none';
            }

            function downloadSelectedPermits() {
                const selected = Array.from(document.querySelectorAll('.select-item:checked')).map(cb => cb.value);
                if (selected.length === 0) {
                    alert('No permits selected.');
                    return;
                }

                // Download each permit as a PDF by opening its URL in a new tab
                selected.forEach(permitId => {
                    const url = `generate_permit.php?id=${encodeURIComponent(permitId)} `;
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `fire_safety_permit_${permitId} .pdf`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                });
            }

            // Open success message modal
            function openSuccessModal() {
                document.getElementById('successModal').style.display = 'block';

                // Automatically close the modal after 2 seconds
                setTimeout(() => {
                    closeSuccessModal();
                }, 2000); // 2000 milliseconds = 2 seconds
            }

            // Refresh the permits table via AJAX
            function refreshPermitsTable() {
                // Preserve current filters/search/pagination
                const urlParams = new URLSearchParams(window.location.search);
                let ajaxUrl = 'fire_safety_inspection_certificate_ajax.php';
                const searchInput = document.querySelector('.search-input');
                if (searchInput && searchInput.value) {
                    urlParams.set('search', searchInput.value);
                }
                if ([...urlParams].length > 0) {
                    ajaxUrl += '?' + urlParams.toString();
                }
                fetch(ajaxUrl)
                    .then(response => response.text())
                    .then(html => {
                        const reportsTableBody = document.getElementById('permitsTableBody');
                        if (html.trim() === '') {
                            reportsTableBody.innerHTML = '<tr><td colspan="12" style="text-align:center;">No reports found.</td></tr>';
                        } else {
                            reportsTableBody.innerHTML = html;
                        }
                    });
            }

            // Close success message modal
            function closeSuccessModal() {
                document.getElementById('successModal').style.display = 'none';
            }

            function toggleMonthFilter() {
                const container = document.getElementById('monthFilterContainer');
                container.style.display = (container.style.display === 'none' || container.style.display === '') ? 'block' : 'none';
            }

            function toggleSortMenu() {
                const menu = document.getElementById('sortMenu');
                menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
                // Hide menu if clicked outside
                document.addEventListener('click', function handler(e) {
                    if (!menu.contains(e.target) && e.target.id !== 'sortIconBtn') {
                        menu.style.display = 'none';
                        document.removeEventListener('click', handler);
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('start_month') || urlParams.get('end_month')) {
                    document.getElementById('monthFilterContainer').style.display = 'block';
                }
            });

            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.querySelector('.search-input');
                const reportsTableBody = document.getElementById('permitsTableBody');
                const totalPermitsCount = document.getElementById('totalPermitsCount');
                const paginationContainer = document.getElementById('paginationContainer');

                if (searchInput && reportsTableBody && totalPermitsCount) {
                    let searchTimeout;
                    searchInput.addEventListener('input', function () {
                        clearTimeout(searchTimeout);
                        searchTimeout = setTimeout(function () {
                            const query = searchInput.value;
                            if (query === '') {
                                window.location.href = window.location.pathname + window.location.search.replace(/([?&])search=[^&]*/g, '');
                                if (paginationContainer) {
                                    paginationContainer.style.display = '';
                                }
                            } else {
                                // Always reset to page 1 when searching
                                fetch(`fire_safety_inspection_certificate_ajax.php?search=${encodeURIComponent(query)}&count=1&page=1`)
                                    .then(response => response.json())
                                    .then(data => {
                                        reportsTableBody.innerHTML = data.html;
                                        totalPermitsCount.textContent = 'Total Reports: ' + data.count;
                                        if (paginationContainer) {
                                            paginationContainer.style.display = 'none';
                                        }
                                    });
                            }
                        }, 0);
                    });
                }
            });
        </script>

</body>

</html>
<script src="../js/archivescript.js"></script>