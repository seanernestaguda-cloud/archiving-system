<?php
session_start();
include 'connection.php';

// Pagination variables
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search = trim($search);
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10; // Number of records per page
$offset = ($page - 1) * $limit;

// Count total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM users WHERE 
    CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ? OR
    username LIKE ? OR
    user_type LIKE ? OR
    department LIKE ? OR
    status LIKE ?";
$param = '%' . $search . '%';
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param('sssss', $param, $param, $param, $param, $param);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = 0;
if ($count_result && $row = $count_result->fetch_assoc()) {
    $total_records = $row['total'];
}
$count_stmt->close();

// Fetch paginated records
$sql = "SELECT * FROM users WHERE 
    CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ? OR
    username LIKE ? OR
    user_type LIKE ? OR
    department LIKE ? OR
    status LIKE ?
    ORDER BY id DESC
    LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssssssi', $param, $param, $param, $param, $param, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $avatar = $row['avatar'] ?: '../avatars/default_avatar.png';
        $fullName = htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
        $username = htmlspecialchars($row['username']);
        $user_type = htmlspecialchars($row['user_type']);
        $department = htmlspecialchars($row['department']);
        $status = htmlspecialchars($row['status']);
        $userId = htmlspecialchars($row['id']);

        echo "<tr>
            <td><img src='../avatars/$avatar' alt='Avatar' width='40' height='40' style='border-radius:100%;'></td>
            <td>$fullName</td>
            <td>$username</td>
            <td>$user_type</td>
            <td>$department</td>";
        if (strtolower($status) === 'verified') {
            echo "<td><span style='color: #fff; font-weight: bold; background-color: green; padding: 5px 10px; border-radius: 20px;'>$status</span></td>";
        } else {
            echo "<td><span style='color: #fff; font-weight: bold; background-color: red; padding: 5px 10px; border-radius: 20px;'>$status</span></td>";
        }
        echo "<td>
                <div class='action-dropdown'>
                    <button class='dropdown-btn' onclick=\"toggleDropdown(event, 'actionDropdown$userId')\">
                        Action <i class='fa-solid fa-caret-down'></i>
                    </button>
                    <div id='actionDropdown$userId' class='action-dropdown-content'>";
        if ($status !== 'verified') {
            echo "<a href='verify_user.php?id=$userId'><i class='fa-solid fa-check'></i> Verify</a>";
        }
        echo "      <a href='edit_user.php?id=$userId'><i class='fa-solid fa-pen-to-square'></i> Edit</a>
                    <a href='#' onclick='confirmDelete($userId)'><i class='fas fa-trash'></i> Delete</a>
                    </div>
                </div>
            </td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='7'>No users found.</td></tr>";
}

// Output pagination if not searching
if ($search === '') {
    $total_pages = ceil($total_records / $limit);
    if ($total_pages > 1) {
        echo "<tr><td colspan='7'><div class='pagination'>";
        for ($i = 1; $i <= $total_pages; $i++) {
            $active = ($i == $page) ? 'active' : '';
            echo "<a href='#' class='page-link $active' data-page='$i'>$i</a> ";
        }
        echo "</div></td></tr>";
    }
}

$stmt->close();
$conn->close();
?>