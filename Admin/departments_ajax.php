<?php
include('connection.php');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "SELECT * FROM departments";
if ($search !== '') {
    $query .= " WHERE departments LIKE ?";
}

$query .= " ORDER BY departments ASC";
$stmt = $conn->prepare($query);

if ($search !== '') {
    $searchParam = "%{$search}%";
    $stmt->bind_param('s', $searchParam);
}

$stmt->execute();
$result = $stmt->get_result();
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

if (count($rows) === 0) {
    echo '<tr><td colspan="2" style="text-align:center;">No records found.</td></tr>';
} else {
    foreach ($rows as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['departments']) . '</td>';
        echo '<td>';
        echo '<form action="departments.php" method="POST" style="display:inline;">';
        echo '<input type="hidden" name="departments" value="' . $row['departments_id'] . '">';
        echo '<button type="button" onclick="confirmDelete(' . $row['departments_id'] . ')" class="delete-btn"><i class="fa-solid fa-trash"></i></button>';
        $js_departments = htmlspecialchars($row['departments'], ENT_QUOTES);
        echo '<button type="button" onclick="openEditModal(' . $row['departments_id'] . ', \'" . $js_departments . "\')" class="edit-btn"><i class="fa-solid fa-pen-to-square"></i></button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
}
$stmt->close();
$conn->close();
?>