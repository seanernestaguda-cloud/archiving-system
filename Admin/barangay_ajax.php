<?php
include('connection.php');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
$params = [];
$param_types = '';

if ($search !== '') {
    $where = "WHERE barangay_name LIKE ?";
    $params[] = "%$search%";
    $param_types = 's';
}

$query = "SELECT * FROM barangays $where ORDER BY barangay_name";
$stmt = $conn->prepare($query);

if ($where) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$rows = [];
while ($barangay = $result->fetch_assoc()) {
    $rows[] = $barangay;
}

if (count($rows) === 0) {
    echo '<tr><td colspan="2" style="text-align:center;">No records found.</td></tr>';
} else {
    foreach ($rows as $barangay) {
        $js_barangay_name = htmlspecialchars($barangay['barangay_name'], ENT_QUOTES);
        echo '<tr>';
        echo '<td>' . htmlspecialchars($barangay['barangay_name']) . '</td>';
        echo '<td class="action-button-container">';
        echo '<form action="barangay_list.php" method="POST" style="display:flex;">';
        echo '<input type="hidden" name="barangay_id" value="' . $barangay['barangay_id'] . '">';
        echo '<button type="button" onclick="confirmDelete(' . $barangay['barangay_id'] . ')" class="delete-btn"><i class="fa-solid fa-trash"></i></button>';
        echo '<button type="button" onclick="openEditModal(' . $barangay['barangay_id'] . ', \'" . $js_barangay_name . "\')" class="edit-btn"><i class="fa-solid fa-pen-to-square"></i></button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
}
$stmt->close();
$conn->close();
?>