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

    // Debugging: Output the fetched MCQs
    echo '<pre>';
    print_r($mcqs);
    echo '</pre>';

    // Debugging: Output the SQL query
    // echo 'SQL Query: ' . $exmIdstmt->sqlstate . '<br>';

    // Debugging: Output the number of fetched MCQs
    echo 'Number of Fetched MCQs: ' . count($mcqs) . '<br>';
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
    <title>Index Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<link rel="stylesheet" href="indx.css">
<!-- <link rel="stylesheet" href="home.css"> -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.16/dist/tailwind.min.css" rel="stylesheet">
</head>
<script src="close.js"></script>
<style>
    /* Style for the MCQ form container */
.mcq-container {
    margin-bottom: 20px;
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 5px;
    background-color: #f9f9f9;
}

/* Style for MCQ question */
.mcq-container h6 {
    margin-top: 0;
    font-weight: bold;
}

/* Style for answer options */
.mcq-container label {
    display: block;
    margin-bottom: 5px;
    font-weight: normal;
}

/* Style for submit button */
button[type="submit"] {
    background-color: #007bff; /* Blue color */
    color: #fff; /* White text */
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
}

/* Hover effect for the submit button */
button[type="submit"]:hover {
    background-color: #0056b3; /* Darker blue on hover */
}

/* Style for 'No MCQs found' message */
p {
    font-weight: bold;
    color: #ff0000; /* Red color */
}

</style>

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
        <h1 class="text-2xl font-bold mb-4">Student Exam</h1>
        <?php
            if (!empty($mcqs)) {
                $currentQuestion = isset($_GET['q']) ? intval($_GET['q']) : 0; // Start from the first question
                $answeredMCQs = isset($_SESSION['answered_mcqs']) ? $_SESSION['answered_mcqs'] : array();
                // print_r($currentQuestion);
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

            <div id="mcq-container" class="border border-gray-300 rounded p-4 bg-gray-100 mb-4">
                <h6 id="mcq-number" class="text-lg font-semibold mb-2">Question <?php echo $currentQuestion + 1; ?></h6>
                <div id="question" class="text-base font-normal mb-4"><?php echo $mcq['question']; ?></div>
                <input type="hidden" id="mcq_id" value="<?php echo $mcq['mcq_id']; ?>">

                <?php
                $options = array('A' => $mcq['option1'], 'B' => $mcq['option2'], 'C' => $mcq['option3'], 'D' => $mcq['option4']);
                $optionKeys = array_keys($options);
                shuffle($optionKeys);

                foreach ($optionKeys as $optionKey) {
                    echo '<label class="block mb-2">';
                    echo '<input type="radio" name="user_answer' . $mcq['mcq_id'] . '" value="' . htmlspecialchars($optionKey) . '" class="mr-2">';
                    echo '<span class="font-normal">' . htmlspecialchars($optionKey) . '. ' . htmlspecialchars($options[$optionKey]) . '</span>';
                    echo '</label>';
                }
                ?>

                <div id="countdown" class="text-sm mt-4">Time Left: <span id="timer" class="font-semibold">10</span> seconds</div>
               
                <?php
        // Check if this is the last MCQ, and if so, display the Submit button
        if ($currentQuestion === count($mcqs) - 1) {
            echo '<button id="submitBtn" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Submit</button>';
        } else {
            echo '<button id="nextBtn" class="mt-4 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Next</button>';
        }
        ?>               
            </div>

            <?php
        } else {
            // All questions have been answered, show a message or redirect as needed.
            ?>
            <div id="mcq-container" class="border border-gray-300 rounded p-4 bg-gray-100 mb-4">
                <p class="text-red-500 font-semibold">All questions have been answered.</p>
                <button id="submitBtn" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Submit</button>
            </div>
            <?php
        }
    } else {
        echo "<p class='text-red-500 font-semibold'>No MCQs found.</p>";
    }
    ?>
</div>

<script>
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };
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
            var userAnswer = selectedAnswer ? selectedAnswer.value : 'N'; // Set default value to 'N' if not selected


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
            var userAnswer = selectedAnswer ? selectedAnswer.value : 'N'; // Set default value to 'N' if not selected


            var userId = <?php echo json_encode($user['User_id']); ?>;
var examId = <?php echo json_encode($exmId); ?>;
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