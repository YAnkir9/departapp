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
    // header("Location: login.php");
    exit();
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="form_cont.css">
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
        <!-- <button class="tablink" onclick="openPage('MCQs', this, 'blue')" id="defaultOpen">MCQs</button> -->
        <!-- <button class="tablink" onclick="openPage('T/F', this, 'blue')">TRUE/FALSE</button>
        <button class="tablink" onclick="openPage('FIB', this, 'blue')">Blanks</button> -->
        <div id="MCQs" class="tabcontent">
        <h2 class="mb-4">Add MCQs</h2>
        <form id="MCQ_Questions" action="import_mcq.php" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="course" class="form-label">Course:</label>
            <select name="course" id="course" required class="form-select">
                <option value="">Select Course</option>
                <?php foreach ($courses as $course): ?>
                <?php
                // Check if the session variable for selected course is set
                $selectedCourseId = isset($_SESSION['selected_course']) ? $_SESSION['selected_course'] : "";
                ?>
                <option value="<?php echo $course['Course_id']; ?>" <?php echo ($selectedCourseId == $course['Course_id']) ? 'selected' : ''; ?>><?php echo $course['Course_name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="subject" class="form-label">Subject:</label>
            <select name="subject" id="subject" required class="form-select">
                <option value="">Select Subject</option>
                <?php
                // Check if the session variable for selected subject is set
                $selectedSubjectId = isset($_SESSION['selected_subject']) ? $_SESSION['selected_subject'] : "";
                $selectedSubjectName = isset($_SESSION['selected_subject_name']) ? $_SESSION['selected_subject_name'] : "";
                ?>
                <option value="<?php echo $selectedSubjectId; ?>" selected><?php echo $selectedSubjectName; ?></option>
            </select>
        </div>

        <div class="mb-3">
            <label for="topic" class="form-label">Topic:</label>
            <select name="topic" id="topic" required class="form-select">
                <option value="">Select Topic</option>
                <?php
                // Check if the session variable for selected topic is set
                $selectedTopicId = isset($_SESSION['selected_topic']) ? $_SESSION['selected_topic'] : "";
                $selectedTopicName = isset($_SESSION['selected_topic_name']) ? $_SESSION['selected_topic_name'] : "";
                ?>
                <!-- Populate the topics dynamically based on your data -->
                <?php foreach ($topics as $topic): ?>
                <option value="<?php echo $topic['topic_id']; ?>" <?php echo ($selectedTopicId == $topic['topic_id']) ? 'selected' : ''; ?>><?php echo $topic['topic_name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <style>
#upload-mcq-form-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    border: 2px solid #ccc;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.image-container {
    text-align: center;
    margin-bottom: 20px;
}

.image-container img {
    max-width: 100%;
    height: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px;
}

input[type="file"] {
    margin-bottom: 10px;
}

.btn-success {
    background-color: #28a745;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
}

.btn-success:hover {
    background-color: #218838;
}

h5 {
    margin-bottom: 10px;
}

        </style>


        <div id="upload-mcq-form-container">

            <div class="image-container">
                <h5>File Must be in this formate</h5><br>
                <img src="image/formate/mcqExcel.webp" alt="Your Image">
            </div>
            <input type="file" name="mcqFile" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
        
            <button type="submit" name="submit_mcq_excel" class="btn btn-success">Submit Excel MCQs</button>   
        </div>
        <script>
            var myElement = document.getElementById('myElementId');
if (myElement !== null) {
    myElement.value = 'New Value';
}

        </script><hr>
        <button type="button" id="show-mcq-form" class="btn btn-primary">Add MCQs</button>
        <div class="mcq-form-container">

            <h3>Add MCQs</h3>

            <div id="mcq-container">
                <div class="mcq-item">
                    <div class="form-group">
                        <label for="question">Question:</label>
                        <input type="text" id="question" name="question[]" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="option1">Option A:</label>
                        <input type="text" id="option1" name="option1[]" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="option2">Option B:</label>
                        <input type="text" id="option2" name="option2[]" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="option3">Option C:</label>
                        <input type="text" id="option3" name="option3[]" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="option4">Option D:</label>
                        <input type="text" id="option4" name="option4[]" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="m_weightage">Weightage:</label>
                        <input type="number" id="m_weightage" name="m_weightage[]" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="correct_answer" class="form-label">Correct Answer:</label>
                        <select name="correct_answer[]" id="correct_answer" class="form-select">
                            <option value="">Select Correct Answer</option>
                            <option value="A">Option A</option>
                            <option value="B">Option B</option>
                            <option value="C">Option C</option>
                            <option value="D">Option D</option>
                        </select>
                    </div>
                </div>
            </div>
            <button type="button" id="add-more-mcq" class="btn btn-primary">Add More Question</button>

            <!-- This is where dynamically added MCQ questions will be appended -->
            <div id="additional-mcq-questions"></div>
            <button type="submit" name="submit_mcq_manual" class="btn btn-success">Submit Manual MCQs</button>

        </div>

        
    </form>
    <script>
        $(document).ready(function () {
    // Hide the MCQ form initially
    $('.mcq-form-container').hide();

    // Toggle the visibility of "show-mcq-form" button
    $('#show-mcq-form').click(function () {
        $('.mcq-form-container').toggle();
    });
});
        // JavaScript code to handle adding more MCQ questions
        document.addEventListener('DOMContentLoaded', function () {
            const mcqContainer = document.getElementById('mcq-container');
            const addMoreMcqButton = document.getElementById('add-more-mcq');
            const formSubmissionTypeInput = document.getElementById('formSubmissionType');

            let questionIndex = 1; // To track the number of added questions

            addMoreMcqButton.addEventListener('click', function () {
                questionIndex++;

                // Create a new MCQ question item
                const newMcqItem = document.createElement('div');
                newMcqItem.className = 'mcq-item';

                // Clone the existing question fields
                const existingMcqItem = mcqContainer.querySelector('.mcq-item');
                newMcqItem.innerHTML = existingMcqItem.innerHTML;

                // Update input field IDs to ensure uniqueness
                newMcqItem.querySelectorAll('input, select').forEach(function (element) {
                    element.id = element.id + questionIndex;
                    element.name = element.name + questionIndex;
                    element.value = ''; // Clear the values for new questions

                    // Add or remove the 'required' attribute based on the form submission type
                    if (formSubmissionTypeInput.value === 'submit_mcq_manual') {
                        element.required = true;
                    } else {
                        element.required = false;
                    }
                });

                // Append the new MCQ question item
                mcqContainer.appendChild(newMcqItem);
            });

            // Set the form submission type when the manual MCQ form is submitted
            document.getElementById('MCQ_Questions').addEventListener('submit', function () {
                formSubmissionTypeInput.value = 'submit_mcq_manual';
            });
        });
    </script>
        </div>
      
    <script>
//ajax code for dropdown
    function setupTopicDropdown(formId) {
        $('#' + formId + ' #course').change(function () {
            var courseId = $(this).val();
            if (courseId !== '') {
                fetchSubjects(formId, courseId);
            } else {
                clearSubjects(formId);
            }
        });

        function fetchSubjects(formId, courseId) {
            var facultyId = <?php echo $_SESSION['User_id']; ?>;
            var subjectSelect = $('select[name="subject"]', '#' + formId);
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
                        updateSubjectDropdown(subjects, formId);
                    } catch (error) {
                        console.log('Error: ' + error);
                        clearSubjects(formId);
                    }
                },
                error: function (xhr, status, error) {
                    console.log('Error: ' + error);
                    clearSubjects(formId);
                }
            });
        }

        function updateSubjectDropdown(subjects, formId) {
            var subjectSelect = $('select[name="subject"]', '#' + formId);
            subjectSelect.empty();
            subjectSelect.append('<option value="">Select Subject</option>');
            subjects.forEach(function (subject) {
                subjectSelect.append('<option value="' + subject.Subject_id + '">' + subject.Subject_name + '</option>');
            });
        }

        function clearSubjects(formId) {
            var subjectSelect = $('select[name="subject"]', '#' + formId);
            subjectSelect.empty();
            subjectSelect.append('<option value="">Select Subject</option>');
        }
        
        // AJAX request to fetch topics based on the selected subject
        $('#' + formId + ' select[name="subject"]').change(function () {
            var subjectId = $(this).val();
            if (subjectId !== '') {
                fetchTopics(formId, subjectId);
            } else {
                clearTopics(formId);
            }
        });

        function fetchTopics(formId, subjectId) {
            var topicSelect = $('select[name="topic"]', '#' + formId);
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
                        updateTopicDropdown(topics, formId);
                    } catch (error) {
                        console.log('Error: ' + error);
                        clearTopics(formId);
                    }
                },
                error: function (xhr, status, error) {
                    console.log('Error: ' + error);
                    clearTopics(formId);
                }
            });
        }

        function updateTopicDropdown(topics, formId) {
            var topicSelect = $('select[name="topic"]', '#' + formId);
            topicSelect.empty();
            topicSelect.append('<option value="">Select Topic</option>');
            topics.forEach(function (topic) {
                topicSelect.append('<option value="' + topic.topic_id + '">' + topic.topic_name + '</option>');
            });
        }

        function clearTopics(formId) {
            var topicSelect = $('select[name="topic"]', '#' + formId);
            topicSelect.empty();
            topicSelect.append('<option value="">Select Topic</option>');
        }
    }

    $(document).ready(function () {
        setupTopicDropdown('MCQ_Questions');
        // setupTopicDropdown('T_F_Question');
        // setupTopicDropdown('FIB_Question');
    });
    </script>
</body>
</html>

