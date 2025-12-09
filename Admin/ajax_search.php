<?php
include('connection.php');

// Initialize $search to an empty string if no search query is provided
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Modify the query to include search functionality
$query = "SELECT id, document_name, department, fileType, 
        DATE_FORMAT(date_archived, '%m-%d-%Y') as date_archived, uploader 
          FROM archives";

if (!empty($search)) {
    $query .= " WHERE document_name LIKE '%$search%' 
                OR department LIKE '%$search%' 
                OR fileType LIKE '%$search%' 
                OR uploader LIKE '%$search%'";
}

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['document_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['fileType']) . "</td>";
        echo "<td>" . htmlspecialchars($row['department']) . "</td>";
        echo "<td>" . htmlspecialchars($row['date_archived']) . "</td>";
        echo "<td>" . htmlspecialchars($row['uploader']) . "</td>"; // Display uploader
        echo "<td>
                <div class='action-dropdown'>
                    <button class='action-btn' onclick=\"toggleActionsDropdown(event)\">Actions <i class='fa fa-caret-down'></i></button>
                    <div class='dropdown-content'>
                        <a href='viewArchive.php?id=" . htmlspecialchars($row['id']) . "'><i class='fa-solid fa-eye'></i> View</a>
                        <a href='downloadArchive.php?id=" . htmlspecialchars($row['id']) . "'><i class='fa-solid fa-download'></i> Download</a>
                        <a href='deleteArchive.php?id=" . htmlspecialchars($row['id']) . "' onclick='return confirm(\"Are you sure you want to delete this record?\");'><i class='fa-solid fa-trash'></i> Delete</a>
                    </div>
                </div>
            </td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='7'>No archives found.</td></tr>";
}
?>
