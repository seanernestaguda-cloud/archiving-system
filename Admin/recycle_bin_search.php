<?php
include('connection.php');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = "%$search%";
$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Fetch deleted items from both tables
$fetch_sql = "
    SELECT * FROM (
        SELECT report_id AS id, report_title AS title, 'Incident Report' AS type, deleted_at
        FROM fire_incident_reports
        WHERE deleted_at IS NOT NULL" . ($search !== '' ? " AND report_title LIKE ?" : "") . "
        UNION ALL
        SELECT id, permit_name AS title, 'Fire Inspection Certificate' AS type, deleted_at
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

if (empty($deleted_items)) {
    echo '<tr><td colspan="6" style="text-align:center;">Recycle bin is empty.</td></tr>';
} else {
    foreach ($deleted_items as $item) {
        ?>
        <tr>
            <td style="display:none;" class="select-col">
                <input type="checkbox" class="row-checkbox" name="selected_ids[]" value="<?= htmlspecialchars($item['id']) ?>" data-type="<?= htmlspecialchars($item['type']) ?>">
            </td>
            <td><?= htmlspecialchars($item['id']) ?></td>
            <td><?= htmlspecialchars($item['title']) ?></td>
            <td><?= htmlspecialchars($item['type']) ?></td>
            <td><?= htmlspecialchars($item['deleted_at']) ?></td>
            <td class="action-button-container">
                <?php if ($item['type'] === 'Incident Report'): ?>
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
        <?php
    }
}
?>