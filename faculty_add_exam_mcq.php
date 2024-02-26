<?php
// Include session handling and database connection
include 'nav.php';
include 'config.php';

// Redirect to login page if user is not logged in
if (!isset($_SESSION['User_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user details
$stmt = $conn->prepare("SELECT first_name, last_name, User_name, User_id FROM users WHERE User_id = ?");
$stmt->bind_param("i", $_SESSION['User_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch exam details based on the provided exam ID
$examID = $_GET['id'] ?? '';
$subjectID = '';
$total = 0;

if (!empty($examID)) {
    $fetchSubjectStmt = $conn->prepare("SELECT subject_id, total_marks FROM exam WHERE exam_id = ?");
    $fetchSubjectStmt->bind_param("i", $examID);
    $fetchSubjectStmt->execute();
    $result = $fetchSubjectStmt->get_result();
    $row = $result->fetch_assoc();
    $subjectID = $row['subject_id'];
    $total = $row['total_marks'];
}

// Fetch topic IDs associated with the exam
$topicIDs = array();

if (!empty($examID)) {
    $fetchTopicStmt = $conn->prepare("SELECT topics.topic_id, topics.topic_name
        FROM topics
        JOIN exam_topic ON topics.topic_id = exam_topic.Topic_id
        WHERE exam_topic.Exam_id = ?");

    $fetchTopicStmt->bind_param("i", $examID);
    $fetchTopicStmt->execute();
    $topic_res = $fetchTopicStmt->get_result();

    while ($topic_row = $topic_res->fetch_assoc()) {
        $topicIDs[] = $topic_row['topic_id'];
    }
}

// Fetch MCQs based on subject and topic IDs
$mcqs = array();

if (!empty($subjectID) && !empty($topicIDs)) {
    $query = "SELECT mcq.mcq_id, mcq.question, mcq.option1, mcq.option2, mcq.option3, mcq.option4, mcq.correct_answer, mcq.topic_id, mcq.m_weightage, topics.topic_name
              FROM mcq
              JOIN topics ON mcq.topic_id = topics.topic_id
              WHERE mcq.subject_id = ? AND (";

    $topicPlaceholders = implode(" OR ", array_fill(0, count($topicIDs), "mcq.topic_id = ?"));
    $query .= $topicPlaceholders . ")";

    $paramTypes = str_repeat("i", count($topicIDs) + 1);
    $params = array_merge([$subjectID], $topicIDs);

    $fetchStmt = $conn->prepare($query);

    $fetchStmt->bind_param($paramTypes, ...$params);
    $fetchStmt->execute();
    $result = $fetchStmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $mcqs[] = $row;
    }
}

// Handle form submission
$error = '';
$selectedMCQs = [];
$selectedMCQTotalMarks = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedMCQs = isset($_POST['selected_mcqs']) ? $_POST['selected_mcqs'] : [];
    $examID = $_POST['exam_id'] ?? '';
    $totalWeightage = 0;

    

    if (!empty($selectedMCQs) && !empty($examID)) {
        $addstmt = $conn->prepare("INSERT INTO exam_mcq (mcq_id, exam_id) VALUES (?, ?)");

        foreach ($selectedMCQs as $mcqID) {
            $addstmt->bind_param("ii", $mcqID, $examID);
            $addstmt->execute();

            if ($addstmt->error) {
                $error = "Error adding MCQs to the exam.";
                break;
            }
        }

        $addstmt->close();

        if (empty($error)) {
            echo "<script>alert('Selected MCQs have been added to the exam successfully.');
            window.location.href = 'faculty_create_exam.php';</script>";
            exit();
        }
    }

    if (!empty($error)) {
        echo "<script>alert('$error');
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
    <title>Faculty Add Exam MCQ</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="indx.css">
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="selectmcq.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="close.js"></script>
    <style>
        #total-marks-container {
            position: fixed;
            top: 400px;
            right: 50px;
            background-color: #3498db;
            color: #ffffff;
            padding: 10px;
            border-radius: 50%;
            text-align: center;
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
            </span></div><br>
            <div>
                <p class="head">Student Progressive Assessment System</p>
            </div>
            <ul class="top-nav">
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

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

    <!-- Content Area on the Right Side -->
    <div id="content">
        <div class="main-top">
            <i class="fas fa-chalkboard-teacher"></i>
        </div>

        <!-- User details section -->
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

        <!-- MCQ selection form -->
        <div class="container">
            <div id="total-marks-container">
                <span id="selectedMCQTotalMarks">
                <?php echo $selectedMCQTotalMarks; ?></span>/<?php echo $total; ?>
            </div>
            <?php if (!empty($mcqs)) : ?>
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <div class="row">
                        <div class="col-md-12">
                            <h3>Fetched MCQs:</h3>
                            <?php foreach ($mcqs as $mcq) : ?>
                                <div class="mcq-item">
                                    <div class="mcq-checkbox">
                                        <input type="checkbox" name="selected_mcqs[]" value="<?php echo $mcq['mcq_id']; ?>" <?php echo (in_array($mcq['mcq_id'], $selectedMCQs) ? 'checked' : ''); ?>>
                                    </div>
                                    <div class="mcq-content">
                                        <p> <?php echo $mcq['question']; ?></p>
                                        <ol type="A">
                                            <li><?php echo $mcq['option1']; ?></li>
                                            <li><?php echo $mcq['option2']; ?></li>
                                            <li><?php echo $mcq['option3']; ?></li>
                                            <li><?php echo $mcq['option4']; ?></li>
                                        </ol>
                                        <p><strong>Correct Answer:</strong> <?php echo $mcq['correct_answer']; ?></p>
                                        <p><strong>Topic:</strong> <?php echo $mcq['topic_name']; ?></p>
                                    </div>
                                    <div class="mcq-weightage">
                                        <?php echo $mcq['m_weightage']; ?> Weightage
                                    </div>
                                </div>
                            <?php endforeach; ?>
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
                            
                            <button type="submit" class="btn btn-primary">
                                Add MCQs to Exam
                            </button>
                        </div>
                    </div>
                </form>
            <?php else : ?>
                <p>No MCQs found.</p>
            <?php endif; ?>
        </div>

        <!-- Add script to update total weightage dynamically -->
        <script>
    $(document).ready(function() {
        $('.mcq-item').click(function() {
            var checkbox = $(this).find('input[name="selected_mcqs[]"]');
            checkbox.prop('checked', !checkbox.prop('checked'));
            updateTotalWeightage();
        });

        $('input[name="selected_mcqs[]"]').change(function() {
            updateTotalWeightage();
        });

        $('form').submit(function(e) {
            var totalWeightage = parseInt($('#selectedMCQTotalMarks').text());
            var examTotal = <?php echo $total; ?>;

            if (totalWeightage !== examTotal) {
                e.preventDefault();
                alert('Please select MCQs, and ensure the total marks match the exam total weightage.');
            }
        });

        function updateTotalWeightage() {
            var totalWeightage = 0;

            $('input[name="selected_mcqs[]"]:checked').each(function() {
                var mcqId = $(this).val();
                var weightage = <?php echo json_encode(array_column($mcqs, 'm_weightage', 'mcq_id')); ?>[mcqId];
                totalWeightage += weightage;
            });

            $('#selectedMCQTotalMarks').text(totalWeightage);
        }
    });
</script>
    </div>
</body>
</html>
