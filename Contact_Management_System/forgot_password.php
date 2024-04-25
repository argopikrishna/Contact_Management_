<?php
// Include your server file here
require_once 'server.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } else if ($password != $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if the username and email match a user in the database
        $query = "SELECT * FROM users WHERE username = '$username' AND email = '$email'";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            // Passwords match, update the user's password in the database
            $query = "UPDATE users SET password = '$password' WHERE username = '$username' AND email = '$email'";
            if ($conn->query($query) === TRUE) {
                $success = "Password has been updated.";
            } else {
                $error = "Error updating password: " . $conn->error;
            }
        } else {
            $error = "The username or email is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Forgot Password | Contact Management System</title>
    <link rel="stylesheet" type="text/css" href="./CSS/forgot_password.css">
</head>

<body>
    <div class="login">
        <h2>Forgot Password</h2>
        <?php if (isset($error)) { ?>
            <p><?php echo $error; ?></p>
        <?php } ?>
        <?php if (isset($success)) { ?>
            <p><?php echo $success; ?></p>
        <?php } ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required><br>

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required><br>

            <label for="password">New Password:</label>
            <input type="password" name="password" id="password" pattern="\d{8,}" required><br>

            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" pattern="\d{8,}" required><br>

            <input type="submit" value="Reset Password">
        </form>
        <p>Remembered your password? <a href="login.php">Login</a></p>
    </div>
</body>

</html>