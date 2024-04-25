<?php
require_once 'server.php';

// Check if the admin is already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

// Check if the admin login form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['admin_login'])) {
    // Retrieve form data
    $adminEmail = $_POST["admin_email"];
    $adminPassword = $_POST["admin_password"];

    // Check admin credentials against the database
    $query = "SELECT * FROM admins WHERE email = '$adminEmail' AND password = '$adminPassword'";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        // Admin credentials are valid
        $admin = $result->fetch_assoc();
        $_SESSION['admin_id'] = $admin['admin_id'];
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $adminError = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Login | Contact Management System</title>
    <link rel="stylesheet" type="text/css" href="./CSS/admin_login.css">
</head>


<body>
    <div class="login">
        <h2>Admin Login</h2>
        <?php if (isset($adminError)) { ?>
            <p><?php echo $adminError; ?></p>
        <?php } ?>
        <form method="POST" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
            <label for="admin_email">Email:</label>
            <input type="email" name="admin_email" id="admin_email" required><br>

            <label for="admin_password">Password:</label>
            <input type="password" name="admin_password" id="admin_password" required><br>

            <input type="submit" name="admin_login" value="Login">
        </form>
        <p>if you are not admin?<a href="login.php">Click Here</a></p>
    </div>
</body>

</html>