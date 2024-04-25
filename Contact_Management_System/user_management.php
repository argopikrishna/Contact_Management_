<?php
require_once 'server.php';

// Check if the user is not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle add user form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    // Retrieve form data
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Insert the new user into the database
    $query = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
    $conn->query($query);
}

// Handle delete user action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Delete the user's logs from the database
    $query = "DELETE FROM logs WHERE user_id = '$user_id'";
    $conn->query($query);

    // Delete the user's contacts from the database
    $query = "DELETE FROM contacts WHERE user_id = '$user_id'";
    $conn->query($query);

    // Delete the user from the database
    $query = "DELETE FROM users WHERE id = '$user_id'";
    $conn->query($query);
}

// Handle edit user form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    // Retrieve form data
    $user_id = $_POST['user_id'];
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Update the user in the database
    $query = "UPDATE users SET username = '$username', email = '$email', password = '$password' WHERE id = '$user_id'";
    $conn->query($query);
}

$query = "SELECT * FROM users";
$result = $conn->query($query);
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <title>User Management | Contact Management System</title>
    <link rel="stylesheet" type="text/css" href="./CSS/user_management.css">
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
        <h3>Add User</h3>
        <form method="post" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required><br><br>

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required><br><br>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required><br><br>

            <input type="submit" name="add_user" value="Add User">
        </form>

        <h3>Users</h3>
        <table>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th style="text-align:right;padding-right:15rem;">Action</th>
            </tr>
            <?php foreach ($users as $user) { ?>
                <tr>
                    <td><?php echo $user['username']; ?></td>
                    <td><?php echo $user['email']; ?></td>
                    <td style="text-align:right;padding-right:10rem;">
                        <a href="edit_user.php?user_id=<?php echo $user['id']; ?>" class="edit">Edit</a>
                        <a href="?action=delete&user_id=<?php echo $user['id']; ?>" class="delete">Delete</a>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </main>
</body>

</html>