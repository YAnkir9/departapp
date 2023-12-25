<?php
// Set error reporting and display errors
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

// Initialize the answered MCQs array in the session if not already set
if (!isset($_SESSION['answered_mcqs'])) {
    $_SESSION['answered_mcqs'] = array();
}

// Fetch user data from the database for the logged-in user
$stmt = $conn->prepare("SELECT first_name, last_name, User_name, User_id, Course_id FROM users WHERE User_id = ?");
$stmt->bind_param("i", $_SESSION['User_id']);
$stmt->execute();
$result = $stmt->get_result();

// Check if user data is fetched successfully
if ($result) {
    $user = $result->fetch_assoc();

    // Course of the particular student
    $course = $user['Course_id'];

    // Fetch the course name from the courses table
    $courseStmt = $conn->prepare("SELECT * FROM cources WHERE Course_id = ?");

    // Check if the course statement is prepared successfully
    if ($courseStmt) {
        $courseStmt->bind_param("i", $course);
        $courseStmt->execute();
        $result = $courseStmt->get_result();

        // Check if course data is fetched successfully
        if ($result) {
            $userCourse = $result->fetch_assoc();
            $courseStmt->close();
        } else {
            throw new Exception("Failed to fetch course data.");
        }
    } else {
        throw new Exception("Failed to prepare course statement: " . $conn->error);
    }

    $exmId = $_GET['exam_id'];

    // Check if MCQs are not already fetched
    if (!isset($_SESSION['mcqs'])) {
        // Fetch the MCQs associated with the exam in a randomized order
        $exmIdstmt = $conn->prepare("SELECT em.exam_mcq, em.Exam_id, em.mcq_id, m.question, m.option1, m.option2, m.option3, m.option4, m.m_weightage, m.correct_answer
        FROM exam_mcq AS em
        JOIN mcq AS m ON em.mcq_id = m.mcq_id
        WHERE em.Exam_id = ?
        ORDER BY RAND()");
        $exmIdstmt->bind_param("i", $exmId);
        $exmIdstmt->execute();
        $exmResult = $exmIdstmt->get_result();

        // Fetch all MCQs and store them in an array and in session
        $mcqs = array();
        while ($row = $exmResult->fetch_assoc()) {
            $mcqs[] = $row;
        }
        $_SESSION['mcqs'] = $mcqs;

        $exmIdstmt->close();
    } else {
        // MCQs are already fetched, retrieve them from the session
        $mcqs = $_SESSION['mcqs'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Exam</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
<div class="container">
    <h1>Student Exam</h1>
    <?php print_r($_SESSION['answered_mcqs']); ?>
    <?php
    if (!empty($mcqs)) {
        $currentQuestion = isset($_GET['q']) ? intval($_GET['q']) : 0; // Start from the first question
        $answeredMCQs = isset($_SESSION['answered_mcqs']) ? $_SESSION['answered_mcqs'] : array();

        // Find the next unanswered MCQ
        while ($currentQuestion < count($mcqs)) {
            $mcq = $mcqs[$currentQuestion];
            if (!in_array($mcq['mcq_id'], $answeredMCQs)) {
                break;
            }
            $currentQuestion++;
        }

        if ($currentQuestion < count($mcqs)) {
            $mcq = $mcqs[$currentQuestion];
            ?>

            <div id="mcq-container">
                <h6 id="mcq-number">Question <?php echo $currentQuestion + 1; ?></h6>
                <div id="question">
                    <?php echo $mcq['question']; ?>
                </div>
                <input type="hidden" id="mcq_id" value="<?php echo $mcq['mcq_id']; ?>">

                <?php
                $options = array('A' => $mcq['option1'], 'B' => $mcq['option2'], 'C' => $mcq['option3'], 'D' => $mcq['option4']);
                $optionKeys = array_keys($options);
                shuffle($optionKeys);

                // Loop through the shuffled options and display them
                foreach ($optionKeys as $optionKey) {
                    echo '<label>';
                    echo '<input type="radio" name="user_answer' . $mcq['mcq_id'] . '" value="' . htmlspecialchars($optionKey) . '">';
                    echo htmlspecialchars($optionKey) . '. ' . htmlspecialchars($options[$optionKey]);
                    echo '</label><br>';
                }
                ?>

                <br><br>
                <div id="countdown">Time Left: <span id="timer">10</span> seconds</div>
               
                <?php
        // Check if this is the last MCQ, and if so, display the Submit button
        if ($currentQuestion === count($mcqs) - 1) {
            echo '<button id="submitBtn">Submit</button>';
        } else {
            echo '<button id="nextBtn">Next</button>';
        }
        ?>               
            </div>

            <?php
        } else {
            // All questions have been answered, show a message or redirect as needed.
            ?>
            <div id="mcq-container">
                <p>All questions have been answered.</p>
                <button id="submitBtn">Submit</button>
            </div>
            <?php
        }
    } else {
        echo "<p>No MCQs found.</p>";
    }
    ?>
</div>

<script>
    // Function to insert MCQ result into the mcq_results table
    function insertMCQResult(userId, examId, mcqId, userAnswer, obtainedWeightage) {
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    // Success, you can handle the response if needed
                    console.log(xhr.responseText);
                } else {
                    // Error handling, handle the error response
                    console.error(xhr.responseText);
                }
            }
        };

        // Prepare the data to send
        var data = new FormData();
        data.append('user_id', userId);
        data.append('Exam_id', examId);
        data.append('mcq_id', mcqId);
        data.append('user_answer', userAnswer);
        data.append('m_obtain', obtainedWeightage);

        // Send the request
        xhr.open('POST', 'insert_mcq_result.php', true);
        xhr.send(data);
    }

    // Function to automatically click the "Next" button
    function clickNextButton() {
        var nextBtn = document.getElementById('nextBtn');
        if (nextBtn) {
            nextBtn.click();
        }
    }

    // Function to automatically click the "Submit" button
    function clickSubmitButton() {
        var submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.click();
        }
    }

    // Add an event listener to the "Next" button
    var nextBtn = document.getElementById('nextBtn');
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            var mcqId = document.getElementById('mcq_id').value;
            var selectedAnswer = document.querySelector('input[name="user_answer' + mcqId + '"]:checked');
            var userAnswer = selectedAnswer ? selectedAnswer.value : null;


            var userId = <?php echo $user['User_id']; ?>;
            var examId = <?php echo $exmId; ?>;
            var obtainedWeightage = 0; // You need to calculate this based on the correct answer

            // Send the answer to the server using AJAX
            insertMCQResult(userId, examId, mcqId, userAnswer, obtainedWeightage);

            // Mark the current MCQ as answered in the session
            var answeredMCQs = <?php echo json_encode($_SESSION['answered_mcqs']); ?>;
            answeredMCQs.push(mcqId);
            sessionStorage.setItem('answeredMCQs', JSON.stringify(answeredMCQs));

            // Redirect to the next question or show the submit button if it's the last question
            if (<?php echo $currentQuestion + 1; ?> < <?php echo count($mcqs); ?>) {
                window.location.href = '?q=<?php echo $currentQuestion + 1; ?>&exam_id=<?php echo $exmId; ?>';
            } else {
                clickSubmitButton();
            }
        });
    }

    // Add an event listener to the "Submit" button
    var submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', function () {
            var mcqId = document.getElementById('mcq_id').value;
            var selectedAnswer = document.querySelector('input[name="user_answer' + mcqId + '"]:checked');
            var userAnswer = selectedAnswer ? selectedAnswer.value : null;


            var userId = <?php echo $user['User_id']; ?>;
            var examId = <?php echo $exmId; ?>;
            var obtainedWeightage = 0; // You need to calculate this based on the correct answer

            // Send the answer to the server using AJAX
            insertMCQResult(userId, examId, mcqId, userAnswer, obtainedWeightage);

            // Mark the current MCQ as answered in the session
            var answeredMCQs = <?php echo json_encode($_SESSION['answered_mcqs']); ?>;
            answeredMCQs.push(mcqId);
            sessionStorage.setItem('answeredMCQs', JSON.stringify(answeredMCQs));
            // Redirect to student_submit_exam.php with the exam ID
            window.location.href = 'student_submit_exam.php?exam_id=<?php echo $exmId; ?>';
        });
    }

    var timer = 10; // Set the initial time in seconds
    var countdown = document.getElementById('timer');

    // Function to update the countdown
    function updateCountdown() {
        countdown.textContent = timer + ' seconds';
        if (timer <= 0) {
            clearInterval(interval);
            if (<?php echo $currentQuestion; ?> === <?php echo count($mcqs) - 1; ?>) {
            clickSubmitButton();
        } else {
            clickNextButton(); // Automatically click the "Next" button when the timer reaches 0
        }
        }
    }

    // Initial update
    updateCountdown();

    // Start the countdown timer
    var interval = setInterval(function () {
        timer--;
        if (timer < 0) {
            timer = 0;
        }
        updateCountdown();
    }, 1000);

</script>
</body>
</html>
