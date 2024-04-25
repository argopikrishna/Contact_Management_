<?php
// Include the server-side script
require_once 'server.php';

// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page
    header("Location: login.php");
    exit();
}




// Check if there's an error message in the session
if (isset($_SESSION['error_message'])) {
    // Display the error message
    echo "<script>alert('{$_SESSION['error_message']}');</script>";
    // Clear the error message
    unset($_SESSION['error_message']);
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];



// Prepare a SQL query to retrieve user data from the database
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    // Fetch the user data
    $user = $result->fetch_assoc();
} else {
    // Redirect to the login page if user data not found
    header("Location: login.php");
    exit();
}

// Function to fetch the list of contacts for the logged-in user
function getContacts($conn, $user_id, $search_term = null)
{
    // Check if a search term is provided
    if ($search_term) {
        // Prepare a SQL query to fetch contacts that match the search term
        $sql = "SELECT id, name, email, phone, address FROM contacts WHERE user_id = ? AND (name LIKE ? OR email LIKE ?)";
        $stmt = $conn->prepare($sql);
        $search_term = $search_term . '%'; // Add a wildcard at the end of the search term
        $stmt->bind_param('iss', $user_id, $search_term, $search_term);
    } else {
        // Prepare a SQL query to fetch all contacts for the user
        $sql = "SELECT id, name, email, phone, address FROM contacts WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
    }

    // Execute the query
    $stmt->execute();

    // Get the result of the query
    $result = $stmt->get_result();

    // Check if any contacts were found
    if ($result->num_rows > 0) {
        // Fetch all contacts and return them
        return $result->fetch_all(MYSQLI_ASSOC);
    } else {
        // Return a message if no contacts were found
        return array(array("message" => "No Contacts found..."));
    }

    // Return an empty array if no contacts were found
    return [];
}

// If the 'reset' GET parameter is set, unset the 'search' GET parameter
if (isset($_GET['reset'])) {
    unset($_GET['search']);
}

// Check if a search term is set
if (isset($_GET['search'])) {
    // Sanitize the search term
    $search_term = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
    // Fetch the contacts that match the search term
    $contacts = getContacts($conn, $_SESSION['user_id'], $search_term);
} else {
    // Fetch all contacts if no search term is set
    $contacts = getContacts($conn, $_SESSION['user_id']);
}

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $newUsername = $_POST["new_username"];
    $newEmail = $_POST["new_email"];

    // Check if username is updated
    if ($newUsername != $user['username']) {
        // Insert a log entry for username update
        $logMessage = "Username updated from '{$user['username']}' to '$newUsername'";
        $escapedLogMessage = $conn->real_escape_string($logMessage);

        $insertLogQuery = "INSERT INTO logs (user_id, action) VALUES ('$user_id', '$escapedLogMessage')";
        $conn->query($insertLogQuery);

        // Update username
        $updateUsernameQuery = "UPDATE users SET username = '$newUsername' WHERE id = '$user_id'";
        $conn->query($updateUsernameQuery);
    }

    // Check if email is updated
    if ($newEmail != $user['email']) {
        // Check if new email already exists
        $checkEmailQuery = "SELECT * FROM users WHERE email = '$newEmail'";
        $result = $conn->query($checkEmailQuery);

        if ($result->num_rows > 0) {
            $_SESSION['error_message'] = 'This email is already in use. Please use a different email.';
            header("Location: dashboard.php");
            exit();
        } else {
            // Insert a log entry for email update
            $logMessage = "Email updated from '{$user['email']}' to '$newEmail'";
            $escapedLogMessage = $conn->real_escape_string($logMessage);

            $insertLogQuery = "INSERT INTO logs (user_id, action) VALUES ('$user_id', '$escapedLogMessage')";
            $conn->query($insertLogQuery);

            // Update email
            $updateEmailQuery = "UPDATE users SET email = '$newEmail' WHERE id = '$user_id'";
            $conn->query($updateEmailQuery);
        }
    }

    // Check if a new profile picture is uploaded
    if (!empty($_FILES["profile_picture"]["name"])) {
        $targetDirectory = "uploads/";
        $profilePicture = $_FILES["profile_picture"]["name"];
        $targetFile = $targetDirectory . basename($profilePicture);
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Check if the uploaded file is a valid JPG or JPEG image
        if ($imageFileType == "jpg" || $imageFileType == "jpeg") {
            move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFile);

            // Update profile picture
            $updateProfilePictureQuery = "UPDATE users SET profile_picture = '$profilePicture' WHERE id = '$user_id'";
            $conn->query($updateProfilePictureQuery);
        } else {
            $_SESSION['profile_update_error'] = "Invalid file format. Only JPG and JPEG files are allowed.";
            header("Location: dashboard.php");
            exit();
        }
    }

    // Set a session variable to indicate that the profile has been updated
    $_SESSION['profile_updated'] = true;
    redirectToDashboard();
}

// Function to redirect to the dashboard page
function redirectToDashboard()
{
    // Redirect to the dashboard page
    header("Location: dashboard.php");
    // Terminate the current script
    exit();
}

