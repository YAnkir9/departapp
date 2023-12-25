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

// Fetch user data from the database for the logged-in user
$stmt = $conn->prepare("SELECT first_name, last_name, User_name, User_id FROM users WHERE User_id = ?");
$stmt->bind_param("i", $_SESSION['User_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch subjects for the logged-in user
$subjectsStmt = $conn->prepare("SELECT Subject_id, Subject_name FROM subjects WHERE User_id = ?");
$subjectsStmt->bind_param("i", $_SESSION['User_id']);
$subjectsStmt->execute();
$subjectsResult = $subjectsStmt->get_result();

// Initialize an array to store average percentages for each subject
$averagePercentagesBySubject = array();

// Loop through subjects
while ($subject = $subjectsResult->fetch_assoc()) {
    $subjectId = $subject['Subject_id'];

    // Fetch exams for the subject
    $examsStmt = $conn->prepare("SELECT Exam_id, Exam_name FROM exam WHERE User_id = ? AND Subject_id = ?");
    $examsStmt->bind_param("ii", $_SESSION['User_id'], $subjectId);
    $examsStmt->execute();
    $examsResult = $examsStmt->get_result();

    // Initialize an array to store average percentages for each exam
    $averagePercentages = array();

    // Loop through exams
    while ($exam = $examsResult->fetch_assoc()) {
        $examId = $exam['Exam_id'];

        // Calculate average percentages for each exam
        $resultsStmt = $conn->prepare("SELECT m.question, m.correct_answer, m.m_weightage, r.user_answer, r.m_obtain
                                        FROM mcq_results AS r
                                        JOIN mcq AS m ON r.mcq_id = m.mcq_id
                                        WHERE r.Exam_id = ?");
        $resultsStmt->bind_param("i", $examId);
        $resultsStmt->execute();
        $resultsResult = $resultsStmt->get_result();

        $totalWeightage = 0;
        $obtainedWeightage = 0;

        while ($row = $resultsResult->fetch_assoc()) {
            $totalWeightage += $row['m_weightage'];

            // Check if the user's answer is correct
            if ($row['user_answer'] == $row['correct_answer']) {
                $obtainedWeightage += $row['m_weightage'];
            }
        }

        // Check if total weightage is not zero to prevent DivisionByZeroError
        $percentage = ($totalWeightage != 0) ? ($obtainedWeightage / $totalWeightage) * 100 : 0;
        $averagePercentages[$exam['Exam_name']] = $percentage;

        $resultsStmt->close();
    }

    $examsStmt->close();

    // Store average percentages for the subject
    $averagePercentagesBySubject[$subject['Subject_name']] = $averagePercentages;
}

$subjectsStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
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

    .subject-slier canvas {
        margin: 0 auto;
    }

    .subject-slideshow {
        background-color: #f1f1f1;
        border: 1px solid #ddd;
        padding: 10px;
        margin: 10px;
        width: 80%; /* Adjust width as needed */
        height: 100%;
        position: relative;
    }

    .slides {
        list-style: none;
        padding: 0;
        margin: 0;
        height: 100%;
        overflow: hidden;
    }

    .slide {
        display: none;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }

    .slide p {
        margin: 0;
        padding: 10px;
        background-color: #fff;
        border: 1px solid #ddd;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
</style>
<body>

   

    <!-- Subject buttons -->
    <div class="subject-slider">
    <h2>Average Result Of Examn</h2>
    <div class="subject-buttons">
        <?php foreach ($averagePercentagesBySubject as $subjectName => $subjectData) { ?>
            <div class="subject-button" onclick="showSubjectChart('<?php echo str_replace(' ', '', $subjectName); ?>')">
                <?php echo $subjectName; ?>
            </div>
        <?php } ?>
    </div>

    <!-- Subject charts container -->
   
        <?php foreach ($averagePercentagesBySubject as $subjectName => $subjectData) { ?>
            <div class="subject-container" id="<?php echo str_replace(' ', '', $subjectName); ?>Container" style="<?php echo ($subjectName == array_key_first($averagePercentagesBySubject)) ? 'display: block;' : 'display: none;'; ?>">
                <h3><?php echo $subjectName; ?></h3>
                <canvas id="<?php echo str_replace(' ', '', $subjectName); ?>Chart" width="400" height="150"></canvas>

                <div class="subject-slideshow">
                    <ul class="slides">
                        <?php foreach ($subjectData as $examName => $examPercentage) { ?>
                            <li class="slide">
                                <p><?php echo $examName; ?>: <?php echo $examPercentage; ?>%</p>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        <?php } ?>
    </div>

    <script>
        // Function to show the selected subject chart
        function showSubjectChart(subjectId) {
            // Hide all subject containers
            const containers = document.querySelectorAll('.subject-container');
            containers.forEach(container => {
                container.style.display = 'none';
            });

            // Show the selected subject container
            const selectedContainer = document.getElementById(subjectId + 'Container');
            if (selectedContainer) {
                selectedContainer.style.display = 'block';
            }
        }

        // Your existing JavaScript code for slideshows and Chart.js remains unchanged

        <?php
        // PHP to JavaScript: Convert PHP array to JavaScript array
        echo "var subjectData = " . json_encode($averagePercentagesBySubject) . ";\n";
        ?>

        // Loop through subjects and draw charts
        for (var subjectName in subjectData) {
            if (subjectData.hasOwnProperty(subjectName)) {
                var examLabels = Object.keys(subjectData[subjectName]);
                var examPercentages = Object.values(subjectData[subjectName]);

                // Draw the chart using Chart.js
                var ctx = document.getElementById(subjectName.replace(' ', '') + 'Chart').getContext('2d');
                var myChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: examLabels,
                        datasets: [{
                            label: 'Average Percentage',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            data: examPercentages,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderWidth: 1
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
            }
        }
    </script>
</body>
</html>
