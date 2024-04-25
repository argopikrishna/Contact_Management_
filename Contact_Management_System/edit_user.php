<?php
require_once 'server.php';

// Check if the user is not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Check if the user ID is provided in the URL parameter
if (!isset($_GET['user_id'])) {
    header("Location: user_management.php");
    exit();
}

// Retrieve the user ID from the URL parameter
$user_id = $_GET['user_id'];

// Prepare and execute the SELECT statement
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if the user exists
if ($result->num_rows == 0) {
    header("Location: user_management.php");
    exit();
}

// Fetch the user details
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Prepare the SQL statement to check if the user already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $stmt->store_result();

    // Check if the user already exists
    if ($stmt->num_rows > 0) {
        // User already exists, display an error message
        echo "<script>alert('A user with this email already exists.');</script>";
    } else {
        // User does not exist, proceed with updating the user

        // Prepare and execute the UPDATE statement
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
        $stmt->bind_param("sssi", $username, $email, $password, $user_id);
        $stmt->execute();

        // Redirect back to user management page
        header("Location: user_management.php");
        exit();
    }
    $stmt->close();
}
?>

<!-- The rest of your HTML code -->

<form method="post" action="edit_user.php?user_id=<?php echo $user_id; ?>">
    <!-- The rest of your form code -->
</form>

<!-- The rest of your HTML code -->

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link rel="stylesheet" type="text/css" href="./CSS/edit_user.css">
</head>


<body>
    <div class="login">
        <h2>Edit User</h2>
        <form method="post" action="edit_user.php?user_id=<?php echo $user_id; ?>">
            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" value="<?php echo $user['username']; ?>" required><br>

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo $user['email']; ?>" required><br>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" pattern="\d{8,}" value="<?php echo $user['password']; ?>" required><br>

            <input type="submit" value="Update">
        </form>
    </div>
</body>

</html>