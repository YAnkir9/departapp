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
            $csvData = "Serial No.,Exam Name,User ID,User Name,First Name,Last Name,Total Marks,Obtained Marks,Percentage\n";

            $serialNumber = 1;

            while ($user = $usersResult->fetch_assoc()) {
                $userId = $user['User_id'];
                $userName = $user['User_name'];
                $firstName = $user['first_name'];
                $lastName = $user['last_name'];
                $totalMarks = $user['total_weightage']; // Get the total marks
                $obtainedMarks = $user['obtained_weightage']; // Get the obtained marks

                // Concatenate the row data into the CSV string
                $csvData .= "{$serialNumber},{$examName},{$userId},{$userName},{$firstName},{$lastName},{$totalMarks},{$obtainedMarks},";

                // Calculate the percentage only if the total marks is not zero
                $percentage = ($totalMarks != 0) ? ($obtainedMarks / $totalMarks) * 100 : 0;
                $csvData .= "{$percentage}\n";

                $serialNumber++;
            }

            // Set the CSV file headers for download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="'.$examName.'.csv"');

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
