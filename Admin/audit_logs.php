<?php
include('connection.php');
include('auth_check.php');

// Only allow admins (add your own admin check if needed)
// if ($_SESSION['role'] !== 'admin') { die('Access denied'); }

$result = $conn->query("SELECT * FROM audit_logs ORDER BY timestamp DESC LIMIT 100");
?>
<!DOCTYPE html>
<html>

<head>
    <title>Audit Logs</title>
    <link rel="stylesheet" href="reportstyle.css">
</head>

<body>
    <div class="card">
        <h2>Audit Logs</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Action</th>
                    <th>Report ID</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['action']); ?></td>
                        <td><?php echo htmlspecialchars($row['report_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['timestamp']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

</html>