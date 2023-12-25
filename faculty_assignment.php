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

// Initialize variables to store error messages
$uploadErrors = [];
$successMsg = '';

// Check if the AJAX request is made to fetch subjects
if (isset($_POST['fetch_subjects'])) {
    $selectedCourseId = $_POST['course'];
    $facultyId = $_SESSION['User_id'];

    try {
        $stmt = $conn->prepare("SELECT Subject_id, Subject_name FROM subjects WHERE Course_id = ? AND User_id = ? ORDER BY Subject_name");
        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param("ii", $selectedCourseId, $facultyId);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception($stmt->error);
        }

        $subjects = $result->fetch_all(MYSQLI_ASSOC);

        // Send the subjects as a JSON response
        echo json_encode($subjects);
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }

    exit();
}
if (isset($_POST['fetch_topics'])) {
    $selectedSubjectId = $_POST['subject'];
    // $facultyId = $_SESSION['User_id'];

    $stmt = $conn->prepare("SELECT topic_id, topic_name FROM topics WHERE subject_id = ? ORDER BY topic_name");
    $stmt->bind_param("i", $selectedSubjectId);
    $stmt->execute();
    $result = $stmt->get_result();
    $topics = $result->fetch_all(MYSQLI_ASSOC);

    // Send the subjects as a JSON response
    echo json_encode($topics);
    exit();
}
// Check if the user is logged in as a faculty member
if ($_SESSION['credential'] == 'faculty' && $_SESSION['is_approved'] == 1) {
    try {
        // Fetch courses associated with the faculty member
        $facultyId = $_SESSION['User_id'];
        $stmt = $conn->prepare("SELECT Course_id, Course_name FROM cources WHERE Course_id IN (SELECT Course_id FROM subjects WHERE User_id = ?) ORDER BY Course_name");
        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param("i", $facultyId);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception($stmt->error);
        }

        $courses = $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
        // You can redirect to an error page or display an error message as per your requirement
        exit();
    }
} else {
    // Redirect to login page or display an error message
    header("Location: login.php");
    exit();
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asgnName = $_POST['assignment_name'];
    $courseId = $_POST['course'];
    $topicId = $_POST['topic'];
    $subjectId = $_POST['subject'];
    $dueDate = $_POST['due_date'];

    // Check if a file is selected for upload
    if (empty($_FILES['file']['name'])) {
        $uploadErrors[] = 'Please select a PDF file to upload.';
    } else {
        $targetDir = 'uploads/';
        $targetFile = $targetDir . basename($_FILES['file']['name']);
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Check if the file is a PDF
        if ($fileType !== 'pdf') {
            $uploadErrors[] = 'Only PDF files are allowed.';
        } else {
            // Upload the file
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
                $uploadErrors[] = 'Error uploading the file.';
            } else {
                // File uploaded successfully
                $successMsg = 'File uploaded successfully.';

                // Prepare and execute the SQL statement to insert the assignment

                $sql = "INSERT INTO assignment (assignment_name, User_id, Course_id, Subject_id, topic_id, 
                ass_upload, due_date, upload_time) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

                $stmt = $conn->prepare($sql);

                // Check if prepare was successful
                if (!$stmt) {
                    die("Prepare failed: " . $conn->error);
                }
                echo "SQL Query: $sql";

                // Bind parameters with appropriate data types
                if (!$stmt->bind_param("siiiiss", $asgnName, $_SESSION['User_id'], $courseId, $subjectId,$topicId, $targetFile, $dueDate)) {
                    die("Binding parameters failed: " . $stmt->error);
                }

                // Execute the statement
                if ($stmt->execute()) {
                    // Assignment inserted successfully
                    $message = "Assignment inserted successfully.";
                    $alertClass = "alert-success";
                } else {
                    // Error occurred while inserting the assignment
                    $message = "Error inserting the assignment.";
                    $alertClass = "alert-danger";
                }
             
            }
        }
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
<!-- <link rel="stylesheet" href="home.css"> -->
<link rel="stylesheet" href="upload_form.css">
<script src="close.js"></script>
<style>     <style>
    .centered-container {
      display: flex;
    justify-content: flex-end;
    padding-top: 10px;
    height: 100vh;
    flex-direction: column-reverse;
    }
 .centered-card {
      display: flex;
      justify-content: center;
    }

    
