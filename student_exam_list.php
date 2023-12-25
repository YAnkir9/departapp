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
    $stmt = $conn->prepare("SELECT first_name, last_name, User_name, User_id, Course_id FROM users WHERE User_id = ?");
    $stmt->bind_param("i", $_SESSION['User_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Course of the particular student
    $course = $user['Course_id'];

    // Fetch the course name from the courses table
    $courseStmt = $conn->prepare("SELECT * FROM cources WHERE Course_id = ?");
    if (!$courseStmt) {
        die("Prepare failed: " . $conn->error); // Display error message if prepare() fails
    }

    $courseStmt->bind_param("i", $course);
    $courseStmt->execute();

    $result = $courseStmt->get_result();
    if (!$result) {
        die("Execute failed: " . $courseStmt->error); // Display error message if execute() fails
    }

    $userCourse = $result->fetch_assoc();


    // Function to check if the result exists for a given exam ID and user ID
    function checkExamResultExists($examId, $userId) {
        global $conn;

        $stmt = $conn->prepare("SELECT * FROM mcq_results WHERE Exam_id = ? AND User_id = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ii", $examId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if a row exists in the result set
        $exists = $result->num_rows > 0;

        $stmt->close();

        return $exists;
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
<link rel="stylesheet" href="s_exam_list.css">

<script src="close.js"></script>
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
                <span class="logo_name">Student</span>
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
            <i class="fas fa-user-graduate"></i>
        </div>
         
        <!-- Your content goes here -->

        <div class="user-details">
        <div class="user-details-column">
                <div class="user-details-container">
                    <label for="username" class="form-label">Course:</label>
                    <span class="user-name"><?php echo $userCourse['Course_name']; ?></span>
                </div>
            </div>
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
<div class="row">
                <div class="col-sm-12">
                    <div class="user-details-container">
                        <div class="mb-1">
                            <label for="subjects" class="form-label">Exam Lists:</label>
                            <?php
                            // Fetch subjects associated with the course
                            $subjectStmt = $conn->prepare("SELECT * FROM subjects WHERE Course_id = ?");
                            if (!$subjectStmt) {
                                die("Prepare failed: " . $conn->error);
                            }
                            $subjectStmt->bind_param("i", $course);
                            $subjectStmt->execute();
                            $subjectResult = $subjectStmt->get_result();

                            // Iterate over each subject
                            while ($subject = $subjectResult->fetch_assoc()) {
                                echo '<div class="subject-container">';
                                echo '<div class="subject-name">' . $subject['Subject_name'] . '</div>';

                                // Fetch exams associated with the subject
                                $examStmt = $conn->prepare("SELECT * FROM exam WHERE Subject_id = ? Order by Create_time Desc" );
                                if (!$examStmt) {
                                    die("Prepare failed: " . $conn->error);
                                }
                                $examStmt->bind_param("i", $subject['Subject_id']);
                                $examStmt->execute();
                                $examResult = $examStmt->get_result();

                                // Iterate over each exam
                                while ($exam = $examResult->fetch_assoc()) {
                                    echo '<div class="exam-container">';

                                    // Check if the result exists for the exam
                                    $resultExists = checkExamResultExists($exam['Exam_id'], $user['User_id']);
    
                                    if ($resultExists) {
                                        echo '<i class="fas fa-check-circle"></i> ';
                                        echo '<a class="exam-name" href="student_submit_exam.php?exam_id=' . $exam['Exam_id'] . '">';
                                    } else {
                                        echo '<a class="exam-name" href="student_exam.php?exam_id=' . $exam['Exam_id'] . '">';
                                    }
                                        echo $exam['Exam_name'] . '</a>';
                                    echo '</div>';
                                }

                                $examStmt->close();

                                echo '</div>';
                            }

                            $subjectStmt->close();
                            ?>
                        </div>
                    </div>
                </div>
            </div>

</div>
</body>
</html>

