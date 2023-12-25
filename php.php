<!-- <?php
// Start the session
session_start();

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

// Initialize variables
$currentStep = isset($_SESSION['current_step']) ? $_SESSION['current_step'] : 0;
$answeredMCQs = isset($_SESSION['answered_mcqs']) ? $_SESSION['answered_mcqs'] : array();
$userCourse = array();

try {
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
        }

        // Fetch the MCQs associated with the exam in a randomized order
        $exmId = $_GET['exam_id'];

        $exmIdstmt = $conn->prepare("SELECT em.exam_mcq, em.Exam_id, em.mcq_id, m.question, m.option1, m.option2, m.option3, m.option4, m.m_weightage, m.correct_answer
        FROM exam_mcq AS em
        JOIN mcq AS m ON em.mcq_id = m.mcq_id
        WHERE em.Exam_id = ?");
        $exmIdstmt->bind_param("i", $exmId);
        $exmIdstmt->execute();
        $exmResult = $exmIdstmt->get_result();

        // Fetch all MCQs and store them in an array
        $mcqs = array();
        while ($row = $exmResult->fetch_assoc()) {
            $mcqs[] = $row;
        }

        $exmIdstmt->close();

        // Initialize an array to store questions and options
        $questions = array();

        // Convert MCQs into questions and options
        foreach ($mcqs as $mcq) {
            $question = $mcq['question'];
            $options = array(
                $mcq['option1'],
                $mcq['option2'],
                $mcq['option3'],
                $mcq['option4']
            );

            $questions[] = array(
                'question' => $question,
                'options' => $options
            );
        }
    } else {
        throw new Exception("Failed to fetch user data.");
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize an array to store user answers and obtained weightage
    $userAnswers = array();
    
    foreach ($mcqs as $mcq) {
        $mcqId = $mcq['mcq_id'];
        $userAnswer = isset($_POST['answer_' . $mcqId]) ? $_POST['answer_' . $mcqId] : null;
        $correctAnswer = $mcq['correct_answer'];
        $weightage = $mcq['m_weightage'];

        // Check if the user's answer is correct
        if ($userAnswer === $correctAnswer) {
            $obtainedWeightage = $weightage;
        } else {
            $obtainedWeightage = 0; // If the answer is incorrect, obtained weightage is 0
        }

        // Store user answer and obtained weightage in the array
        $userAnswers[] = array(
            'user_id' => $_SESSION['User_id'],
            'Exam_id' => $exmId, // You may need to retrieve this value appropriately
            'mcq_id' => $mcqId,
            'question' => $mcq['question'],
            'selected_answer' => $userAnswer,
            'm_obtain' => $obtainedWeightage
        );

        // Add the answered MCQ ID to the answeredMCQs array
        $answeredMCQs[] = $mcqId;
    }

    // Assuming your database table is named "mcq_results"
    $stmt = $conn->prepare("INSERT INTO mcq_results (user_id, Exam_id, mcq_id, question, selected_answer, m_obtain) 
            VALUES (?, ?, ?, ?, ?, ?)");
    
    // Check if the statement was prepared successfully
    if ($stmt) {
        foreach ($userAnswers as $userAnswer) {
            // Bind parameters
            $stmt->bind_param("iiissi", $userAnswer['user_id'], $userAnswer['Exam_id'], $userAnswer['mcq_id'], 
            $userAnswer['question'], $userAnswer['selected_answer'], $userAnswer['m_obtain']);

            // Execute the statement
            if (!$stmt->execute()) {
                echo "Error inserting data into the database.";
                exit();
            }
        }
        
        // Close the statement
        $stmt->close();
        
        // You can add further logic here, e.g., redirect the user or display a confirmation message
        echo "Answers submitted successfully";
        exit(); // Terminate the script after submission
    } else {
        echo "Error preparing the database statement.";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCQ Quiz</title>
    <style>
        /* Add your CSS styles here */
    </style>
</head>
<body>
    <form id="mcqForm" method="post">
        <div class="question" id="question"></div>
        <div class="options" id="options"></div>
        <div id="timer">Time remaining: 10 seconds</div>
        <button id="nextButton" type="button">Next</button>
        <button id="submitButton" type="submit" style="display: none;">Submit</button>
    </form>

    <script>
        // Define your MCQ questions and answers here
        const questions = <?php echo json_encode($questions); ?>;
        
        let currentQuestion = 0;
        let timeLeft = 10; // Initial time for each question in seconds

        const questionElement = document.getElementById("question");
        const optionsElement = document.getElementById("options");
        const timerElement = document.getElementById("timer");
        const nextButton = document.getElementById("nextButton");
        const submitButton = document.getElementById("submitButton");

        const selectedAnswers = []; // Array to store selected answers

        function displayQuestion() {
            questionElement.textContent = questions[currentQuestion].question;
            optionsElement.innerHTML = "";
            
            questions[currentQuestion].options.forEach((option, index) => {
                const optionElement = document.createElement("div");
                optionElement.classList.add("option");
                const radioButton = document.createElement("input");
                radioButton.type = "radio";
                radioButton.name = "answer";
                radioButton.value = option;
                radioButton.addEventListener("change", function () {
                    selectedAnswers[currentQuestion] = option; // Store the selected answer
                    nextButton.disabled = false; // Enable the "Next" button
                });
                optionElement.appendChild(radioButton);
                optionElement.appendChild(document.createTextNode(`${index + 1}. ${option}`));
                optionsElement.appendChild(optionElement);
            });

            // Disable the "Next" button initially
            nextButton.disabled = true;

            // Set the timer to 10 seconds
            timeLeft = 10;

            const timerInterval = setInterval(function () {
                timeLeft--;
                timerElement.textContent = `Time remaining: ${timeLeft} seconds`;

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    loadNextQuestion(); // Automatically load the next question when the timer runs out
                }
            }, 1000);
        }

        function loadNextQuestion() {
            currentQuestion++;
            if (currentQuestion < questions.length) {
                displayQuestion(); // Display the next question
                nextButton.disabled = true; // Disable the "Next" button again
            } else {
                // All questions answered, show the submit button
                nextButton.style.display = "none";
                submitButton.style.display = "block";
            }
        }

        function logSelectedAnswers() {
            console.log("Selected Answers:");
            for (let i = 0; i < questions.length; i++) {
                console.log(`Question ${i + 1}: ${questions[i].question}`);
                console.log(`Selected Answer: ${selectedAnswers[i] || 'NULL'}`);
            }
        }

        nextButton.addEventListener("click", loadNextQuestion);

        // Load the first question when the page loads
        displayQuestion();

        // Handle form submission
        const mcqForm = document.getElementById("mcqForm");
        mcqForm.addEventListener("submit", function (event) {
            event.preventDefault();
            
            // Prepare the data to be sent to the server
            const answersData = {
                questions: questions.map((question, index) => ({
                    question: question.question,
                    selectedAnswer: selectedAnswers[index] || 'NULL',
                })),
            };

            // Send the data to the server using a POST request
            fetch(window.location.href, {
                method: 'POST',
                body: JSON.stringify({ answers: answersData }),
                headers: {
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.text())
            .then(data => {
                alert(data); // Display the server response (e.g., "Answers submitted successfully")
                // You can add further logic here, e.g., redirect the user
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting answers.');
            });
        });
    </script>
</body>
</html>
============================================================================================= -->
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

// Initialize variables
$currentStep = isset($_SESSION['current_step']) ? $_SESSION['current_step'] : 0;
$answeredMCQs = isset($_SESSION['answered_mcqs']) ? $_SESSION['answered_mcqs'] : array();
$userCourse = array();

try {
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
        }

        // Fetch the MCQs associated with the exam in a randomized order
        $exmId = $_GET['exam_id'];

        $exmIdstmt = $conn->prepare("SELECT em.exam_mcq, em.Exam_id, em.mcq_id, m.question, m.option1, m.option2, m.option3, m.option4, m.m_weightage, m.correct_answer
        FROM exam_mcq AS em
        JOIN mcq AS m ON em.mcq_id = m.mcq_id
        WHERE em.Exam_id = ?");
        $exmIdstmt->bind_param("i", $exmId);
        $exmIdstmt->execute();
        $exmResult = $exmIdstmt->get_result();

        // Fetch all MCQs and store them in an array
        $mcqs = array();
        while ($row = $exmResult->fetch_assoc()) {
            $mcqs[] = $row;
        }

        $exmIdstmt->close();

        // Initialize an array to store questions and options
        $questions = array();

        // Convert MCQs into questions and options
        foreach ($mcqs as $mcq) {
            $question = $mcq['question'];
            $options = array(
                $mcq['option1'],
                $mcq['option2'],
                $mcq['option3'],
                $mcq['option4']
            );

            $questions[] = array(
                'question' => $question,
                'options' => $options
            );
        }
    } else {
        throw new Exception("Failed to fetch user data.");
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCQ Quiz</title>
    <style>
        /* Add your CSS styles here */
    </style>
</head>
<body>
    <div class="question" id="question"></div>
    <div class="options" id="options"></div>
    <div id="timer">Time remaining: 10 seconds</div>
    <button id="nextButton">Next</button>

    <script>
        // Define your MCQ questions and answers here
        const questions = <?php echo json_encode($questions); ?>;
        
        let currentQuestion = 0;
        let timeLeft = 10; // Initial time for each question in seconds

        const questionElement = document.getElementById("question");
        const optionsElement = document.getElementById("options");
        const timerElement = document.getElementById("timer");
        const nextButton = document.getElementById("nextButton");

        const selectedAnswers = []; // Array to store selected answers

// Declare the timerInterval variable in the global scope
let timerInterval;

function displayQuestion() {
    // Reset the timer
    clearInterval(timerInterval);
    
    questionElement.textContent = questions[currentQuestion].question;
    optionsElement.innerHTML = "";
    
    questions[currentQuestion].options.forEach((option, index) => {
        const optionElement = document.createElement("div");
        optionElement.classList.add("option");
        const radioButton = document.createElement("input");
        radioButton.type = "radio";
        radioButton.name = "answer";
        radioButton.value = option;
        radioButton.addEventListener("change", function () {
            selectedAnswers[currentQuestion] = option; // Store the selected answer
            nextButton.disabled = false; // Enable the "Next" button
        });
        optionElement.appendChild(radioButton);
        optionElement.appendChild(document.createTextNode(`${index + 1}. ${option}`));
        optionsElement.appendChild(optionElement);
    });

    // Disable the "Next" button initially
    nextButton.disabled = true;

    // Set the timer to 10 seconds
    timeLeft = 10;

    timerInterval = setInterval(function () {
        timeLeft--;
        timerElement.textContent = `Time remaining: ${timeLeft} seconds`;

        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            loadNextQuestion(); // Automatically load the next question when the timer runs out
        }
    }, 1000);
}
        function loadNextQuestion() {
            currentQuestion++;
            if (currentQuestion < questions.length) {
                displayQuestion(); // Display the next question
                nextButton.disabled = true; // Disable the "Next" button again
            } else {
                alert("Test completed!");
                logSelectedAnswers(); // Log the selected answers when the test is completed
            }
        }

        function logSelectedAnswers() {
            console.log("Selected Answers:");
            for (let i = 0; i < questions.length; i++) {
                console.log(`Question ${i + 1}: ${questions[i].question}`);
                console.log(`Selected Answer: ${selectedAnswers[i] || 'Null'}`);
            }
        }

        nextButton.addEventListener("click", loadNextQuestion);

        // Load the first question when the page loads
        displayQuestion();

    </script>
</body>
</html>

