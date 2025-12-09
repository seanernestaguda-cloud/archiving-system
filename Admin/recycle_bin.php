<?php
include('connection.php');
include('auth_check.php');

$sql_settings = "SELECT system_name FROM settings LIMIT 1";
$result_settings = $conn->query($sql_settings);
$system_name = 'BUREAU OF FIRE PROTECTION ARCHIVING SYSTEM';
if ($result_settings && $row_settings = $result_settings->fetch_assoc()) {
    $system_name = $row_settings['system_name'];
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

// --- SEARCH LOGIC ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = "%$search%";

// --- COUNT LOGIC ---
// Combine both tables for counting
$count_sql = "
    SELECT COUNT(*) AS cnt FROM (
        SELECT report_id AS id, report_title AS title, 'Fire Incident Report' AS type, deleted_at
        FROM fire_incident_reports
        WHERE deleted_at IS NOT NULL" . ($search !== '' ? " AND report_title LIKE ?" : "") . "
        UNION ALL
     SELECT id, permit_name AS title, 'Fire Inspection Report' AS type, deleted_at   
        FROM fire_safety_inspection_certificate
        WHERE deleted_at IS NOT NULL" . ($search !== '' ? " AND permit_name LIKE ?" : "") . "
    ) AS combined
";
if ($search !== '') {
    $stmt_count = $conn->prepare($count_sql);
    $stmt_count->bind_param("ss", $search_param, $search_param);
    $stmt_count->execute();
    $total_reports = $stmt_count->get_result()->fetch_assoc()['cnt'];
    $stmt_count->close();
} else {
    $total_reports = $conn->query($count_sql)->fetch_assoc()['cnt'];
}

$per_page = 10; // Number of items per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total_reports / $per_page);

// --- FETCH PAGINATED ITEMS ---
// Combine both tables for fetching
$fetch_sql = "
    SELECT * FROM (
        SELECT report_id AS id, report_title AS title, 'Fire Incident Report' AS type, deleted_at
        FROM fire_incident_reports
        WHERE deleted_at IS NOT NULL" . ($search !== '' ? " AND report_title LIKE ?" : "") . "
        UNION ALL
        SELECT id, permit_name AS title, 'Fire Inspection Report' AS type, deleted_at
        FROM fire_safety_inspection_certificate
        WHERE deleted_at IS NOT NULL" . ($search !== '' ? " AND permit_name LIKE ?" : "") . "
    ) AS combined
    ORDER BY deleted_at DESC
    LIMIT ?, ?
";
$deleted_items = [];
if ($search !== '') {
    $stmt_fetch = $conn->prepare($fetch_sql);
    $stmt_fetch->bind_param("ssii", $search_param, $search_param, $offset, $per_page);
} else {
    $stmt_fetch = $conn->prepare($fetch_sql);
    $stmt_fetch->bind_param("ii", $offset, $per_page);
}
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
while ($row = $result->fetch_assoc()) {
    $deleted_items[] = $row;
}
$stmt_fetch->close();
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
    <title>Recycle Bin</title>
    <style>
        .action-button-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .action-button-container button {
            border-style: hidden;
            margin: 0px;
            /* Add space between buttons */
            padding: 10px 12px;
            /* Adjust padding as needed */
            font-size: 14px;
            /* Font size */
            border-radius: 0px;
            /* Rounded corners */
            cursor: pointer;
            /* Pointer cursor on hover */
        }

        .restore-btn {
            background-color: #4CAF50;
            color: white;
        }

        .restore-btn:hover {
            background-color: #45a049;
            /* Darker green on hover */
        }

        .search-input-container {
            position: relative;
            display: inline-block;
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


        .restore-btn.show-empty-bin-modal {
            background-color: white;
            /* Bootstrap danger red */
            color: #444;
            border: none;
            border-radius: 4px;
            padding: 10px 18px;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s;
            box-shadow: 0 2px 6px rgba(217, 83, 79, 0.08);
        }

        .restore-btn.show-empty-bin-modal i {
            margin-right: 8px;
            /* Space between icon and text */
        }

        .restore-btn.show-empty-bin-modal:hover {
            background-color: #4444;
            /* Darker red on hover */
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

        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            pointer-events: none;
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
                <h2><?php echo htmlspecialchars($system_name); ?></h2>
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
                <h3>Recycle Bin</h3>
                <hr>
                <div class="entries-search">
                    <div class="entries-left">
                        <button type="button" id="toggleSelectBtn" class="select-multi-btn" style="margin-right:10px;">
                            <i class="fa-solid fa-check-double"></i> Select
                        </button>
                        <form id="bulkRestoreForm" method="POST" action="bulk_restore.php" style="display:inline;">
                            <button type="submit" id="restoreSelectedBtn" class="select-multi-btn" style="display:none;">
                                <i class="fa-solid fa-rotate-left"></i> Restore Selected
                            </button>
                        </form>
                        <form method="POST" action="empty_recycle_bin.php" id="emptyBinForm" style="display:inline;">
                            <button type="button" class="restore-btn show-empty-bin-modal">
                                <i class="fa-solid fa-trash"></i> Empty Recycle Bin
                            </button>
                        </form>
                    </div>
                    <form method="GET" style="display:inline;" id="searchForm">
                        <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="search" name="search" class="search-input" placeholder="Search..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    </form>
                </div>
                <table class="archive-table">
                    <thead>
                        <tr>
                            <th style="display:none;" class="select-col"><input type="checkbox" id="selectAll"></th>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Date Deleted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="recycleBinTableBody">
                        <?php if (empty($deleted_items)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;">Recycle bin is empty.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($deleted_items as $item): ?>
                                <tr>
                                    <td style="display:none;" class="select-col">
                                        <input type="checkbox" class="row-checkbox" name="selected_ids[]" value="<?= htmlspecialchars($item['id']) ?>" data-type="<?= htmlspecialchars($item['type']) ?>">
                                    </td>
                                    <td><?= htmlspecialchars($item['id']) ?></td>
                                    <td><?= htmlspecialchars($item['title']) ?></td>
                                    <td><?= htmlspecialchars($item['type']) ?></td>
                                    <td><?= htmlspecialchars($item['deleted_at']) ?></td>
                                    <td class="action-button-container">
                                        <?php if ($item['type'] === 'Fire Incident Report'): ?>
                                            <form method="POST" action="restore_report.php" style="display:inline;">
                                                <input type="hidden" name="report_id" value="<?= $item['id'] ?>">
                                                <button type="button" class="restore-btn show-restore-modal"><i class="fa-solid fa-rotate-left"></i></button>
                                            </form>
                                            <form method="POST" action="permanent_delete_report.php" style="display:inline;">
                                                <input type="hidden" name="report_id" value="<?= $item['id'] ?>">
                                                <button type="button" class="delete-btn show-confirm-modal"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="restore_permit.php" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                <button type="button" class="restore-btn show-restore-modal"><i class="fa-solid fa-rotate-left"></i></button>
                                            </form>
                                            <form method="POST" action="permanent_delete_permit.php" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                <button type="button" class="delete-btn show-confirm-modal"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php
                // Pagination HTML
                if ($total_pages > 1): ?>
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
                                        ?>" class="pagination-btn<?php if ($i == $page) echo ' active'; ?>"><?php echo $i; ?></a>
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
                </form>

                <div id="confirmModal" class="confirm-delete-modal">
                    <div class="modal-content">
                        <span class="close" id="closeModal" hidden>&times;</span>
                        <h3>Confirm Permanent Delete?</h3>
                        <hr>
                        <p>Are you sure you want to permanently delete this item? This action cannot be undone!</p>
                        <button id="confirmDelete" class="confirm-btn">Delete</button>
                        <button id="cancelDelete" class="cancel-btn">Cancel</button>
                    </div>
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

            <div id="emptyBinModal" class="confirm-delete-modal" style="display:none;">
                <div class="modal-content">
                    <span class="close" id="closeEmptyBinModal" hidden>&times;</span>
                    <h3>Confirm Empty Recycle Bin?</h3>
                    <hr>
                    <p>Are you sure you want to permanently delete these items? This action cannot be undone!</p>
                    <button id="confirmEmptyBin" class="confirm-btn">Empty Recycle Bin</button>
                    <button id="cancelEmptyBin" class="cancel-btn">Cancel</button>
                </div>
            </div>

            <div id="restoreModal" class="confirm-delete-modal" style="display:none;">
                <div class="modal-content">
                    <span class="close" id="closeRestoreModal" hidden>&times;</span>
                    <h3>Confirm Restore?</h3>
                    <hr>
                    <p>Are you sure you want to restore this item?</p>
                    <button id="confirmRestore" class="confirm-btn">Restore</button>
                    <button id="cancelRestore" class="cancel-btn">Cancel</button>
                </div>
            </div>

            <div id="successModal" class="success-modal">
                <div class="success-modal-content">
                    <i class="fa-regular fa-circle-check"></i>
                    <h2>Success!</h2>
                    <p id="successMessage"> Report deleted successfully!</p>
                </div>
            </div>

            <div id="emptyBinSuccessModal" class="success-modal" style="display:none;">
                <div class="success-modal-content">
                    <i class="fa-solid fa-trash"></i>
                    <h2>Success!</h2>
                    <p id="emptyBinSuccessMessage">All items have been permanently deleted.</p>
                </div>
            </div>

            <div id="restoreSelectedSuccessModal" class="success-modal" style="display:none;">
                <div class="success-modal-content">
                    <i class="fa-solid fa-rotate-left"></i>
                    <h2>Success!</h2>
                    <p id="restoreSelectedSuccessModalMessage">Selected items restored successfully!</p>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const searchInput = document.querySelector('.search-input');
                    const tableBody = document.getElementById('recycleBinTableBody');
                    let debounceTimer;

                    function fetchRecycleBin(searchValue) {
                        const xhr = new XMLHttpRequest();
                        xhr.open('GET', 'recycle_bin_search.php?search=' + encodeURIComponent(searchValue), true);
                        xhr.onload = function() {
                            if (xhr.status === 200) {
                                tableBody.innerHTML = xhr.responseText;
                            }
                        };
                        xhr.send();
                    }

                    if (searchInput && tableBody) {
                        searchInput.addEventListener('input', function() {
                            clearTimeout(debounceTimer);
                            debounceTimer = setTimeout(function() {
                                fetchRecycleBin(searchInput.value);
                            }, 500); // 500ms debounce
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

                document.querySelector('.search-input').addEventListener('input', function() {
                    const search = this.value.toLowerCase();
                    document.querySelectorAll('.archive-table tbody tr').forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(search) ? '' : 'none';
                    });
                });


                let formToSubmit = null;

                document.querySelectorAll('.show-confirm-modal').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        formToSubmit = this.closest('form');
                        document.getElementById('confirmModal').style.display = 'flex';
                    });
                });

                document.getElementById('closeModal').onclick = function() {
                    document.getElementById('confirmModal').style.display = 'none';
                    formToSubmit = null;
                };
                document.getElementById('cancelDelete').onclick = function() {
                    document.getElementById('confirmModal').style.display = 'none';
                    formToSubmit = null;
                };
                document.getElementById('confirmDelete').onclick = function() {
                    if (formToSubmit) formToSubmit.submit();
                    document.getElementById('confirmModal').style.display = 'none';
                };
                window.onclick = function(event) {
                    if (event.target == document.getElementById('confirmModal')) {
                        document.getElementById('confirmModal').style.display = 'none';
                        formToSubmit = null;
                    }
                };

                let emptyBinForm = document.getElementById('emptyBinForm');

                document.querySelector('.show-empty-bin-modal').addEventListener('click', function(e) {
                    document.getElementById('emptyBinModal').style.display = 'flex';
                });

                document.getElementById('closeEmptyBinModal').onclick = function() {
                    document.getElementById('emptyBinModal').style.display = 'none';
                };
                document.getElementById('cancelEmptyBin').onclick = function() {
                    document.getElementById('emptyBinModal').style.display = 'none';
                };
                document.getElementById('confirmEmptyBin').onclick = function() {
                    if (emptyBinForm) emptyBinForm.submit();
                    document.getElementById('emptyBinModal').style.display = 'none';
                };
                window.addEventListener('click', function(event) {
                    if (event.target == document.getElementById('emptyBinModal')) {
                        document.getElementById('emptyBinModal').style.display = 'none';
                    }
                });

                let restoreFormToSubmit = null;

                document.querySelectorAll('.show-restore-modal').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        restoreFormToSubmit = this.closest('form');
                        document.getElementById('restoreModal').style.display = 'flex';
                    });
                });

                document.getElementById('closeRestoreModal').onclick = function() {
                    document.getElementById('restoreModal').style.display = 'none';
                    restoreFormToSubmit = null;
                };
                document.getElementById('cancelRestore').onclick = function() {
                    document.getElementById('restoreModal').style.display = 'none';
                    restoreFormToSubmit = null;
                };
                document.getElementById('confirmRestore').onclick = function() {
                    if (restoreFormToSubmit) restoreFormToSubmit.submit();
                    document.getElementById('restoreModal').style.display = 'none';
                };
                window.addEventListener('click', function(event) {
                    if (event.target == document.getElementById('restoreModal')) {
                        document.getElementById('restoreModal').style.display = 'none';
                        restoreFormToSubmit = null;
                    }
                });


                const toggleSelectBtn = document.getElementById('toggleSelectBtn');
                const restoreSelectedBtn = document.getElementById('restoreSelectedBtn');
                const selectCols = document.querySelectorAll('.select-col');
                const selectAll = document.getElementById('selectAll');
                const rowCheckboxes = document.querySelectorAll('.row-checkbox');
                const bulkRestoreForm = document.getElementById('bulkRestoreForm');

                let selectMode = false;

                toggleSelectBtn.addEventListener('click', function() {
                    selectMode = !selectMode;
                    selectCols.forEach(col => col.style.display = selectMode ? '' : 'none');
                    restoreSelectedBtn.style.display = selectMode ? '' : 'none';
                    if (!selectMode) {
                        rowCheckboxes.forEach(cb => cb.checked = false);
                        selectAll.checked = false;
                    }
                });

                // Select all logic
                if (selectAll) {
                    selectAll.addEventListener('change', function() {
                        rowCheckboxes.forEach(cb => cb.checked = selectAll.checked);
                    });
                }

                // On submit, collect checked IDs and types
                bulkRestoreForm.addEventListener('submit', function(e) {
                    // Remove any previous hidden fields
                    document.querySelectorAll('.bulk-hidden').forEach(el => el.remove());
                    let anyChecked = false;
                    rowCheckboxes.forEach(cb => {
                        if (cb.checked) {
                            anyChecked = true;
                            // Add hidden fields for each checked item
                            const inputId = document.createElement('input');
                            inputId.type = 'hidden';
                            inputId.name = 'selected_ids[]';
                            inputId.value = cb.value;
                            inputId.classList.add('bulk-hidden');
                            bulkRestoreForm.appendChild(inputId);

                            const inputType = document.createElement('input');
                            inputType.type = 'hidden';
                            inputType.name = 'selected_types[]';
                            inputType.value = cb.getAttribute('data-type');
                            inputType.classList.add('bulk-hidden');
                            bulkRestoreForm.appendChild(inputType);
                        }
                    });
                    if (!anyChecked) {
                        e.preventDefault();
                        alert('Please select at least one item to restore.');
                    }
                });

                document.addEventListener('DOMContentLoaded', function() {
                    const urlParams = new URLSearchParams(window.location.search);

                    if (urlParams.get('success') === 'restore') {
                        document.getElementById('successMessage').textContent = 'Report restored successfully!';
                        document.getElementById('successModal').style.display = 'block';
                        setTimeout(() => {
                            document.getElementById('successModal').style.display = 'none';
                            urlParams.delete('success');
                            window.history.replaceState({}, document.title, window.location.pathname + '?' + urlParams.toString());
                        }, 2000);
                    }
                    if (urlParams.get('success') === 'delete') {
                        document.getElementById('successMessage').textContent = 'Report deleted successfully!';
                        document.getElementById('successModal').style.display = 'block';
                        setTimeout(() => {
                            document.getElementById('successModal').style.display = 'none';
                            urlParams.delete('success');
                            window.history.replaceState({}, document.title, window.location.pathname + '?' + urlParams.toString());
                        }, 2000);
                    }
                    if (urlParams.get('success') === 'empty') {
                        document.getElementById('emptyBinSuccessModal').style.display = 'block';
                        setTimeout(() => {
                            document.getElementById('emptyBinSuccessModal').style.display = 'none';
                            urlParams.delete('success');
                            window.history.replaceState({}, document.title, window.location.pathname + '?' + urlParams.toString());
                        }, 2000);
                    }
                    if (urlParams.get('success') === 'restore_selected') {
                        document.getElementById('restoreSelectedSuccessModal').style.display = 'block';
                        setTimeout(() => {
                            document.getElementById('restoreSelectedSuccessModal').style.display = 'none';
                            urlParams.delete('success');
                            window.history.replaceState({}, document.title, window.location.pathname + '?' + urlParams.toString());
                        }, 2000);
                    }
                });
            </script>
</body>

</html>
<script src="../js/archivescript.js">
</script>

<script>


</script>