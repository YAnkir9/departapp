<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the file that defines the navigation links
include 'nav.php';

// Include the database connection file
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['User_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data from the database for the logged-in user
$stmt = $conn->prepare("SELECT first_name, last_name, User_name, User_id FROM users WHERE User_id = ?");
$stmt->bind_param("i", $_SESSION['User_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    $sql = "SELECT * FROM `users` WHERE `User_id`='$user_id'";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $fname = $row['first_name'];
        $lname = $row['last_name'];
        $cred = $row['credential'];
        $email = $row['Email'];
        $uname = $row['User_name'];
    }
}

if (isset($_POST['submit'])) {
    $fname = $_POST['first_name'];
    $lname = $_POST['last_name'];
    $cred = $_POST['credential'];
    $email = $_POST['email'];
    $uname = $_POST['user_name'];
    $pass = $_POST['_password'];
    $password = password_hash($pass, PASSWORD_DEFAULT);
    
    // Update the password in the query
    $sql = "UPDATE `users` SET `first_name`='$fname', `last_name`='$lname',
               `Email`='$email',`User_name`='$uname', `_Password`='$password' WHERE `User_id`='$user_id'";
    $result = $conn->query($sql);

    if ($result == TRUE) {
        echo "<script>alert('Record updated successfully.');</script>";
        header("Location: aproved_users.php");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<link rel="stylesheet" href="indx.css">
<link rel="stylesheet" href="home.css">

<script src="close.js"></script>
<style>/* Style the form container */
form {
    max-width: 400px; /* Adjust the maximum width of the form container */
    margin: 0 auto; /* Center the form horizontally on the page */
    padding: 20px; /* Add some padding to the form container */
    border: 1px solid #ddd; /* Add a border around the form container */
    border-radius: 10px; /* Add rounded corners to the form container */
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Add a subtle box shadow */
    overflow-y: auto; /* Add this property to enable vertical scrollbar if content overflows */
    max-height: 80vh; 
}

/* Style the fieldset (form sections) */
fieldset {
    border: 1px solid #ddd; /* Add a border around the fieldset */
    padding: 10px; /* Add some padding to the fieldset */
    border-radius: 5px; /* Add rounded corners to the fieldset */
    margin-bottom: 20px; /* Add spacing between fieldsets */
}

/* Style form labels */
.form-label {
    font-weight: bold; /* Make labels bold */
}

/* Style form input fields */
.form-control {
    width: 100%; /* Make input fields take full width of their container */
    padding: 10px; /* Add padding to input fields */
    margin-bottom: 10px; /* Add spacing between input fields */
    border: 1px solid #ddd; /* Add a border around input fields */
    border-radius: 5px; /* Add rounded corners to input fields */
}

/* Style the form select element */
.form-select {
    width: 100%; /* Make select element take full width of its container */
    padding: 10px; /* Add padding to the select element */
    margin-bottom: 10px; /* Add spacing between the select element and other fields */
    border: 1px solid #ddd; /* Add a border around the select element */
    border-radius: 5px; /* Add rounded corners to the select element */
}

/* Style the form submit button */
.btn-primary {
    background-color: #007bff; /* Button background color */
    color: white; /* Button text color */
    padding: 10px 20px; /* Add padding to the button */
    border: none; /* Remove button border */
    border-radius: 5px; /* Add rounded corners to the button */
    cursor: pointer; /* Add a pointer cursor on hover */
}

/* Style the form submit button on hover */
.btn-primary:hover {
    background-color: #0056b3; /* Change background color on hover */
}
</style>
</head>
<body>
    <!-- Top navigation bar with toggle button (for mobile) -->
    <header>
        <nav>
            <button class="toggle-button" id="toggleSidebar">☰</button>
            <div class="logo"><span class="nav_image">
                    <img src="image/Logo.png" alt="logo_img" />
                </span>
            </div><br>
            <div>
                <p class="head">Student Progressive Assessment System</p>
            </div>
            <ul class="top-nav">
            </ul>
        </nav>
    </header>

    <!-- Left side sliding navigation bar -->
    <aside id="sidebar">
    <button class="close-button" id="closeSidebar">✖</button>
    <ul class="sidebar-nav">
        <div class="menu_container">

        <div class="menu_items">
        <div class="logo logo_items flex">
                <span class="nav_image">
                    <img src="image/Logo.png" alt="logo_img" />
                </span>
                <span class="logo_name">Admin</span>
            </div>

        <ul class="menu_item">
                    <?php foreach ($navLinks as $link): ?>
                        <li class="item">
                            <a href="<?php echo $link['href']; ?>" class="link flex">
                                <?php if (isset($link['icon'])): ?>
                                    <i class="<?php echo $link['icon']; ?>"></i>
                                <?php endif; ?>
                                <span><?php echo $link['label']; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </ul>
</aside>    <!-- Content Area on the Right Side -->
    <div id="content">
        <div class="main-top">
            <i class="fas fa-user-cog admin-icon"></i>
        </div>
         
        <!-- Your content goes here -->
        <div class="user-details">
            <div class="user-details-column">
                <div class="user-details-container">
                    <label for="name" class="form-label">Name:</label>
                    <span class="user-name"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></span>
            </div>
    </div>
    <div class="user-details-column">
        <div class="user-details-container">
            <label for="username" class="form-label">Username:</label>
            <span class="user-name"><?php echo $user['User_name']; ?></span>
        </div>
    </div>
    <div class="user-details-column">
        <div class="icon-container">
            <a href="#">
                <i class="fas fa-info-circle">   Profile</i>
            </a>
        </div>
    </div>

</div>
<div class="container">
<form method="POST">
                <fieldset>
                    <legend>Personal information:</legend>

                    <div class="mb-3">
                        <label for="first_name" class="form-label">First name:</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $fname; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last name:</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $lname; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="credential" class="form-label">Credential:</label>
                        <select class="form-select" id="credential" name="credential" required>
                            <option value="student" <?php if ($cred === 'student') echo 'selected'; ?>>STUDENT</option>
                            <option value="faculty" <?php if ($cred === 'faculty') echo 'selected'; ?>>FACULTY</option>
                            <option value="admin" <?php if ($cred === 'admin') echo 'selected'; ?>>ADMIN</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="user_name" class="form-label">User name:</label>
                        <input type="text" class="form-control" id="user_name" name="user_name" value="<?php echo $uname; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="_password" class="form-label">Password:</label>
                        <input type="password" class="form-control" id="_password" name="_password" value="<?php echo $pass; ?>" required>
                    </div>

                    <button type="submit" name="submit" class="btn btn-primary">Submit</button>
                </fieldset>
            </form>
</div>
</body>
</html>

