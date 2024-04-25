<?php
require_once 'server.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    // email validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Check if the email is already registered in the database
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email address is already registered.";
        } else {
            // File upload handling
            $targetDirectory = "uploads/"; // Directory to store uploaded files

            $profilePicture = $_FILES["profile_picture"]["name"];
            $profilePictureTmp = $_FILES["profile_picture"]["tmp_name"];
            $profilePictureFilename = uniqid() . '_' . $profilePicture;
            $targetFile = $targetDirectory . $profilePictureFilename;

            if (move_uploaded_file($profilePictureTmp, $targetFile)) {
                $insertQuery = "INSERT INTO users (username, email, password, profile_picture) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("ssss", $username, $email, $password, $profilePictureFilename);
                $stmt->execute();

                header("Location: login.php?success=1");
                exit();
            } else {
                $error = "Failed to upload the profile picture.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Register | Contact Management System</title>
    <link rel="stylesheet" type="text/css" href="./CSS/register.css">
</head>

<body>
    <div class="register">
        <h2>Register</h2>
        <?php if (isset($error)) { ?>
            <p><?php echo $error; ?></p>
        <?php } ?>
        <form method="post" action="<?php echo $_SERVER["PHP_SELF"]; ?>" enctype="multipart/form-data">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required><br>

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required><br>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" pattern="\d{8,}" required><br>

            <label for="profile_picture">Profile Picture:</label>
            <input type="file" name="profile_picture" id="profile_picture"><br>

            <input type="submit" value="Register">
        </form>
        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>

</html>