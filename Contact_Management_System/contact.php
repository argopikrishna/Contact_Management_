<?php
require_once 'server.php';

// Check if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_SESSION['error_message'])) {
    echo "<script>alert('{$_SESSION['error_message']}');</script>";
    unset($_SESSION['error_message']); // Clear the error message
}
// Function to fetch the list of contacts for the logged-in user
function getContacts($conn, $user_id)
{
    $query = "SELECT id, name, email, phone, address FROM contacts WHERE user_id = '$user_id'";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    return [];
}


// Fetch the list of contacts for the logged-in user
$contacts = getContacts($conn, $_SESSION['user_id']);

// Handle form submission for adding/editing/deleting contacts
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_contact'])) {

        // Retrieve form data
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        // Handle adding a new contact
        $stmt = $conn->prepare("SELECT * FROM contacts WHERE phone = ? AND user_id = ?");
        $stmt->bind_param("si", $phone, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $_SESSION['error_message'] = 'A contact with this phone number already exists.';
            header("Location: contact.php");
            exit();
        } else {

            // Prepare the SQL statement to check if the contact already exists



            $stmt = $conn->prepare("SELECT * FROM contacts WHERE (email = ? AND name = ?) AND user_id = ?");
            $stmt->bind_param("ssi", $email, $name, $_SESSION['user_id']);



            // Execute the prepared statement
            $stmt->execute();

            // Store the result
            $stmt->store_result();

            // Check if the contact already exists
            if ($stmt->num_rows > 0) {
                // Contact already exists, display an error message
                $_SESSION['error_message'] = 'Contact with this name and (email and phone number already exists.';
                header("Location: contact.php");
                exit();
            } else {
                // Contact does not exist, proceed with adding the contact

                // Prepare the SQL statement
                $stmt = $conn->prepare("INSERT INTO contacts (user_id, `name`, email, phone, `address`) VALUES (?, ?, ?, ?, ?)");

                // Bind the variables to the prepared statement as parameters
                $stmt->bind_param("issss", $_SESSION['user_id'], $name, $email, $phone, $address);

                // Execute the prepared statement
                $stmt->execute();

                // Insert a log entry for the action
                $logAction = "Added a contact";
                $logUserId = $_SESSION['user_id'];
                $logQuery = "INSERT INTO logs (action, user_id) VALUES ('$logAction', '$logUserId')";
                $conn->query($logQuery);

                // Redirect to the contact page to prevent form resubmission
                header("Location: contact.php");
                exit();
            }
            // Close the statement
            $stmt->close();
        }
    } elseif (isset($_POST['edit_contact'])) {
        // Handle editing an existing contact

        // Retrieve form data
        $contactId = $_POST['contact_id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        // In the 'edit_contact' section
        $stmt = $conn->prepare("SELECT * FROM contacts WHERE phone = ? AND user_id = ? AND id != ?");
        $stmt->bind_param("sii", $phone, $_SESSION['user_id'], $contactId);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo "<script>alert('A contact with this phone number already exists.');</script>";
        } else {


            // Prepare the SQL statement to check if the contact already exists
            $stmt = $conn->prepare("SELECT * FROM contacts WHERE (email = ? AND phone = ? AND name = ?) AND user_id = ? AND id != ?");
            $stmt->bind_param("sssii", $email, $phone, $name, $_SESSION['user_id'], $contactId);

            // Execute the prepared statement
            $stmt->execute();

            // Store the result
            $stmt->store_result();

            // Check if the contact already exists
            if ($stmt->num_rows > 0) {
                // Contact already exists, display an error message
                echo "<script>alert('Contact exists.');</script>";
            } else {
                // Contact does not exist, proceed with updating the contact

                // Update the contact in the database
                $updateQuery = "UPDATE contacts SET name = '$name', email = '$email', phone = '$phone', address = '$address' WHERE id = '$contactId' AND user_id = '{$_SESSION['user_id']}'";
                $conn->query($updateQuery);

                // Insert a log entry for the action
                $logAction = "Edited a contact";
                $logUserId = $_SESSION['user_id'];
                $logQuery = "INSERT INTO logs (action, user_id) VALUES ('$logAction', '$logUserId')";
                $conn->query($logQuery);

                // Redirect to the contact page to prevent form resubmission
                header("Location: contact.php");
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_contact'])) {
        // Handle deleting an existing contact
        $contactId = $_POST['contact_id'];

        // Delete the contact from the database
        $deleteQuery = "DELETE FROM contacts WHERE id = '$contactId' AND user_id = '{$_SESSION['user_id']}'";
        $conn->query($deleteQuery);

        // Insert a log entry for the action
        $logAction = "Deleted a contact";
        $logUserId = $_SESSION['user_id'];
        $logQuery = "INSERT INTO logs (action, user_id) VALUES ('$logAction', '$logUserId')";
        $conn->query($logQuery);

        // Redirect to the contact page to prevent form resubmission
        header("Location: contact.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Contact | Contact Management System</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" type="text/css" href="./CSS/contact.css">
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
            <h3>Add Contacts</h3>
            <?php
            // Check if a contact ID is provided for editing
            if (isset($_GET['contact_id'])) {
                $contactId = $_GET['contact_id'];

                // Retrieve the contact details from the database
                $query = "SELECT * FROM contacts WHERE id = '$contactId' AND user_id = '{$_SESSION['user_id']}'";
                $result = $conn->query($query);

                if ($result && $result->num_rows > 0) {
                    $contact = $result->fetch_assoc();
            ?>
                    <form method="post" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
                        <label for="name">Name:</label>
                        <input type="text" name="name" id="name" value="<?php echo $contact['name']; ?>" required><br>

                        <label for="email">Email:</label>
                        <input type="email" name="email" id="email" value="<?php echo $contact['email']; ?>" required><br>

                        <label for="phone">Phone:</label>
                        <input type="text" name="phone" id="phone" pattern="\d{10}" value="<?php echo $contact['phone']; ?>" required><br>

                        <label for="address">Address:</label>
                        <textarea name="address" id="address" required><?php echo $contact['address']; ?></textarea><br>

                        <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                        <input type="submit" name="edit_contact" value="Update Contact">
                    </form>
                <?php
                } else {
                    echo "Contact not found.";
                }
            } else {
                ?>
                <form method="post" action="<?php echo $_SERVER["PHP_SELF"]; ?>" enctype="multipart/form-data">
                    <label for="name">Name:</label>
                    <input type="text" name="name" id="name" required><br>

                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" required><br>

                    <label for="phone">Phone:</label>
                    <input type="text" name="phone" id="phone" pattern="\d{10}" required><br>

                    <label for="address">Address:</label>
                    <textarea type="text" name="address" id="address" required></textarea><br>

                    <input class="btn" type="submit" name="add_contact" value="Confirm">
                </form>
            <?php
            }
            ?>
        </div>
        <div class="right-side">
            <h3>Contact List</h3>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($contacts as $contact) { ?>
                    <tr>
                        <td><?php echo $contact['name']; ?></td>
                        <td><?php echo $contact['email']; ?></td>
                        <td><?php echo $contact['phone']; ?></td>
                        <td class="address"><?php echo $contact['address']; ?></td>
                        <td style="display:inline-flex;">
                            <div class="edit-container">
                                <a href="contact.php?contact_id=<?php echo $contact['id']; ?>" class="edit">Edit</a>
                            </div>
                            <div class="delete-container">
                                <form method="post">
                                    <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                    <input type="submit" class="delete" name="delete_contact" value="Delete" onclick="return confirm('Are you sure you want to delete this contact?')">
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
        <div>
</body>

</html>