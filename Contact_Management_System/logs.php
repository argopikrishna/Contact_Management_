<?php
require_once 'server.php';

// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch the logs from the database for the logged-in user
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM logs WHERE user_id = '$user_id' ORDER BY created_at DESC";
$result = $conn->query($query);

// Check if there are any logs
if ($result && $result->num_rows > 0) {
    // Fetch the logs into an array
    $logs = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $logs = [];
}



// Handle delete all logs
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Delete a specific log or all logs based on the button clicked
    if (isset($_POST['delete_logs'])) {
        // Delete all logs query
        $deleteQuery = "DELETE FROM logs WHERE user_id = '$user_id'";

        // Execute the delete query
        $conn->query($deleteQuery);
    }
}
?>



<!DOCTYPE html>
<html>

<head>
    <title>Logs | Contact Management System</title>
    <link rel="stylesheet" type="text/css" href="./CSS/logs.css">
</head>


<body>
    <nav>
        <div class="navigation-bar">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="contact.php">Contacts</a></li>
                <li><a href="logs.php">Logs</a></li>
                <li class="logout"><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    <div class="container">
        <h3>Logs</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Action</th>
                <th>User ID</th>
                <th>Date/Time</th>
            </tr>
            <?php
            // Output the logs
            foreach ($logs as $log) {
                echo "<tr>";
                echo "<td>{$log['id']}</td>";
                echo "<td>{$log['action']}</td>";
                echo "<td>{$log['user_id']}</td>";
                echo "<td>{$log['created_at']}</td>";
                echo "</tr>";
            }

            if (empty($logs)) {
                echo "<tr><td colspan='4' style='text-align: center;'>No logs found.</td></tr>";
            }
            ?>
        </table>
        <div class="deletelogs-container">
            <form method="post">
                <input type="submit" class="deletelogs" name="delete_logs" value="Delete All Logs" onclick="return confirm('Are you sure you want to delete all Logs?')">
            </form>
        </div>
    </div>
</body>

</html>