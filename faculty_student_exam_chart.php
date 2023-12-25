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

// Fetch subjects for the logged-in user
$subjectsStmt = $conn->prepare("SELECT Subject_id, Subject_name FROM subjects WHERE User_id = ?");
$subjectsStmt->bind_param("i", $_SESSION['User_id']);
$subjectsStmt->execute();
$subjectsResult = $subjectsStmt->get_result();

// Initialize arrays to store data for plotting
$chartData = array();

// Loop through subjects
while ($subject = $subjectsResult->fetch_assoc()) {
    $subjectId = $subject['Subject_id'];

    // Fetch exams for the subject along with the subject name  
    $examsStmt = $conn->prepare("SELECT e.Exam_id, e.Exam_name,e.Course_id, s.Subject_name 
        FROM exam e
        JOIN subjects s ON e.Subject_id = s.Subject_id
        WHERE e.Subject_id = ?");
    $examsStmt->bind_param("i",$subjectId);
    $examsStmt->execute();  
    $examsResult = $examsStmt->get_result();
    // var_dump($examsResult);
    // Loop through exams
    while ($exam = $examsResult->fetch_assoc()) {
        $examId = $exam['Exam_id'];
        $courseId = $exam['Course_id']; // Fetch Course_id from the exam table
    
        // Fetch users associated with the Course_id for the particular exam
        $usersStmt = $conn->prepare("SELECT u.User_name, r.m_obtain, COUNT(mcq_id) AS total_questions
            FROM users u
            JOIN mcq_results r ON u.User_id = r.user_id
            JOIN cources c ON u.Course_id = c.Course_id
            WHERE r.Exam_id = ? AND c.Course_id = ?
            GROUP BY u.User_id");
        $usersStmt->bind_param("ii", $examId, $courseId); // Assuming $courseId is the specific Course_id you want to filter
        $usersStmt->execute();
        $usersResult = $usersStmt->get_result();

        // Initialize arrays for storing data for each exam
        $chartData[$subject['Subject_name']][$exam['Exam_name']] = array();

        // Loop through users
        while ($user = $usersResult->fetch_assoc()) {
            // Calculate average percentage for each student across multiple exams
            $percentage = ($user['total_questions'] > 0) ? ($user['m_obtain'] / $user['total_questions']) * 100 : 0;

            // Store data for plotting
            $chartData[$subject['Subject_name']][$exam['Exam_name']][] = array(
                'student' => $user['User_name'],
                'percentage' => $percentage,
            );
        }

        $usersStmt->close();
    }

    $examsStmt->close();
}

$subjectsStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results Bar Diagram</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .subject-buttons {
            display: flex;
            justify-content: center;
            margin-bottom: 10px;
        }

        .subject-button {
            margin: 0 10px;
            padding: 5px 10px;
            cursor: pointer;
        }

        .subject-button:hover {
            background-color: #ddd;
        }

        .subject-slider {
            display: flex;
            flex-direction: column;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 10px;
            width: 80%; /* Adjust width as needed */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .subject-slider canvas {
            margin: 0 auto;
        }
    </style>
</head>
<body>

<!-- Subject buttons -->
<div class="subject-slider">
    <h2>Exam Results Bar Diagram</h2>
    <div class="subject-buttons">
        <?php foreach ($chartData as $subjectName => $subjectExams) { ?>
            <?php foreach ($subjectExams as $examName => $students) { ?>
                <div class="subject-button" onclick="showExamChart('<?php echo $subjectName; ?>', '<?php echo $examName; ?>')">
                    <?php echo $examName; ?>
                </div>
            <?php } ?>
        <?php } ?>
    </div>

    <!-- Exam charts container -->
    <?php foreach ($chartData as $subjectName => $subjectExams) { ?>
        <?php foreach ($subjectExams as $examName => $students) { ?>
            <div class="exam-container <?php echo str_replace(' ', '', $subjectName) . str_replace(' ', '', $examName); ?>" style="display: none;">
                <h3><?php echo $examName; ?></h3>
                <canvas id="<?php echo str_replace(' ', '', $subjectName) . str_replace(' ', '', $examName); ?>Chart" width="600" height="300"></canvas>
            </div>
        <?php } ?>
    <?php } ?>
</div>

<script>
    // Function to show the selected exam chart
    function showExamChart(subjectName, examName) {
        // Hide all exam containers
        const containers = document.querySelectorAll('.exam-container');
        containers.forEach(container => {
            container.style.display = 'none';
        });

        // Show the selected exam container
        const selectedContainer = document.querySelector('.' + subjectName + examName);
        if (selectedContainer) {
            selectedContainer.style.display = 'block';
        }

        // Retrieve data for the selected exam
        const chartData = <?php echo json_encode($chartData); ?>;
        const selectedData = chartData[subjectName][examName];

        // Get the canvas element and its context
        const canvasId = subjectName.replace(/ /g, '') + examName.replace(/ /g, '') + 'Chart';
        const canvas = document.getElementById(canvasId);
        const ctx = canvas.getContext('2d');

        // Destroy the existing chart if it exists
        if (canvas.chart) {
            canvas.chart.destroy();
        }

        // Extract student names and percentages from the selected data
        const labels = selectedData.map(item => item.student);
        const percentages = selectedData.map(item => item.percentage);

        // Draw the chart using Chart.js
        const myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Percentage Score',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    data: percentages,
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100 // Assuming the percentage scale
                    }
                }
            }
        });

        // Save the chart reference to the canvas for later destruction
        canvas.chart = myChart;
    }
</script>
</body>
</html>