<?php
require_once 'server.php';

// Check if the admin is not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get admin information
$admin_id = $_SESSION['admin_id'];

// Retrieve admin data from the database
$query = "SELECT * FROM admins WHERE admin_id = '$admin_id'";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $admin = $result->fetch_assoc();
} else {
    // Redirect if admin data not found
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $newEmail = $_POST["new_email"];

    // Check if email is updated
    if ($newEmail != $admin['email']) {
        // Update email
        $updateEmailQuery = "UPDATE admins SET email = '$newEmail' WHERE admin_id = '$admin_id'";
        $conn->query($updateEmailQuery);
    }

    $_SESSION['email_updated'] = true;
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Admin Dashboard | Contact Management System</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" type="text/css" href="./CSS/admin_dashboard.css">
</head>

<body>
    <nav>
        <div class="navigation-bar">
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="user_management.php">User Management</a></li>
                <li class="logout"><a href="admin_logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    <main>
        <div class="admin-information">
            <h2>Admin Information</h2>
            <p>Admin ID: <?php echo $admin['admin_id']; ?></p>
            <p>Email: <?php echo $admin['email']; ?></p>
        </div>
        <!-- Update Form -->
        <div>
            <form method="post" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
                <label for="new_email">Update Email:</label><br>
                <input type="email" name="new_email" id="new_email" value="<?php echo $admin['email']; ?>" required><br>

                <input type="submit" value="Update">
            </form>
        </div>

        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Retrieve form data
            $newEmail = $_POST["new_email"];

            // Check if email is updated
            if ($newEmail != $admin['email']) {
                // Insert a log entry for email update
                $logMessage = "Email updated from '{$admin['email']}' to '$newEmail'";
                $escapedLogMessage = $conn->real_escape_string($logMessage);

                $insertLogQuery = "INSERT INTO logs (admin_id, action) VALUES ($admin_id, '$escapedLogMessage')";
                $conn->query($insertLogQuery);

                // Update email
                $updateEmailQuery = "UPDATE admins SET email = '$newEmail' WHERE admin_id = $admin_id";
                $conn->query($updateEmailQuery);
            }
            // Redirect to dashboard
            header("Location: admin_dashboard.php");
            exit();
        }
        ?>

        <!-- Display success message if email updated -->
        <?php if (isset($_SESSION['email_updated'])) { ?>
            <script>
                // Display success message using SweetAlert
                Swal.fire({
                    title: 'Success',
                    text: 'Email updated successfully!',
                    icon: 'success',
                    timer: 3000
                });
            </script>
        <?php
            // Remove the success message flag from the session
            unset($_SESSION['email_updated']);
        }
        ?>
    </main>
</body>

</html>