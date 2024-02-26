<?php
// Set error reporting and display errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include 'config.php';

require 'vendor/autoload.php'; // Include PhpSpreadsheet autoloader

// Handle the form submission to add MCQs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_topic'])) {
        // Handle manual addition of MCQs
        if (isset($_POST['subject']) && isset($_POST['new_topic'])) {
            $subjectId = $_POST['subject'];
            $NewTopic = $_POST['new_topic'];
    
            // Debugging: Check values of $subjectId and $topicId
            echo "Subject ID (MCQ): " . $subjectId . "<br>";
    
            $stmt = $conn->prepare("INSERT INTO topics (topic_name, subject_id) VALUES (?, ?)");
            $stmt->bind_param("si", $NewTopic, $subjectId);
            $result = $stmt->execute();
    
            $stmt->close();
    
            if ($result) {
                echo '<script type="text/javascript">window.alert("Topic added successfully."); window.location.href = "faculty_add_question.php";</script>';
            } else {
                echo '<script type="text/javascript">window.alert("Failed to add topic."); window.location.href = "faculty_add_question.php";</script>';
            }
        } else {
            $errorMessage = "Missing subject ID or topic name";
            echo '<script type="text/javascript">window.alert("' . $errorMessage . '"); window.location.href = "faculty_add_question.php";</script>';
        }
    }
    
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
                $errorMessage = "Please add at least one MCQ question.";
                echo '<script type="text/javascript">window.alert("' . $errorMessage . '"); window.location.href = "faculty_add_question.php";</script>';
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
                        $errorMessage = "Error adding MCQ: " . $conn->error;
                        echo '<script type="text/javascript">window.alert("' . $errorMessage . '"); window.location.href = "faculty_add_question.php";</script>';
                        break; // Exit the loop if an error occurs
                    }
                }

                $stmt->close();

                if ($result) {
                    echo '<script type="text/javascript">window.alert("MCQs added successfully."); window.location.href = "faculty_add_question.php";</script>';
                }
            }
        } else {
            $errorMessage = "Missing subject ID or MCQ data.";
            echo '<script type="text/javascript">window.alert("' . $errorMessage . '"); window.location.href = "faculty_add_question.php";</script>';
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
                    $correctAnswer = strtoupper($worksheet->getCell('G' . $row)->getValue()); // Convert to uppercase for case-insensitive check

                    // Additional checks for empty values
                    if (empty($question) || empty($option1) || empty($option2) || empty($option3) || empty($option4) || empty($mWeightage) || empty($correctAnswer)) {
                        $errorMessage = "Error: Data in row $row cannot be empty.";
                        echo '<script type="text/javascript">window.alert("' . $errorMessage . '"); window.location.href = "faculty_add_question.php";</script>';
                        break; // Exit the loop if an error occurs
                    }

                    // Additional checks for data types
                    if (!is_numeric($mWeightage)) {
                        $errorMessage = "Error: Weightage in row $row must be a numeric value.";
                        echo '<script type="text/javascript">window.alert("' . $errorMessage . '"); window.location.href = "faculty_add_question.php";</script>';
                        break; // Exit the loop if an error occurs
                    }

                    // Additional checks for correct answer
                    if (!in_array($correctAnswer, ["A", "B", "C", "D"])) {
                        $errorMessage = "Error: Invalid correct answer in row $row. It must be one of 'A', 'B', 'C', or 'D'.";
                        echo '<script type="text/javascript">window.alert("' . $errorMessage . '"); window.location.href = "faculty_add_question.php";</script>';
                        break; // Exit the loop if an error occurs
                    }

                    // Continue with the binding and execution
                    $stmt->bind_param("sssssisii", $question, $option1, $option2, $option3, $option4, 
                        $mWeightage, $correctAnswer, $subjectId, $topicId);
                    $result = $stmt->execute();

                    if (!$result) {
                        $errorMessage = "Error adding MCQ from Excel: " . $conn->error;
                        echo '<script type="text/javascript">window.alert("' . $errorMessage . '"); window.location.href = "faculty_add_question.php";</script>';
                        break; // Exit the loop if an error occurs
                    }
                }

                $stmt->close();

                if ($result) {
                    echo '<script type="text/javascript">window.alert("MCQs added successfully from Excel file."); window.location.href = "faculty_add_question.php";</script>';
                }
            } else {
                $errorMessage = "Error uploading Excel file: " . ($_FILES['mcqFile']['error'] ?? 'Unknown error');
                echo '<script type="text/javascript">window.alert("' . $errorMessage . '"); window.location.href = "faculty_add_question.php";</script>';
            }
        } else {
            $errorMessage = "Missing subject ID or MCQ data.";
            echo '<script type="text/javascript">window.alert("' . $errorMessage . '"); window.location.href = "faculty_add_question.php";</script>';
        }
    }
    exit(); // No need for header("Location") here
}
?>
