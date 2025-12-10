<?php
// session_start();
include('connection.php');
include('auth_check.php');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


$allowed_sort_columns = ['report_id', 'report_title', 'created_at', 'fire_location'];
$sort_by = isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowed_sort_columns) ? $_GET['sort_by'] : 'report_id';
$order_by = 'ASC';

// If sorting by 'fire_location', use the full concatenated location
if ($sort_by === 'fire_location') {
    $order_by_sql = "ORDER BY CONCAT(street, ', ', purok, ', ', fire_location, ', ', municipality) $order_by";
} else {
    $order_by_sql = "ORDER BY $sort_by $order_by";
}

$where_clauses[] = "deleted_at IS NULL";
$params = [];
$param_types = '';

// Only show reports uploaded by the current user
$username = $_SESSION['username'];
$where_clauses[] = "uploader = ?";
$params[] = $username;
$param_types .= 's';

if (!empty($_GET['start_month'])) {
    $start = $_GET['start_month'] . '-01 00:00:00';
    $where_clauses[] = "incident_date >= ?";
    $params[] = $start;
    $param_types .= 's';
}

if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where_clauses[] = "(report_id LIKE ? OR report_title LIKE ? OR fire_location LIKE ? OR establishment LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $param_types .= 'ssss';
}

if (!empty($_GET['end_month'])) {
    $end_month = $_GET['end_month'];
    $last_day = date('t', strtotime($end_month . '-01'));
    $end = $end_month . '-' . $last_day . ' 23:59:59';
    $where_clauses[] = "incident_date <= ?";
    $params[] = $end;
    $param_types .= 's';
}

$where_sql = '';
if ($where_clauses) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

$avatar = '../avatars/default_avatar.png';
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

$per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;
$offset = ($page - 1) * $per_page;

$count_query = "SELECT COUNT(*) FROM fire_incident_reports $where_sql";
$stmt_count = $conn->prepare($count_query);
if ($param_types) {
    $stmt_count->bind_param($param_types, ...$params);
}
$stmt_count->execute();
$stmt_count->bind_result($total_reports);
$stmt_count->fetch();
$stmt_count->close();

$query = "SELECT 
    report_id, 
    report_title, 
    CONCAT(street, ', ', purok, ', ', fire_location, ', ', municipality) AS fire_location_combined, 
    incident_date, 
    establishment, 
    victims, 
    firefighters,
    property_damage, 
    fire_types, 
    created_at, 
    uploader, 
    department,
    caller_name,
    responding_team,
    arrival_time,
    fireout_time,
    alarm_status,
    occupancy_type, documentation_photos, narrative_report, progress_report, final_investigation_report
