<?php
// Start the session if it hasn't been started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection file
include "config.php";

// Check if the user is logged in
if (!isset($_SESSION['User_id'])) {
  header("Location: login.php");
  exit();
}

// Get the user ID from the session
$userID = $_SESSION['User_id'];

// Retrieve user details based on User_id
$stmt = $conn->prepare("SELECT * FROM users WHERE User_id = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get the user's role
$role = $user['credential'];

// Define the navigation links based on the user's role
$navLinks = array();

if ($role === 'admin') {
  $navLinks = array(

    array(
      'href' => 'admin_home.php',
      'icon' => 'fas fa-menorah',
      'label' => 'Dashboard'
    ),
    array(
      'href' => 'aproved_users.php',
      'icon' => 'fas fa-user',
      'label' => 'Users'
    ),
    array(
      'href' => '#',
      'icon' => 'fas fa-info-circle',
      'label' => 'About'
    ),

    array(
      'href' => 'login.php',
      'icon' => 'fas fa-sign-out-alt',
      'label' => 'Log out'
    )
  );
} elseif ($role === 'student') {
  $navLinks = array(


    array(
      'href' => 'student_exam.php',
      'icon' => 'fas fa-menorah',
      'label' => 'Dashboard'
    ),

    array(
        'href' => 'student_exam_list.php',
        'icon' => 'fas fa-comment',
        'label' => 'fas fa-graduation-cap'
    ),
    array(
        'href' => 'login.php',
        'icon' => 'fas fa-sign-out-alt',
        'label' => 'Log out'
    )
   );
} elseif ($role === 'faculty') {
  $navLinks = array(

    array(

      'href' => 'faculty_exam.php',
      'icon' => 'fas fa-menorah',
      'label' => 'Dashboard'
    ),
    array(
      'href' => 'facultyExamList.php',
      'icon' => 'fas fa-file-alt',
      'label' => 'Exam list'
    ),
    array(
      'href' => 'faculty_create_exam.php',
      'icon' => 'fas fa-file-alt',
      'label' => 'Create Exam'
    ),
    array(
      'href' => 'faculty_add_question.php',
      'icon' => 'fas fa-file-alt',
      'label' => 'Add Questions'
    ),
    array(
      'href' => '#',
      'icon' => 'fas fa-info-circle',
      'label' => 'About'
    ),
    array(
      'href' => 'login.php',
      'icon' => 'fas fa-sign-out-alt',
      'label' => 'Log out'
    )
  );
}
?>