// Function to display error messages
function displayErrorMessage()
{
    // Check if there's an error message in the session
    if (isset($_SESSION['error_message'])) {
        // Display the error message
        echo "<script>alert('{$_SESSION['error_message']}');</script>";
        // Clear the error message from the session
        unset($_SESSION['error_message']);
    }
}

// Function to get user data from the database
function getUserData($conn, $user_id)
{
    // Prepare a SQL query to retrieve user data from the database
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    // Bind the user ID to the SQL query
    $stmt->bind_param("i", $user_id);
    // Execute the SQL query
    $stmt->execute();
    // Get the result of the SQL query
    $result = $stmt->get_result();
    // Fetch the user data and return it
    return $result->fetch_assoc();
}

// Function to get contacts based on a search term
function getContactsBasedOnSearch($conn, $user_id)
{
    // Get the search term from the GET parameters
    $search_term = $_GET['search'] ?? '';
    // Prepare a SQL query to retrieve contacts that match the search term
    $sql = "SELECT * FROM contacts WHERE user_id = ? AND name LIKE ?";
    $stmt = $conn->prepare($sql);
    // Bind the user ID and the search term to the SQL query
    $stmt->bind_param("is", $user_id, $search_term);
    // Execute the SQL query
    $stmt->execute();
    // Get the result of the SQL query
    $result = $stmt->get_result();
    // Fetch all contacts and return them
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to update the username of a user
function updateUsername($conn, $user_id, $oldUsername, $newUsername)
{
    // Prepare a SQL query to update the username of a user
    $sql = "UPDATE users SET username = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    // Bind the new username and the user ID to the SQL query
    $stmt->bind_param("si", $newUsername, $user_id);
    // Execute the SQL query
    $stmt->execute();
}

// Function to update the email of a user
function updateEmail($conn, $user_id, $oldEmail, $newEmail)
{
    // Prepare a SQL query to update the email of a user
    $sql = "UPDATE users SET email = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    // Bind the new email and the user ID to the SQL query
    $stmt->bind_param("si", $newEmail, $user_id);
    // Execute the SQL query
    $stmt->execute();
}

// Function to update the profile picture of a user
function updateProfilePicture($conn, $user_id)
{
    // Define the target directory for uploaded files
    $target_dir = "uploads/";
    // Define the target file path
    $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
    // Move the uploaded file to the target directory
    move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file);
    // Prepare a SQL query to update the profile picture of a user
    $sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    // Bind the target file path and the user ID to the SQL query
    $stmt->bind_param("si", $target_file, $user_id);
    // Execute the SQL query
    $stmt->execute();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Dashboard | Contact Management System</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" type="text/css" href="./CSS/dashboard.css">
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
        <div class="left-side">
            <div class="user-information">
                <h2>User Information</h2>
                <p>Username: <?php echo $user['username']; ?></p>
                <p>Email: <?php echo $user['email']; ?></p>
                <img src="uploads/<?php echo $user['profile_picture']; ?>" alt="Profile Picture" width="200">
            </div>

            <!-- Update Form -->
            <div>
                <form method="post" action="<?php echo $_SERVER["PHP_SELF"]; ?>" enctype="multipart/form-data">
                    <label for="new_username">Username:</label>
                    <input type="text" name="new_username" id="new_username" value="<?php echo $user['username']; ?>" required><br>

                    <label for="new_email">Email:</label>
                    <input type="email" name="new_email" id="new_email" value="<?php echo $user['email']; ?>" required><br>

                    <label for="profile_picture">Profile Picture (JPG/JPEG only):</label>
                    <input type="file" name="profile_picture" id="profile_picture"><br>

                    <input type="submit" value="Update">
                </form>
            </div>
        </div>
        <div class="right-side">
            <div>
                <h2>Search Contacts</h2>
                <form method="get" class="search-form" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
                    <input type="text" name="search" placeholder="Search contacts..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <input type="submit" value="Search">
                    <input type="submit" name="reset" value="CLEAR">
                </form>
            </div>
            <div>
                <h2>Contact List</h2>
                <table>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                    </tr>
                    <?php
                    if (isset($contacts[0]['message'])) {
                        echo "<td colspan=3>" . $contacts[0]['message'] . "</td>";
                    } else {
                        foreach ($contacts as $contact) { ?>
                            <tr>
                                <td><?php echo $contact['name']; ?></td>
                                <td><?php echo $contact['email']; ?></td>
                                <td><?php echo $contact['phone']; ?></td>
                            </tr>
                    <?php }
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>
<!-- Display success message if profile updated -->
<?php if (isset($_SESSION['profile_updated'])) { ?>
        <script>
            // Display success message using SweetAlert
            Swal.fire({
                title: 'Success',
                text: 'Profile updated successfully!',
                icon: 'success',
                timer: 3000
            });
        </script>
    <?php
        // Remove the success message flag from the session
        unset($_SESSION['profile_updated']);
    }

    // Display error message if profile update encountered an error
    if (isset($_SESSION['profile_update_error'])) {
        echo '<script>
                // Display error message using SweetAlert
                Swal.fire({
                    title: "Error",
                    text: "' . $_SESSION['profile_update_error'] . '",
                    icon: "error",
                    timer: 5000
                });
            </script>';
        // Remove the error message from the session
        unset($_SESSION['profile_update_error']);
    }
    ?>
</body>

</html>