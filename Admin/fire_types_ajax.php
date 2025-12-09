<?php
include('connection.php');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
$params = [];
$param_types = '';

if ($search !== '') {
    $where = "WHERE fire_types LIKE ? OR description LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types = 'ss';
}

$query = "SELECT * FROM fire_types $where ORDER BY fire_types";
$stmt = $conn->prepare($query);

if ($where) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$rows = [];
while ($fire_type = $result->fetch_assoc()) {
    $rows[] = $fire_type;
}

if (count($rows) === 0) {
    echo '<tr><td colspan="3" style="text-align:center;">No records found.</td></tr>';
} else {
    foreach ($rows as $fire_type) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($fire_type['fire_types']) . '</td>';
        echo '<td>' . htmlspecialchars($fire_type['description']) . '</td>';
        echo '<td class="action-button-container">';
        echo '<form action="fire_types.php" method="POST" style="display:flex;">';
        echo '<input type="hidden" name="fire_types_id" value="' . $fire_type['fire_types_id'] . '">';
        echo '<button type="button" onclick="confirmDelete(' . $fire_type['fire_types_id'] . ')" class="delete-btn"><i class="fa-solid fa-trash"></i></button>';
        $js_fire_types = htmlspecialchars($fire_type['fire_types'], ENT_QUOTES);
        $js_description = htmlspecialchars($fire_type['description'], ENT_QUOTES);
        echo '<button type="button" onclick="openEditModal(' . $fire_type['fire_types_id'] . ', \'" . $js_fire_types . "\', \'" . $js_description . "\')" class="edit-btn"><i class="fa-solid fa-pen-to-square"></i></button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
}
$stmt->close();
$conn->close();
?>