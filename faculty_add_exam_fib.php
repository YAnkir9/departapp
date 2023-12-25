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

// Get the exam ID from the URL parameter
$examID = $_GET['id'] ?? '';

// Fetch subject ID from the exam table based on the exam ID
$subjectID = '';
if (!empty($examID)) {
    $fetchSubjectStmt = $conn->prepare("SELECT subject_id FROM exam WHERE exam_id = ?");
    $fetchSubjectStmt->bind_param("i", $examID);
    $fetchSubjectStmt->execute();
    $result = $fetchSubjectStmt->get_result();
    $row = $result->fetch_assoc();
    $subjectID = $row['subject_id'];

    }
    // Fetch topic IDs for the exam
$topicIDs = array(); // Initialize an array to store topic IDs

if (!empty($examID)) {
    $fetchTopicStmt = $conn->prepare("SELECT topics.topic_id
        FROM topics
        JOIN exam_topic ON topics.topic_id = exam_topic.Topic_id
        WHERE exam_topic.Exam_id = ?");

    if (!$fetchTopicStmt) {
        die("Prepare failed: " . $conn->error);
    }

    $fetchTopicStmt->bind_param("i", $examID);

    if (!$fetchTopicStmt->execute()) {
        die("Execute failed: " . $fetchTopicStmt->error);
    }

    $topic_res = $fetchTopicStmt->get_result();

    while ($topic_row = $topic_res->fetch_assoc()) {
        $topicIDs[] = $topic_row['topic_id'];
    }
}

// Fetch MCQs for the subject and specific topics
$mcqs = array();

if (!empty($subjectID) && !empty($topicIDs)) {
    // Construct the SQL query with a WHERE clause for subject ID and any of the topic IDs
    $query = "SELECT mcq_id, question, option1, option2, option3, option4, correct_answer, topic_id 
              FROM mcq 
              WHERE subject_id = ? AND (";

    // Create placeholders for each topic ID in the OR condition
    $topicPlaceholders = implode(" OR ", array_fill(0, count($topicIDs), "topic_id = ?"));

    $query .= $topicPlaceholders . ")";

    // Create a string for parameter types, "i" for integers
    $paramTypes = str_repeat("i", count($topicIDs) + 1);

    // Combine the subject ID and topic IDs into one array
    $params = array_merge([$subjectID], $topicIDs);

    // Prepare and execute the query
    $fetchStmt = $conn->prepare($query);

    if (!$fetchStmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind parameters
    $fetchStmt->bind_param($paramTypes, ...$params);

    if (!$fetchStmt->execute()) {
        die("Execute failed: " . $fetchStmt->error);
    }

    $result = $fetchStmt->get_result();

    // Fetch and store the selected MCQs
    while ($row = $result->fetch_assoc()) {
        $mcqs[] = $row;
    }
// Check the count of fetched MCQs
if (count($mcqs) < 5) {
    // Begin a transaction to ensure data consistency
    $conn->begin_transaction();

    try {
        // Delete the exam details from the exam table
        $deleteExamStmt = $conn->prepare("DELETE FROM exam WHERE exam_id = ?");
        $deleteExamStmt->bind_param("i", $examID);
        $deleteExamStmt->execute();
        
        // Delete the associated records from the exam_topic table
        $deleteExamTopicStmt = $conn->prepare("DELETE FROM exam_topic WHERE Exam_id = ?");
        $deleteExamTopicStmt->bind_param("i", $examID);
        $deleteExamTopicStmt->execute();

        // Commit the transaction
        $conn->commit();

        // Display an alert to the user
        echo '<script>';
        echo 'alert("There are not enough questions available to create a new exam. Please add more questions to the database.");';
        echo 'window.location.href = "create_exam.php";'; // Redirect to create_exam.php
        echo '</script>';
        exit();
    } catch (Exception $e) {
        // Handle the error and roll back the transaction if an error occurs
        $conn->rollback();

        // Display an error message
        echo '<script>';
        echo 'alert("An error occurred while deleting the exam details. Please try again later.");';
        echo 'window.location.href = "faculty_create_exam.php";'; // Redirect to create_exam.php
        echo '</script>';
        exit();
    }
}
}

$error = ''; // Variable to hold the error message

$selectedMCQs=[];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the selected MCQs and the exam ID from the form
    $selectedMCQs = isset($_POST['selected_mcqs']) ? $_POST['selected_mcqs'] : [];
    $examID = $_POST['exam_id'] ?? '';

    // Validate the number of selected MCQs
    if (count($selectedMCQs) !== 5) {
        $error = "Please select exactly 5 MCQs.";
    } elseif (!empty($selectedMCQs) && !empty($examID)) {
        // Insert the selected MCQs with the exam ID into the database
        $addstmt = $conn->prepare("INSERT INTO exam_mcq (mcq_id, exam_id) VALUES (?, ?)");

        if (!$addstmt) {
            // Handle the error if prepare() fails
            die("Prepare failed: " . $conn->error);
        }

        foreach ($selectedMCQs as $mcqID) {
            $addstmt->bind_param("ii", $mcqID, $examID);
            $addstmt->execute();

            if ($addstmt->error) {
                // Handle the error if execute() fails
                die("Execute failed: " . $addstmt->error);
            }
        }

        $addstmt->close();

        echo "<script>alert('Selected MCQs have been added to the exam successfully.');
        window.location.href = 'faculty_create_exam.php';</script>";
        exit();
    } else {
        $error = "No MCQs selected or Exam ID not provided.";
    }

    // Display the error message if any
    if (!empty($error)) {
        echo "<script>alert('Selected atleast 5 MCQs.');
        window.location.href = 'faculty_add_exam_mcq.php?id=' + encodeURIComponent('$examID');</script>";
                exit();
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
<!-- <?php     print_r($topicIDs);?>
<?php     print_r($mcqs);?>
<?php     print_r($subjectID);?> -->
<?php if (!empty($mcqs)) : ?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <div class="row">
            <div class="col-md-12">
                <h3>Fetched MCQs:</h3>
                <table class="table">
                <thead>
                    <tr>
                        <th>Select</th>
                            <th>Question</th>
                            <th>Correct Answer</th>
                            <th>Topic</th>
    
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mcqs as $mcq) : ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_mcqs[]" value="<?php echo $mcq['mcq_id']; ?>" <?php echo (in_array($mcq['mcq_id'], $selectedMCQs) ? 'checked' : ''); ?>>
                                </td>
                                <td><?php echo $mcq['question']; ?></td>
                                <td><?php echo $mcq['option1']; ?></td>
                                <td><?php echo $mcq['option2']; ?></td>
                                <td><?php echo $mcq['option3']; ?></td>
                                <td><?php echo $mcq['option4']; ?></td>
                                <td><?php echo $mcq['correct_answer']; ?></td>
                                <td><?php echo $mcq['topic_id']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="exam_id">Exam ID:</label>
                                <input type="text" value="<?php echo $_GET['id']; ?>" id="exam_id" name="exam_id" class="form-control" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary" <?php echo (count($selectedMCQs) >= 5 ? 'disabled' : ''); ?>>
                                Add MCQs to Exam
                            </button>
                        </div>
                    </div>
                </form>
            <?php else : ?>
                <p>No MCQs found.</p>
            <?php endif; ?>
       </div>
</body>
</html>

