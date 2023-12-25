<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the file that defines the navigation links
include 'nav.php';

// Include the database connection file
include 'config.php';

// Check if the user is logged in as a faculty member
if ($_SESSION['credential'] == 'faculty' && $_SESSION['is_approved'] == 1) {
    // Fetch courses associated with the faculty member
    $facultyId = $_SESSION['User_id'];
    $stmt = $conn->prepare("SELECT Course_id, Course_name FROM cources WHERE Course_id IN (SELECT Course_id FROM subjects WHERE User_id = ?) ORDER BY Course_name");
    $stmt->bind_param("i", $facultyId);
    $stmt->execute();
    $result = $stmt->get_result();
    $courses = $result->fetch_all(MYSQLI_ASSOC);
} else {
    // Redirect to login page or display an error message
    header("Location: login.php");
    exit();
}

// Fetch user data from the database for the logged-in user
$stmt = $conn->prepare("SELECT first_name, last_name, User_name, User_id FROM users WHERE User_id = ?");
$stmt->bind_param("i", $_SESSION['User_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if the AJAX request is made to fetch subjects
if (isset($_POST['fetch_subjects'])) {
    $selectedCourseId = $_POST['course'];
    $facultyId = $_SESSION['User_id'];

    $stmt = $conn->prepare("SELECT Subject_id, Subject_name FROM subjects WHERE Course_id = ? AND User_id = ? ORDER BY Subject_name");
    $stmt->bind_param("ii", $selectedCourseId, $facultyId);
    $stmt->execute();
    $result = $stmt->get_result();
    $subjects = $result->fetch_all(MYSQLI_ASSOC);

    // Send the subjects as a JSON response
    echo json_encode($subjects);
    exit();
}

if (isset($_POST['fetch_topics'])) {
    $selectedSubjectId = $_POST['subject'];

    $stmt = $conn->prepare("SELECT topic_id, topic_name FROM topics WHERE subject_id = ? ORDER BY topic_name");
    $stmt->bind_param("i", $selectedSubjectId);
    $stmt->execute();
    $result = $stmt->get_result();
    $topics = $result->fetch_all(MYSQLI_ASSOC);

    // Send the subjects as a JSON response
    echo json_encode($topics);
    exit();
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the form data
    $courseId = $_POST['course'];
    $subjectId = $_POST['subject'];
    $examName = $_POST['exam_name'];
    $examType = $_POST['exam_type'];
    $currentDateTime = date('Y-m-d H:i:s');
    // Generate a unique Exam_id
    $examId = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

    // Insert the values into the exam table
    $stmt = $conn->prepare("INSERT INTO exam (Exam_id, Exam_name, Exam_type, User_id, Course_id, Subject_id, Create_time) 
    VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiiiis", $examId, $examName, $examType, $_SESSION['User_id'], $courseId, $subjectId, $currentDateTime);

    if ($stmt->execute()) {
        // Exam inserted successfully

        // Insert selected topics into exam_topics table
        if (isset($_POST['topics'])) {
            $selectedTopics = $_POST['topics']; // Array of selected topic IDs
            foreach ($selectedTopics as $topicId) {
                // Prepare the INSERT statement
                $tstmt = $conn->prepare("INSERT INTO exam_topic (Exam_id, Topic_id) VALUES (?, ?)");
            
                if (!$tstmt) {
                    // Check if the prepare statement failed
                    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
                }
            
                // Bind parameters
                $bindResult = $tstmt->bind_param("ii", $examId, $topicId);
            
                if (!$bindResult) {
                    // Check if the bind_param failed
                    die("Binding parameters failed: (" . $tstmt->errno . ") " . $tstmt->error);
                }
            
                // Execute the statement
                $executeResult = $tstmt->execute();
            
                if (!$executeResult) {
                    // Check if the execute failed
                    die("Execute failed: (" . $tstmt->errno . ") " . $tstmt->error);
                }
                if($examType=2){
                    $redirectUrl = 'faculty_add_exam_mcq.php?id=' . $examId;
                    echo "<script>window.location.href = '$redirectUrl';</script>";    
                }
                if($examType=0){
                    $redirectUrl = 'faculty_add_exam_tf.php?id=' . $examId;
                    echo "<script>window.location.href = '$redirectUrl';</script>";    
                }
                if($examType=1){
                    $redirectUrl = 'faculty_add_exam_fib.php?id=' . $examId;
                    echo "<script>window.location.href = '$redirectUrl';</script>";    
                }
            
            
                // Close the statement
                $tstmt->close();
            }
                        
        }
        // Exam inserted successfully
        $message = "Exam inserted successfully.";
        $alertClass = "alert-success";
        // $redirectUrl = 'faculty_add_exam_mcq.php?id=' . $examId;
        // echo "<script>window.location.href = '$redirectUrl';</script>";
        exit();
    } else {
        // Error occurred while inserting the exam
        $message = "Error inserting the exam.";
        $alertClass = "alert-danger";
    }


}

// Check if a success message is set
if (isset($_GET['success'])) {
    // Retrieve the success message and sanitize it
    $successMessage = htmlspecialchars($_GET['success']);

    // Display the success message in an alert box
    echo '<script>alert("' . $successMessage . '");</script>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <link rel="stylesheet" href="indx.css">
    <link rel="stylesheet" href="exam.css">

    <script src="close.js"></script>
</head>
<body>
    <!-- Top navigation bar with toggle button (for mobile) -->
    <header>
        <nav>
            <button class="toggle-button" id="toggleSidebar">☰</button>
            <div class="logo">
                <span class="nav_image">
                    <img src="image/Logo.png" alt="logo_img" />
                </span>
            </div>
            <br>
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
    </aside>

    <!-- Content Area on the Right Side -->
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
                        <i class="fas fa-info-circle"> Profile</i>
                    </a>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="form-container">
                <h2 class="mb-4">Create Exam</h2>
                <form id="create_exam_form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
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
                        <label for="selected-topics" class="form-label">Topics:</label>
                        <div class="custom-dropdown">
                            <input type="text" id="selected-topics" class="form-control"
                                placeholder="Select topics" readonly>
                            <div class="dropdown-content">
                                <!-- Checkboxes will be dynamically added using JavaScript -->
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="exam_name" class="form-label">Exam Name:</label>
                        <input type="text" name="exam_name" id="exam_name" required class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="exam_type" class="form-label">Exam Type:</label>
                        <select name="exam_type" id="exam_type" required class="form-select">
                            <option value="0">MCQ</option>
                            <option value="1">Fill Ups</option>
                            <option value="2">True/False</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Topics:</label>
                        <div class="topic-checkboxes">
                            <!-- Checkboxes will be dynamically added using JavaScript -->
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Exam</button>
                </form>
            </div>
        </div>

        <script>
            $(document).ready(function () {
                // Set the default exam type as "MCQ"

                // AJAX request to fetch subjects
                $('#course').change(function () {
                    var courseId = $(this).val();
                    if (courseId !== '') {
                        fetchSubjects(courseId);
                    } else {
                        clearSubjects();
                    }
                });

                function fetchSubjects(courseId) {
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

                // AJAX request to fetch topics
                $('#subject').change(function () {
                    var subjectId = $(this).val();
                    if (subjectId !== '') {
                        fetchTopics(subjectId);
                    } else {
                        clearTopics();
                    }
                });

                function fetchTopics(subjectId) {
                    var topicSelect = $('select[name="topics"]');
                    topicSelect.html('<option value="">Loading...</option>');

                    $.ajax({
                        url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                        type: 'POST',
                        data: {
                            subject: subjectId,
                            fetch_topics: true // Indicate that you're fetching topics
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
                    var dropdownContent = $('.dropdown-content');
                    dropdownContent.empty(); // Clear any existing checkboxes

                    topics.forEach(function (topic) {
                        dropdownContent.append(
                            `<label class="checkbox-label">
                                <input class="form-check-input" type="checkbox" name="topics[]" value="${topic.topic_id}">
                                ${topic.topic_name}
                            </label>`
                        );
                    });

                    $('.form-check-input').change(function () {
                        updateSelectedTopics();
                    });
                }

                function updateSelectedTopics() {
                    var selectedTopicIds = $('.form-check-input:checked')
                        .map(function () {
                            return $(this).val();
                        })
                        .get();

                    $('#selected-topics').val(selectedTopicIds.join(', ')); // Update the text area
                }

                function clearTopics() {
                    var topicSelect = $('select[name="topics"]');
                    topicSelect.empty();
                    topicSelect.append('<option value="">Select Topic</option>');
                }

                // Client-side validation to ensure at least one topic is selected
                $('#create_exam_form').submit(function () {
                    var selectedTopics = $('.form-check-input:checked');
                    if (selectedTopics.length === 0) {
                        alert('Please select at least one topic for the exam.');
                        return false; // Prevent form submission
                    }
                });
            });
        </script>
    </div>
</body>
</html>
