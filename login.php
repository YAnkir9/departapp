<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
// Start the session
session_start();

// Include the database connection file
include "config.php";

// Initialize error message
$error = '';

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the form data
    $username = $_POST['User_name'];
    $password = $_POST['_Password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE User_name = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($result->num_rows > 0 && password_verify($password, $user['_Password'])) {
        // Valid user
        $_SESSION['User_name'] = $user['User_name'];
        $_SESSION['credential'] = $user['credential'];
        $_SESSION['is_approved'] = $user['is_approved'];
        $_SESSION['User_id'] = $user['User_id']; // Store the User ID in a session variable

        $is_approved = $_SESSION['is_approved'];

        if ($_SESSION['credential'] == 'admin') {
            header('Location: admin_home.php');
            exit();
        } elseif ($_SESSION['credential'] == 'student' && $is_approved == 1) {
            header("Location: student_home.php");
            exit();
        } elseif ($_SESSION['credential'] == 'faculty' && $is_approved == 1) {
            header("Location: faculty_home.php");
            exit();
        } else {
            $error = "Your registration is under process. Please try logging in after we inform you.";
        }
    } else {
        $error = 'Invalid username or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Login Form</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<link rel="stylesheet" href="login.css">
</head>
<body>
  <div class="wrapper">
    <div id="formContent">
      <h2 class="active"> Sign In </h2>
      <div class="fadeIn first">
        <img src="image/Logo.png" id="icon" alt="User Icon" />
      </div>
      <form action="" method="post">
        <input type="text" class="fadeIn second" name="User_name" placeholder="Username">
        <input type="password" class="fadeIn second" name="_Password" placeholder="Password">
        <input type="submit" class="fadeIn fourth" name="submit">
      </form>
      <div class="error-msg"><?php echo $error; ?></div>
      <div id="formFooter">
        <a class="underlineHover" href="sign-in.php">Click here for registration request</a>
      </div>
    </div>
  </div>
</body>
</html>