</style>    
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
                <span class="logo_name">Faculty</span>
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
            <i class="fas fa-chalkboard-teacher"></i>
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
<div class="upload-container">
<h1>Assignment Upload</h1>

        <?php if (!empty($uploadErrors)): ?>
            <div class="error-msg">
                <ul>
                    <?php foreach ($uploadErrors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($successMsg): ?>
            <div class="success-msg"><?php echo $successMsg; ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">

            <label for="assignment_name">Topic Name:</label>
            <input type="text" name="assignment_name" required>

            <div class="mb-3">
    <label for="course" class="form-label">Course:</label>
    <select name="course" id="course" required class="form-select">
        <option value="">Select Course</option>
        <?php foreach ($courses as $course): ?>
            <option value="<?php echo $course['Course_id']; ?>"><?php echo $course['Course_name']; ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="mb-3">
    <label for="subject" class="form-label">Subject:</label>
    <select name="subject" id="subject" required class="form-select">
        <option value="">Select Subject</option>
    </select>
</div>

<div class="mb-3">
    <label for="topic" class="form-label">Topic:</label>
    <select name="topic" id="topic" required class="form-select">
        <option value="">Select Topic</option>
    </select>
</div>
 <!-- <?php print $_POST['topic']?> -->
<label for="file">Upload Assignment (PDF only):</label>
            <input type="file" name="file" required>

            <label for="due_date">Due Date:</label>
            <input type="datetime-local" name="due_date" required>

            <button type="submit" name="submit">Upload</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
$(document).ready(function () {
    // AJAX request to fetch subjects based on the selected course
    $('#course').change(function () {
        var courseId = $(this).val();
        if (courseId !== '') {
            fetchSubjects(courseId);
        } else {
            clearSubjects();
        }
    });

    function fetchSubjects(courseId) {
        var facultyId = <?php echo $_SESSION['User_id']; ?>;
        var subjectSelect = $('select[name="subject"]');
        subjectSelect.html('<option value="">Loading...</option>');

        $.ajax({
            url: '<?php echo $_SERVER['PHP_SELF']; ?>',
            type: 'POST',
            data: {
                course: courseId,
                fetch_subjects: true
            },
            success: function (response) {
                try {
                    var subjects = JSON.parse(response);
                    updateSubjectDropdown(subjects);
                } catch (error) {
                    console.log('Error: ' + error);
                    clearSubjects();
                }
            },
            error: function (xhr, status, error) {
                console.log('Error: ' + error);
                clearSubjects();
            }
        });
    }

    function updateSubjectDropdown(subjects) {
        var subjectSelect = $('select[name="subject"]');
        subjectSelect.empty();
        subjectSelect.append('<option value="">Select Subject</option>');
        subjects.forEach(function (subject) {
            subjectSelect.append('<option value="' + subject.Subject_id + '">' + subject.Subject_name + '</option>');
        });
    }

    function clearSubjects() {
        var subjectSelect = $('select[name="subject"]');
        subjectSelect.empty();
        subjectSelect.append('<option value="">Select Subject</option>');
    }

    // AJAX request to fetch topics based on the selected subject
    $('#subject').change(function () {
        var subjectId = $(this).val();
        if (subjectId !== '') {
            fetchTopics(subjectId);
        } else {
            clearTopics();
        }
    });

    function fetchTopics(subjectId) {
        var topicSelect = $('select[name="topic"]');
        topicSelect.html('<option value="">Loading...</option>');

        $.ajax({
            url: '<?php echo $_SERVER['PHP_SELF']; ?>',
            type: 'POST',
            data: {
                subject: subjectId,
                fetch_topics: true
            },
            success: function (response) {
                try {
                    var topics = JSON.parse(response);
                    updateTopicDropdown(topics);
                } catch (error) {
                    console.log('Error: ' + error);
                    clearTopics();
                }
            },
            error: function (xhr, status, error) {
                console.log('Error: ' + error);
                clearTopics();
            }
        });
    }

    function updateTopicDropdown(topics) {
        var topicSelect = $('select[name="topic"]');
        topicSelect.empty();
        topicSelect.append('<option value="">Select Topic</option>');
        topics.forEach(function (topic) {
            topicSelect.append('<option value="' + topic.topic_id + '">' + topic.topic_name + '</option>');
        });
    }

    function clearTopics() {
        var topicSelect = $('select[name="topic"]');
        topicSelect.empty();
        topicSelect.append('<option value="">Select Topic</option>');
    }
});
</script>
</body>
</html>


