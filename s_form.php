<?php
// Set error reporting and display errors (for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include 'config.php';

// Check if the request is an AJAX request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Handle the AJAX request here

    // Ensure that the action is set
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'submit_answers') {
            // Process the submitted MCQ answers for the current step
            $answers = $_POST['answers']; // An array containing answers for each MCQ
            $obtainedWeightage = $_POST['obtained_weightage']; // Obtained weightage for this step
            $userId = $_POST['user_id']; // User ID
            $examId = $_POST['exam_id']; // Exam ID

            // Loop through each MCQ and insert the data into the database
            foreach ($answers as $mcqId => $userAnswer) {
                $stmt = $conn->prepare("INSERT INTO mcq_results (user_id, Exam_id, mcq_id, user_answer, m_obtain) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiisi", $userId, $examId, $mcqId, $userAnswer, $obtainedWeightage);
                $stmt->execute();
                // You may add error handling here if needed
            }

            // Send a success response back to the AJAX request
            echo json_encode(array('success' => true));
            exit();
        }
        error_log("Invalid action received: " . $_POST['action']);
    }

    // If the action is not set or not recognized, send an error response
    echo json_encode(array('error' => 'Invalid action'));
    exit();
} else {
    // This script should only handle AJAX requests
    // Redirect or handle non-AJAX requests as needed
    echo "Invalid request";
    exit();
}
?>