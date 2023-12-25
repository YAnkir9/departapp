<?php
// Include the database connection file
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve data sent via POST request
    $userId = $_POST['user_id'];
    $examId = $_POST['Exam_id'];
    $mcqId = $_POST['mcq_id'];
    $userAnswer = $_POST['user_answer'];
    $obtainedWeightage = $_POST['m_obtain'];

    // Retrieve the correct answer and weightage for the MCQ from the database
    $stmt = $conn->prepare("SELECT correct_answer, m_weightage FROM mcq WHERE mcq_id = ?");
    $stmt->bind_param("i", $mcqId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $correctAnswer = $row['correct_answer'];
        $weightage = $row['m_weightage'];

        // Check if the user's answer is correct
        if ($userAnswer === $correctAnswer) {
            $obtainedWeightage += $weightage;
        }

        // Perform the insertion into the database
        try {
            $stmt = $conn->prepare("INSERT INTO mcq_results (user_id, Exam_id, mcq_id, user_answer, m_obtain) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisi", $userId, $examId, $mcqId, $userAnswer, $obtainedWeightage);
            $stmt->execute();
            $stmt->close();

            // You can send a success response if needed
            echo "MCQ result inserted successfully!";
        } catch (Exception $e) {
            // Handle any exceptions or errors that occurred during the insertion
            echo "Error: " . $e->getMessage();
        }
    } else {
        // MCQ not found in the database
        echo "MCQ not found in the database.";
    }
} else {
    // Handle cases where the page is accessed directly without a POST request
    echo "Invalid request method.";
}
?>
