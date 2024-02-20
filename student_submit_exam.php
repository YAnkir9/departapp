<?php
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
$stmt = $conn->prepare("SELECT first_name, last_name, User_name, User_id, Course_id FROM users WHERE User_id = ?");
$stmt->bind_param("i", $_SESSION['User_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();


$course = $user['Course_id'];

// Fetch the course name from the courses table
$courseStmt = $conn->prepare("SELECT * FROM cources WHERE Course_id = ?");
$courseStmt->bind_param("i", $course);
$courseStmt->execute();

$result = $courseStmt->get_result();
$userCourse = $result->fetch_assoc();

$courseStmt->close();

// Get the exam ID and percentage from the URL
$examId = $_GET['exam_id'];

// Fetch the exam details from the database
$examStmt = $conn->prepare("SELECT * FROM exam WHERE Exam_id = ?");
$examStmt->bind_param("i", $examId);
$examStmt->execute();

$examResult = $examStmt->get_result();
$exam = $examResult->fetch_assoc();

$examStmt->close();

$resultsStmt = $conn->prepare("SELECT m.question,m.option1,m.option2,m.option3,m.option4, m.correct_answer, m.m_weightage, r.user_answer, r.m_obtain
FROM mcq_results AS r
JOIN mcq AS m ON r.mcq_id = m.mcq_id
WHERE r.user_id = ? AND r.Exam_id = ?");
$resultsStmt->bind_param("ii", $_SESSION['User_id'], $examId);
$resultsStmt->execute();
$resultsResult = $resultsStmt->get_result();

$results = array();
while ($row = $resultsResult->fetch_assoc()) {
    // Get the option values for user_answer and correct_answer from the database
    $correctAnswerValue = $row[$optionColumns[$correctAnswerOption]];


    $row['m_obtain'] = ($row['user_answer'] == $row['correct_answer']) ? $row['m_weightage'] : 0;
    $results[] = $row;
}
$resultsStmt->close();

$totalWeightage = 0;
$obtainedWeightage = 0;

foreach ($results as $result) {
    $totalWeightage += $result['m_weightage'];

    // Check if the user's answer is correct
    if ($result['user_answer'] == $result['correct_answer']) {
        $obtainedWeightage += $result['m_weightage'];
    } else {
        // Set the obtained weightage to 0 if the answer is incorrect
        $obtainedWeightage += 0;
    }
}

// Calculate the percentage if $totalWeightage is not zero
$percentage = ($totalWeightage != 0) ? ($obtainedWeightage / $totalWeightage) * 100 : 0;


?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
 
    <link rel="stylesheet" href="indx.css">
 <style>
    /* Container styles */
    .container {
    max-width: 800px;
    margin: 20px auto;
}

.card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 20px;
}

.card-header {
    background-color: #007bff;
    color: #fff;
    padding: 15px;
    font-weight: bold;
}

.card-body {
    padding: 20px;
}

.details-container {
    display: flex;
    justify-content: space-around;
    margin-bottom: 20px;
}

.details-card {
    flex: 0 0 calc(50% - 10px);
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.details-card h4 {
    margin-top: 0;
    margin-bottom: 10px;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th,
.table td {
    border: 1px solid #ddd;
    padding: 12px;
}

.table th {
    background-color: #f2f2f2;
    font-weight: bold;
    text-align: left;
}

.table tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}

.table tbody tr:hover {
    background-color: #f0f0f0;
}
 </style>
</head>
<body>
    <header>
        <nav>
            <button class="toggle-button" id="toggleSidebar">☰</button>
            <div class="logo"><span class="nav_image">
                    <img src="image/Logo.png" alt="logo_img" />
                </span>
            </div><br>
            <div>
                <p class="head">Student Progressive Assessment System</p>
            </div>
            <ul class="top-nav">
            </ul>
        </nav>
    </header>

    <!-- Left side sliding navigation bar -->
    <aside id="sidebar">
    <button class="close-button" id="closeSidebar">✖</button>
    <ul class="sidebar-nav">
        <div class="menu_container">

        <div class="menu_items">
        <div class="logo logo_items flex">
                <span class="nav_image">
                    <img src="image/Logo.png" alt="logo_img" />
                </span>
                <span class="logo_name">Student</span>
            </div>

        <ul class="menu_item">
                    <?php foreach ($navLinks as $link): ?>
                        <li class="item">
                            <a href="<?php echo $link['href']; ?>" class="link flex">
                                <?php if (isset($link['icon'])): ?>
                                    <i class="<?php echo $link['icon']; ?>"></i>
                                <?php endif; ?>
                                <span><?php echo $link['label']; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </ul>
</aside>    <!-- Content Area on the Right Side -->
    <div id="content">
        <div class="main-top">
            <i class="fas fa-user-graduate"></i>
        </div>
         
        <!-- Your content goes here -->

        <div class="user-details">
        <div class="user-details-column">
                <div class="user-details-container">
                    <label for="username" class="form-label">Course:</label>
                    <span class="user-name"><?php echo $userCourse['Course_name']; ?></span>
                </div>
            </div>
            <div class="user-details-column">
                <div class="user-details-container">
                    <label for="name" class="form-label">Name:</label>
                    <span class="user-name"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></span>
                </div>
            </div>
            <div class="user-details-column">
                <div class="user-details-container">
                    <label for="username" class="form-label">Username:</label>
                    <span class="user-name"><?php echo $user['User_name']; ?></span>
                </div>
            </div>
    <div class="user-details-column">
        <div class="icon-container">
            <a href="#">
                <i class="fas fa-info-circle">   Profile</i>
            </a>
        </div>
    </div>

</div>


<div class="container">
        <div class="card">
            <div class="card-header">
                <h4>MCQ Results</h4>
            </div>
            <div class="card-body">
                <div class="details-container">
                    <!-- Student details card -->
                    <div class="details-card">
                        <h4>Student Details</h4>
                        <p><strong>Username:</strong> <?php echo $user['User_name']; ?></p>
                        <p><strong>First Name:</strong> <?php echo $user['first_name']; ?></p>
                        <p><strong>Last Name:</strong> <?php echo $user['last_name']; ?></p>
                        <p><strong>Course:</strong> <?php echo $userCourse['Course_name']; ?></p>
                    </div>
                    <!-- Exam details card -->
                    <div class="details-card">
                        <h4>Exam Details</h4>
                        <p><strong>Exam Name:</strong> <?php echo $exam['Exam_name']; ?></p>
                        <p><strong>Total Marks:</strong> <?php echo $totalWeightage; ?></p>
                        <p><strong>Marks Obtained:</strong> <?php echo $obtainedWeightage; ?></p>
                        <p><strong>Percentage:</strong> <?php echo $percentage; ?>%</p>
                    </div>
                </div>
                <!-- Exam result table -->
                <table class="table">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Selected Option</th>
                            <th>Correct Option</th>
                            <th>Obtained Marks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): 
            $userAnswerOption = $result['user_answer'];
            $correctAnswerOption = $result['correct_answer'];
            
            // Map the option values to the corresponding database columns
            $optionColumns = [
                'A' => 'option1',
                'B' => 'option2',
                'C' => 'option3',
                'D' => 'option4'
            ];
            
            $userAnswerValue = $result[$optionColumns[$userAnswerOption]];
            $correctAnswerValue = $result[$optionColumns[$correctAnswerOption]];
            
            $m_obtain = ($userAnswerOption == $correctAnswerOption) ? $result['m_weightage'] : 0;
        
                        ?>
                            <tr>
                                <td><?php echo $result['question']; ?></td>
                                <td><?php echo $userAnswerValue; ?></td>
                                <td><?php echo $correctAnswerValue; ?></td>
                                <td><?php echo $result['m_obtain']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
        </section>
    </div>
    <script>
        // Updated JavaScript for sidebar functionality

document.getElementById('toggleSidebar').addEventListener('click', function () {
    document.getElementById('sidebar').style.left = '0';
});

document.getElementById('closeSidebar').addEventListener('click', function () {
    document.getElementById('sidebar').style.left = '-250px';
});

    </script>
</body>
</html>
