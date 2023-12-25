<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'config.php';

require 'vendor/autoload.php'; // Include PhpSpreadsheet autoloader

// Handle the form submission to add MCQs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_mcq_manual'])) {
        // Handle manual addition of MCQs
        if (isset($_POST['subject']) && isset($_POST['topic'])) {
            $subjectId = $_POST['subject'];
            $topicId = $_POST['topic'];

            // Debugging: Check values of $subjectId and $topicId
            echo "Subject ID (MCQ): " . $subjectId . "<br>";
            echo "Topic ID (MCQ): " . $topicId . "<br>";

            $numQuestions = count($_POST['question']);
            if ($numQuestions < 1) {
                echo "Please add at least one MCQ question.";
            } else {
                $stmt = $conn->prepare("INSERT INTO mcq (question, option1, option2, option3, option4, 
                        m_weightage, correct_answer, subject_id, topic_id, create_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

                for ($i = 0; $i < $numQuestions; $i++) {
                    $question = $_POST['question'][$i];
                    $option1 = $_POST['option1'][$i];
                    $option2 = $_POST['option2'][$i];
                    $option3 = $_POST['option3'][$i];
                    $option4 = $_POST['option4'][$i];
                    $weightage = $_POST['m_weightage'][$i];
                    $correctAnswer = $_POST['correct_answer'][$i];

                    $stmt->bind_param("sssssisii", $question, $option1, $option2, $option3, $option4, 
                            $weightage, $correctAnswer, $subjectId, $topicId);
                    $result = $stmt->execute();

                    if (!$result) {
                        echo "Error adding MCQ: " . $conn->error;
                        break; // Exit the loop if an error occurs
                    }
                }

                $stmt->close();

                if ($result) {
                    echo "MCQs added successfully.";
                }
            }
        } else {
            echo "Missing subject ID or MCQ data.";
        }
    } 
    if (isset($_POST['submit_mcq_excel'])) {
        // Handle import of MCQs from Excel file
        if (isset($_POST['subject']) && isset($_POST['topic'])) {
            $subjectId = $_POST['subject'];
            $topicId = $_POST['topic'];

            // Debugging: Check values of $subjectId and $topicId
            echo "Subject ID (MCQ): " . $subjectId . "<br>";
            echo "Topic ID (MCQ): " . $topicId . "<br>";
            print_r($_FILES['mcqFile']);
            // Import data from Excel file
            if (isset($_FILES['mcqFile']) && $_FILES['mcqFile']['error'] == 0) {
                
                
                $excelFile = $_FILES['mcqFile']['tmp_name'];

                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelFile);
                $worksheet = $spreadsheet->getActiveSheet();
                $highestRow = $worksheet->getHighestRow();

                $stmt = $conn->prepare("INSERT INTO mcq (question, option1, option2, option3, option4, 
                        m_weightage, correct_answer, subject_id, topic_id, create_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

                for ($row = 2; $row <= $highestRow; $row++) {
                    $question = $worksheet->getCell('A' . $row)->getValue();
                    $option1 = $worksheet->getCell('B' . $row)->getValue();
                    $option2 = $worksheet->getCell('C' . $row)->getValue();
                    $option3 = $worksheet->getCell('D' . $row)->getValue();
                    $option4 = $worksheet->getCell('E' . $row)->getValue();
                    $mWeightage = $worksheet->getCell('F' . $row)->getValue();
                    $correctAnswer = $worksheet->getCell('G' . $row)->getValue();

                    $stmt->bind_param("sssssisii", $question, $option1, $option2, $option3, $option4, 
                            $mWeightage, $correctAnswer, $subjectId, $topicId);
                    $result = $stmt->execute();

                    if (!$result) {
                        echo "Error adding MCQ from Excel: " . $conn->error;
                        break; // Exit the loop if an error occurs
                    }
                }

                $stmt->close();

                if ($result) {
                    echo "MCQs added successfully from Excel file.";
                }
            } else {
                echo "Error uploading Excel file: " . ($_FILES['mcqFile']['error'] ?? 'Unknown error');
            }
        } else {
            echo "Missing subject ID or MCQ data.";
        }
    }
    header("Location: faculty_add_question.php");
    exit();
}
?>
