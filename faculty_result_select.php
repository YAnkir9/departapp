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

// Check if the AJAX request is made to fetch exams
if (isset($_POST['fetch_exams'])) {
    $selectedSubjectId = $_POST['subject'];

    $stmt = $conn->prepare("SELECT Exam_id, Exam_name FROM exam WHERE Subject_id = ?");
    $stmt->bind_param("i", $selectedSubjectId);
    $stmt->execute();
    $result = $stmt->get_result();
    $exams = $result->fetch_all(MYSQLI_ASSOC);

    // Send the exams as a JSON response
    echo json_encode($exams);
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<link rel="stylesheet" href="indx.css">
<!-- <link rel="stylesheet" href="home.css"> -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
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
<div class="form-container">

<form id="show_the_result" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
    <h2 class="mb-4">View Result</h2>
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
        <label for="exam" class="form-label">Exam:</label>
        <select name="exam" id="exam" required class="form-select">
            <option value="">Select Exam</option>
        </select>
    </div>
    <input type="submit" name="submit" value="Submit">
</form>
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
// Retrieve the selected course, subject, and exam IDs from the form data
$selectedCourseId = $_POST['course'];
$selectedSubjectId = $_POST['subject'];
$selectedExamId = $_POST['exam'];

// Fetch the course, subject, and exam names from the database using the IDs
$stmt = $conn->prepare("SELECT Course_name FROM cources WHERE Course_id = ?");
$stmt->bind_param("i", $selectedCourseId);
$stmt->execute();
$result = $stmt->get_result();
$courseName = $result->fetch_assoc()['Course_name'];

$stmt = $conn->prepare("SELECT Subject_name FROM subjects WHERE Subject_id = ?");
$stmt->bind_param("i", $selectedSubjectId);
$stmt->execute();
$result = $stmt->get_result();
$subjectName = $result->fetch_assoc()['Subject_name'];

$stmt = $conn->prepare("SELECT Exam_name FROM exam WHERE Exam_id = ?");
$stmt->bind_param("i", $selectedExamId);
$stmt->execute();
$result = $stmt->get_result();
$examName = $result->fetch_assoc()['Exam_name'];

// Print the selected subject, course, and exam names
echo '<div class="details">';
echo "<div><h5>Course: $courseName</h5></div>";
echo "<div><h5>Subject: $subjectName</h5></div>";
echo "<div><h5>Exam: $examName</h5></div>";
echo '</div>'.'<hr>';
// Fetch users associated with the selected course
$stmt = $conn->prepare("SELECT User_id, User_name FROM users WHERE Course_id = ? ORDER BY User_name");
$stmt->bind_param("i", $selectedCourseId);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

// Display the list of users
echo '<div>';
echo '<h3>Users list :</h3>';
echo '<ul class="user-list">';
foreach ($users as $user) {
$userId = $user['User_id'];
$examId = $_POST['exam'];
$redirectLink = "student_result.php?user_id=$userId&exam_id=$examId";
$redirecttoscvLink = "generate_report.php?exam_id=$examId"; // Report generation script

// Check if the result is stored for the particular student on the selected exam
$stmt = $conn->prepare("SELECT * FROM mcq_results WHERE user_id = ? AND Exam_id = ?");
$stmt->bind_param("ii", $userId, $examId);
$stmt->execute();
$result = $stmt->get_result();
$hasResult = $result->num_rows > 0;

// Display check circle icon if the result is stored, otherwise display pending icon
$iconClass = $hasResult ? 'fas fa-check-circle' : 'fas fa-circle-notch fa-spin';
if ($hasResult) {
echo '<li><a href="' . $redirectLink . '"><i class="' . $iconClass . '"></i>' . $user['User_name'] . '</a></li>';
} else {
echo '<li><i class="' . $iconClass . '"></i>' . $user['User_name'] . '</li>';
}

}
echo '<div><a href="'.$redirecttoscvLink.' ">GENERATE REORT'.'</div>';

echo '</ul>';
echo '</div>';

// Hide the form using JavaScript after submission
echo '<script>document.getElementById("show_the_result").style.display = "none";</script>';
}
?>
</div>
<script>
$(document).ready(function() {
                $('#course').change(function() {
                    var courseId = $(this).val();
                    if (courseId !== '') {
                        fetchSubjects(courseId);
                    } else {
                        clearSubjects();
                    }
                });

                $('#subject').change(function() {
                    var subjectId = $(this).val();
                    if (subjectId !== '') {
                        fetchExams(subjectId);
                    } else {
                        clearExams();
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
                        success: function(response) {
                            try {
                                var subjects = JSON.parse(response);
                                updateSubjectDropdown(subjects);
                            } catch (error) {
                                console.log('Error: ' + error);
                                clearSubjects();
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('Error: ' + error);
                            clearSubjects();
                        }
                    });
                }

                function updateSubjectDropdown(subjects) {
                    var subjectSelect = $('select[name="subject"]');
                    subjectSelect.empty();
                    subjectSelect.append('<option value="">Select Subject</option>');
                    subjects.forEach(function(subject) {
                        subjectSelect.append('<option value="' + subject.Subject_id + '">' + subject.Subject_name + '</option>');
                    });
                }

                function clearSubjects() {
                    var subjectSelect = $('select[name="subject"]');
                    subjectSelect.empty();
                    subjectSelect.append('<option value="">Select Subject</option>');
                }

                function fetchExams(subjectId) {
                    var examSelect = $('select[name="exam"]');
                    examSelect.html('<option value="">Loading...</option>');

                    $.ajax({
                        url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                        type: 'POST',
                        data: {
                            subject: subjectId,
                            fetch_exams: true
                        },
                        success: function(response) {
                            try {
                                var exams = JSON.parse(response);
                                updateExamDropdown(exams);
                            } catch (error) {
                                console.log('Error: ' + error);
                                clearExams();
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('Error: ' + error);
                            clearExams();
                        }
                    });
                }

                function updateExamDropdown(exams) {
                    var examSelect = $('select[name="exam"]');
                    examSelect.empty();

                    if (exams.length === 0) {
                        examSelect.append('<option value="">Exam is not created</option>');
                    } else {
                        examSelect.append('<option value="">Select Exam</option>');
                        exams.forEach(function(exam) {
                            examSelect.append('<option value="' + exam.Exam_id + '">' + exam.Exam_name + '</option>');
                        });
                    }
                }

                function clearExams() {
                    var examSelect = $('select[name="exam"]');
                    examSelect.empty();
                    examSelect.append('<option value="">Select Exam</option>');
                }
            });
</script>
</div>
</body>
</html>
