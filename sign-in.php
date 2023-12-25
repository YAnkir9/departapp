<?php

include "config.php"; // Include the database connection

if (isset($_POST['submit'])) {
    $fname = $_POST['first_name'];
    $lname = $_POST['last_name'];
    $cred = $_POST['credential'];
    $email = $_POST['email'];
    $uname = $_POST['user_name'];
    $pass = $_POST['_password'];
    $password = password_hash($pass, PASSWORD_DEFAULT);
    $courseId = '';

    if ($cred === 'student' && isset($_POST['course_id'])) {
        $courseId = $_POST['course_id'];
    }

    // Use prepared statements to insert data
    $stmt = $conn->prepare("INSERT INTO `users`(`first_name`, `last_name`, `email`,`credential`, `user_name`, `_password`, `course_id`)
                            VALUES (?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sssssss", $fname, $lname, $email, $cred, $uname, $password, $courseId);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Registration successful. Please wait for admin approval.';
        header('Location: login.php');
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close(); // Close the prepared statement
    $conn->close(); // Close the database connection
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sign In</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <link rel="stylesheet" href="login.css">
</head>
<style>
.back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 20px;
            color: #000080; /* Adjust the color of the arrow */
            cursor: pointer;
        }</style>
<body>
<div class="wrapper">
    <div id="formContent">
    <a href="login.php" class="back-button">
            <i class="fa fa-arrow-left"></i> <!-- Font Awesome arrow icon -->
        </a>

    <h2 class="active"> Sign In </h2>
      <div class="fadeIn first">
        <img src="image/Logo.png" id="icon" alt="User Icon" />
      </div>
      <form action="" method="post"> 
        <input type="text" class="fadeIn second" name="first_name" placeholder="First Name" required="required">
        <input type="text" class="fadeIn second" name="last_name" placeholder="Last Name" required="required">
        <select id="credential" class="fadeIn second" name="credential" required="required" >
            <option value="" style="color: #cccccc;">Select Credentials</option>
            <option value="student">Student</option>
            <option value="faculty">Faculty</option>
        </select>
        
        <input type="text" class="fadeIn second" name="email" placeholder="Email" required="required">
        
        <input type="text" class="fadeIn second" name="user_name" placeholder="Username" required="required">
        <input type="password" class="fadeIn second" name="_password" placeholder="Password" required="required">
        <div id="courseIdField" class="form-group invisible">
            <select name="course_id" class="fadeIn second" required="required">
                <option value="">Select Course</option>
                <option value="1">Integrated MSc[CS-1]</option>
                <option value="2">Integrated MSc[CS-2]</option>
                <option value="3">Integrated MSc[CS-3]</option>
                <option value="4">Integrated MSc[CS-4]</option>
            </select>
        </div>
        <input type="submit" class="fadeIn fourth" name="submit">
      </form>
      <script>
    $(document).ready(function () {
        $('#credential').change(function () {
            if ($(this).val() === 'student') {
                $('#courseIdField').removeClass('invisible');
                $('select[name="course_id"]').attr('required', 'required');
            } else {
                $('#courseIdField').addClass('invisible');
                $('select[name="course_id"]').removeAttr('required');
            }
        });
        
        // Trigger the change event on page load
        $('#credential').change();
    });
</script>
      
</body>
</html>
