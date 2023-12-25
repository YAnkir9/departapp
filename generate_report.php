<?php
// Include the file that defines the navigation links
include 'nav.php';

// Include the database connection file
include 'config.php';

try {
    // Check if the user is logged in as a faculty member
    if (isset($_SESSION['credential']) && $_SESSION['credential'] == 'faculty' && isset($_SESSION['is_approved']) && $_SESSION['is_approved'] == 1) {
        // Fetch the exam details based on the exam_id passed in the URL
        if (isset($_GET['exam_id'])) {
            $examId = $_GET['exam_id'];

            // Fetching exam details
            $stmt = $conn->prepare("SELECT Exam_name FROM exam WHERE Exam_id = ?");
            $stmt->bind_param("i", $examId);
            $stmt->execute();
            $result = $stmt->get_result();
            $exam = $result->fetch_assoc();
            $examName = $exam['Exam_name'];

            // Fetch distinct users and their corresponding results
            $stmt = $conn->prepare("SELECT u.User_id, u.User_name, u.first_name, u.last_name, SUM(m.m_weightage) AS total_weightage, SUM(IF(r.user_answer = m.correct_answer, m.m_weightage, 0)) AS obtained_weightage
                                    FROM users AS u
                                    JOIN mcq_results AS r ON u.User_id = r.user_id
                                    JOIN mcq AS m ON r.mcq_id = m.mcq_id
                                    WHERE r.Exam_id = ?
                                    GROUP BY u.User_id");
            $stmt->bind_param("i", $examId);
            $stmt->execute();
            $usersResult = $stmt->get_result();

            // Prepare the CSV data
            $csvData .= '"Department Of Computer Science",,,,,,,,,'.PHP_EOL;
            $csvData .= '"Rollwala Computer Centre",,,,,,,,,'.PHP_EOL;
            $csvData .= '"Gujarat University",,,,,,,,,'.PHP_EOL.PHP_EOL;
            $csvData .= '"' . $examName . '",,,,,,,,'.PHP_EOL.PHP_EOL;

            $csvData .= '"' . $courseName . '",,,,,"","",""'.PHP_EOL.PHP_EOL;
            while($row = mysqli_fetch_assoc($result)) {
                $csvData .= $row['user_id'] . ',' . $row['obtained_marks'] . PHP_EOL; 
              }                
    



            // Footer
            $csvData .= "\nPlace : _________________\t\tName of Examiner : _____________________\n";
            $csvData .= "Date : __________________\n";
            $csvData .= "\t\t______________________________________\n";
            $csvData .= "\t\t(Signature Of the Examiner)\n";

            // Set the CSV file headers for download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $examName . '_formatted_output.csv"');

            // Output the CSV data
            echo $csvData;
            exit();
        } else {
            throw new Exception("Exam ID is not specified.");
        }
    } else {
        // Redirect to login page or display an error message
        header("Location: login.php");
        exit();
    }
} catch (Exception $e) {
    // Handle the exception and display an error message
    echo "An error occurred: " . $e->getMessage();
}
?>