FROM fire_incident_reports 
$where_sql
$order_by_sql
LIMIT ? OFFSET ?";
$full_param_types = $param_types . 'ii';
$params_with_limit = array_merge($params, [$per_page, $offset]);
$stmt = $conn->prepare($query);
$stmt->bind_param($full_param_types, ...$params_with_limit);
$stmt->execute();
$result = $stmt->get_result();
$reports = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
    <title>My Fire Incident Reports</title>
    <style>
        .header {
            position: fixed;
            z-index: 1000;
        }

        .success-message {
            color: white;
            position: fixed;
            background-color: #003D73;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px;
            border-radius: 5px;
            font-size: 16px;
            z-index: 9999;
            opacity: 0;
            /* Initially hidden */
            animation: fadeInOut 4s ease forwards;
            /* Animation added */
        }

        /* Add to your existing CSS */
        .side-by-side {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            /* Adjust space between the fields */
        }

        .modal-input-container {
            flex: 1;
            /* Ensure both fields take up equal space */
        }

        .modal-input {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
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

        .selec-multi-btn:focus {
            outline: none;
            box-shadow: 0 0 0 2px #80bdff;
        }

        .no-caret-select {
            border: none;
        }

        .no-caret-select::focus {
            outline: none;
            border: none;
            box-shadow: none;
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
                    <li><a href="userdashboard.php"><i class="fa-solid fa-gauge"></i> <span>Dashboard</span></a></li>
                    <li class="archive-text">
                        <p>Archives</p>
                    </li>
                    <!-- <li><a href="fire_types.php"><i class="fa-solid fa-fire-flame-curved"></i><span> Causes of Fire </span></a></li>
                <li><a href="barangay_list.php"><i class="fa-solid fa-building"></i><span> Barangay List </span></a></li> -->
                    <li><a href="myarchives.php"><i class="fa-solid fa-box-archive"></i><span> My Archives </span></a>
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

                    <!-- <li class="archive-text"><span>Maintenance</span></li>
                <li><a href="activity_logs.php"><i class="fa-solid fa-file-invoice"></i><span> Activity Logs </span></a></li>
                <li><a href="departments.php"><i class="fas fa-users"></i><span> Department List </span></a></li>
                <li><a href="manageuser.php"><i class="fas fa-users"></i><span> Manage Users </span></a></li>
                <li><a href="setting.php"><i class="fa-solid fa-gear"></i> <span>Settings</span></a></li> -->
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
                <section class="archive-section">
                    <h3><?php echo htmlspecialchars($_SESSION['username']); ?>'s Fire Incident Reports</h3>
                    <p> List of Fire Incident Reports</p>
                    <br>
                    <p id="totalReportsCount" style="font-weight:bold; color:#003D73; margin-bottom:10px;">Total
                        Reports: <?php echo number_format($total_reports); ?></p>
                    <hr class="section-separator full-bleed">
                    <div class="top-controls">
                        <button onclick="window.location.href='create_fire_incident_report.php'"
                            class="create-new-button">
                            <i class="fa-solid fa-circle-plus"></i>Create New</button>
                    </div>
                    <hr class="section-separator full-bleed">

                    <div class="entries-search">
                        <div class="entries-left">
                            <button id="toggleSelectBtn" class="select-multi-btn" onclick="toggleSelectMode()">
                                <i class="fa-solid fa-check-square"></i>
                                <label for=""> Select </label>
                            </button>
                            <button id="deleteSelectedBtn" class="select-multi-btn" style="display:none;"
                                onclick="deleteSelectedReports()">
                                <i class="fa-solid fa-trash"></i>
                                <label for=""> Delete Selected </label>
                            </button>
                            <button id="downloadSelectedBtn" class="select-multi-btn" style="display:none;"
                                onclick="downloadSelectedReports()">
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
                                    <a href="?sort_by=report_id" class="select-multi-btn"
                                        style="width:100%; text-align:left; border-radius:0; border-bottom:1px solid #eee; text-decoration: none;">ID</a>
                                    <a href="?sort_by=report_title" class="select-multi-btn"
                                        style="width:100%; text-align:left; border-radius:0; border-bottom:1px solid #eee; text-decoration: none;">Title</a>
                                    <a href="?sort_by=created_at" class="select-multi-btn"
                                        style="width:100%; text-align:left; border-radius:0; border-bottom:1px solid #eee; text-decoration: none;">Date
                                        Created</a>
                                    <a href="?sort_by=fire_location" class="select-multi-btn"
                                        style="width:100%; text-align:left; border-radius:0; text-decoration: none;">Location</a>
                                </div>
                            </div>

                            <form action="export_my_reports.php" method="GET" style="display:inline;">
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
                            <button type="button" class="select-multi-btn" id="printTableBtn" style="margin-left: 5px;">
                                <i class="fa-solid fa-print" style="color: #003D73;"></i>
                                <label for="">Print</label>
                            </button>
                            </form>
                        </div>


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
                                <th class="select-checkbox-header" style="display:none;">
                                    <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)" />
                                <th> Report ID </th>
                                <th>Report Title</th>
                                <th>Location</th>
                                <th>Time and Date</th>
                                <th>Establishment</th>
                                <th>Casualties</th>
                                <th>Damage to Property</th>
                                <th>Cause of Fire</th>
                                <th>Created At</th>
                                <th>Status</th>
                                <th>Action</th>

                            </tr>
                        </thead>
                        <tbody id="reportsTableBody">
                            <?php if (count($reports) === 0): ?>
                                <tr>
                                    <td colspan="11" style="text-align:center;">No reports found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reports as $row): ?>
                                    <tr id="report-row<?php echo $row['report_id']; ?>">
                                        <td class="select-checkbox-cell" style="display:none;">
                                            <input type="checkbox" class="select-item"
                                                value="<?php echo htmlspecialchars($row['report_id']); ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($row['report_id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['report_title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['fire_location_combined']); ?></td>
                                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                        <td><?php echo htmlspecialchars($row['establishment']); ?></td>
                                        <td>
                                            <?php
                                            $victims_count = empty($row['victims']) ? 0 : substr_count($row['victims'], ',') + 1;
                                            $firefighters_count = empty($row['firefighters']) ? 0 : substr_count($row['firefighters'], ',') + 1;
                                            echo $victims_count + $firefighters_count;
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars("₱" . $row['property_damage']); ?></td>
                                        <td><?php echo empty($row['fire_types']) ? 'Under Investigation' : htmlspecialchars($row['fire_types']); ?>
                                        <td><?php echo htmlspecialchars($row['created_at'] ?? ''); ?></td>
                                        <td>
                                            <?php
                                            // List all required fields from your create form
                                            $required_fields = [
                                                $row['report_title'],
                                                $row['caller_name'],
                                                $row['responding_team'],
                                                $row['fire_location_combined'],
                                                $row['incident_date'],
                                                $row['arrival_time'],
                                                $row['fireout_time'],
                                                $row['establishment'],
                                                $row['alarm_status'],
                                                $row['occupancy_type'],
                                                $row['property_damage'],
                                                $row['fire_types'],
                                                $row['documentation_photos'],
                                                $row['narrative_report'],
                                                $row['progress_report'],
                                                $row['final_investigation_report']
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
                                                onclick="window.location.href='view_my_report.php?report_id=<?php echo $row['report_id']; ?>'">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            <button class="delete-btn" onclick="deleteReport(<?php echo $row['report_id']; ?>)">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                            <button class="download-btn"
                                                onclick="window.location.href='generate_pdf.php?report_id=<?php echo $row['report_id']; ?>'">
                                                <i class="fa-solid fa-download"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>

                    </table>

                    <?php
                    $total_pages = ceil($total_reports / $per_page);
                    // Hide pagination if searching
                    $is_searching = !empty($_GET['search']);
                    if ($total_pages > 1 && !$is_searching): ?>
                        <div class="pagination" style="margin: 20px 0; text-align: center;">
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

        <!-- <div id="uploadModal" class="report-details-modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeUploadModal()">&times;</span>
        <h3>Upload Fire Incident Report</h3>
        <form action="fire_incident_report.php" method="POST" enctype="multipart/form-data">
            <label for="file">Select File:</label>
            <input type="file" id="file" name="file" accept=".xls,.xlsx,.pdf" required>
            <div style="margin-top: 15px;">
                <button type="submit" class="action-button">Upload</button>
            </div>
        </form>
    </div>
</div> -->

        <div id="confirmDeleteModal" class="confirm-delete-modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeDeleteModal()">&times;</span>
                <h3>Confirm Delete?</h3>
                <hr>
                <p> Are you sure you want to delete this report? </p>
                <button id="confirmDeleteBtn" class="confirm-btn">Confirm</button>
                <button class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
            </div>
        </div>

        <div id="successModal" class="success-modal">
            <div class="success-modal-content">
                <i class="fa-regular fa-circle-check"></i>
                <h2>Success!</h2>
                <p id="successMessage"> Report Deleted Successfully!</p>
            </div>
        </div>


        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Print Table Button functionality
                const printBtn = document.getElementById('printTableBtn');
                if (printBtn) {
                    printBtn.addEventListener('click', function () {
                        const table = document.querySelector('.archive-table');
                        if (!table) return;
                        // Clone table to avoid modifying the original
                        const tableClone = table.cloneNode(true);
                        // Remove Action column from thead
                        const theadRow = tableClone.querySelector('thead tr');
                        if (theadRow) {
                            let actionColIndex = -1;
                            Array.from(theadRow.children).forEach((th, idx) => {
                                if (th.textContent.trim().toLowerCase() === 'action') {
                                    actionColIndex = idx;
                                }
                            });
                            if (actionColIndex !== -1) {
                                theadRow.removeChild(theadRow.children[actionColIndex]);
                            }
                        }
                        // Remove Action column from tbody
                        const rows = tableClone.querySelectorAll('tbody tr');
                        rows.forEach(row => {
                            if (row.children.length > 0) {
                                row.removeChild(row.lastElementChild);
                            }
                        });
                        // Remove Action column from tfoot if present
                        const tfootRow = tableClone.querySelector('tfoot tr');
                        if (tfootRow && tfootRow.children.length > 0) {
                            tfootRow.removeChild(tfootRow.lastElementChild);
                        }
                        const newWin = window.open('', '', 'width=900,height=700');
                        newWin.document.write('<html><head><title>Print Table</title>');
                        newWin.document.write('<link rel="stylesheet" href="reportstyle.css">');
                        newWin.document.write('</head><body >');
                        newWin.document.write(tableClone.outerHTML);
                        newWin.document.write('</body></html>');
                        newWin.document.close();
                        newWin.focus();
                        setTimeout(() => {
                            newWin.print();
                        }, 500);
                    });
                }
            });
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
                setTimeout(() => {
                    closeSuccessModal();
                }, 2000);
            }

            // Close success message modal
            function closeSuccessModal() {
                document.getElementById('successModal').style.display = 'none';
            }

            function toggleSelectMode() {
                const header = document.querySelector('.select-checkbox-header');
                const cells = document.querySelectorAll('.select-checkbox-cell');
                const deleteBtn = document.getElementById('deleteSelectedBtn');
                const downloadBtn = document.getElementById('downloadSelectedBtn');
                const isVisible = header.style.display !== 'none';

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

            // "Select All" functionality
            function toggleSelectAll(source) {
                const checkboxes = document.querySelectorAll('.select-item');
                checkboxes.forEach(cb => cb.checked = source.checked);
            }

            let selectedToDelete = []; // Store selected IDs globally
            let singleDeleteId = null; // Store single delete ID

            function deleteSelectedReports() {
                selectedToDelete = Array.from(document.querySelectorAll('.select-item:checked')).map(cb => cb.value);
                singleDeleteId = null; // Make sure single delete is not set
                if (selectedToDelete.length === 0) {
                    alert('No reports selected.');
                    return;
                }
                document.getElementById('confirmDeleteModal').style.display = 'flex';
            }

            function deleteReport(report_id) {
                selectedToDelete = []; // Make sure multi-delete is not set
                singleDeleteId = report_id; // Set single delete ID
                document.getElementById('confirmDeleteModal').style.display = 'flex';
            }

            // Confirm delete handler for both single and multi delete
            document.getElementById('confirmDeleteBtn').onclick = function () {
                if (singleDeleteId) {
                    // Single delete
                    fetch('delete_report.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'report_id=' + encodeURIComponent(singleDeleteId)
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                openSuccessModal();
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1200); // Wait for success modal before reload
                            } else {
                                alert('Error deleting report: ' + (data.error || 'Unknown error'));
                            }
                            singleDeleteId = null;
                            closeDeleteModal();
                        });
                } else if (selectedToDelete.length > 0) {
                    // Multi delete
                    fetch('delete_selected_reports.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            report_ids: selectedToDelete
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                openSuccessModal();
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1200); // Wait for success modal before reload
                            } else {
                                alert('Error deleting reports.');
                            }
                            selectedToDelete = [];
                            closeDeleteModal();
                        });
                }
            };

            function closeDeleteModal() {
                document.getElementById('confirmDeleteModal').style.display = 'none';
            }

            function downloadSelectedReports() {
                const selected = Array.from(document.querySelectorAll('.select-item:checked')).map(cb => cb.value);
                if (selected.length === 0) {
                    alert('No reports selected.');
                    return;
                }
                selected.forEach(reportId => {
                    const url = `generate_pdf.php?report_id=${encodeURIComponent(reportId)}`;
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `fire_report_${reportId}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                });
            }

            function toggleMonthFilter() {
                const container = document.getElementById('monthFilterContainer');
                container.style.display = (container.style.display === 'none' || container.style.display === '') ? 'block' : 'none';
            }

            function toggleSortMenu() {
                const menu = document.getElementById('sortMenu');
                menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
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
                const reportsTableBody = document.getElementById('reportsTableBody');
                const totalReportsCount = document.getElementById('totalReportsCount');

                if (searchInput && reportsTableBody && totalReportsCount) {
                    let searchTimeout;
                    searchInput.addEventListener('input', function () {
                        clearTimeout(searchTimeout);
                        searchTimeout = setTimeout(function () {
                            const query = searchInput.value;
                            if (query === '') {
                                window.location.href = window.location.pathname + window.location.search.replace(/([?&])search=[^&]*/g, '');
                            } else {
                                fetch(`my_fire_incident_report_ajax.php?search=${encodeURIComponent(query)}&count=1`)
                                    .then(response => response.json())
                                    .then(data => {
                                        reportsTableBody.innerHTML = data.html;
                                        totalReportsCount.textContent = `Total Reports: ${data.count}`;
                                    });
                            }
                        }, 0);
                    });
                    // Prevent Enter key from submitting the form
                    searchInput.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                        }
                    });
                }
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