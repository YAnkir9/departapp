<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'config.php';

require 'vendor/autoload.php'; // Include PhpSpreadsheet autoloader

// Handle the form submission to add True/False questions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_tf_manual'])) {
        // Handle manual addition of True/False questions
        if (isset($_POST['subject']) && isset($_POST['topic'])) {
            $subjectId = $_POST['subject'];
            $topicId = $_POST['topic'];

            // Debugging: Check values of $subjectId and $topicId
            echo "Subject ID (True/False): " . $subjectId . "<br>";
            echo "Topic ID (True/False): " . $topicId . "<br>";

            $numQuestions = count($_POST['test_question']);
            if ($numQuestions < 1) {
                echo "Please add at least one True/False question.";
            } else {
                $stmt = $conn->prepare("INSERT INTO test_content (test_question_type, test_question, 
                        test_correct_answer, test_que_weightage, test_sub_id, test_topic_id, 
                        create_time) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())");

                $questionType = "1"; // Set default value to "1" for True/False questions
                for ($i = 0; $i < $numQuestions; $i++) {
                    $question = $_POST['tf_question'][$i];
                    $correctAnswer = $_POST['tf_correct_answer'][$i];
                    $weightage = $_POST['tf_weightage'][$i];

                    $stmt->bind_param("ssdiii", $questionType, $question, $correctAnswer, $weightage, $subjectId, $topicId);
                    $result = $stmt->execute();

                    if (!$result) {
                        echo "Error adding True/False question: " . $conn->error;
                        break; // Exit the loop if an error occurs
                    }
                }

                $stmt->close();

                if ($result) {
                    echo "True/False questions added successfully.";
                }
            }
        } else {
            echo "Missing subject ID or True/False data.";
        }
    }

    if (isset($_POST['submit_tf_excel'])) {
        // Handle import of True/False questions from Excel file
        if (isset($_POST['subject']) && isset($_POST['topic'])) {
            $subjectId = $_POST['subject'];
            $topicId = $_POST['topic'];

            // Debugging: Check values of $subjectId and $topicId
            echo "Subject ID (True/False): " . $subjectId . "<br>";
            echo "Topic ID (True/False): " . $topicId . "<br>";
            print_r($_FILES['tfFile']);

            // Import data from Excel file
            if (isset($_FILES['tfFile']) && $_FILES['tfFile']['error'] == 0) {
                $excelFile = $_FILES['tfFile']['tmp_name'];

                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelFile);
                $worksheet = $spreadsheet->getActiveSheet();
                $highestRow = $worksheet->getHighestRow();

                $stmt = $conn->prepare("INSERT INTO test_content (test_question_type, test_question, 
                        test_correct_answer, test_que_weightage, test_sub_id, test_topic_id, create_time) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())");

                $questionType = "1"; // Set default value to "1" for True/False questions
                for ($row = 2; $row <= $highestRow; $row++) {
                    $question = $worksheet->getCell('A' . $row)->getValue();
                    $correctAnswer = $worksheet->getCell('B' . $row)->getValue();
                    $weightage = $worksheet->getCell('C' . $row)->getValue();

                    $stmt->bind_param("ssdiii", $questionType, $question, $correctAnswer, $weightage, $subjectId, $topicId);
                    $result = $stmt->execute();

                    if (!$result) {
                        echo "Error adding True/False question from Excel: " . $conn->error;
                        break; // Exit the loop if an error occurs
                    }
                }

                $stmt->close();

                if ($result) {
                    echo "True/False questions added successfully from Excel file.";
                }
            } else {
                echo "Error uploading Excel file: " . ($_FILES['tfFile']['error'] ?? 'Unknown error');
            }
        } else {
            echo "Missing subject ID or True/False data.";
        }
    }
    
    header("Location: faculty_add_question.php");
    exit();
}
?>
