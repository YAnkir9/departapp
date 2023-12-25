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

$resultsStmt = $conn->prepare("SELECT m.question, m.correct_answer, m.m_weightage, r.user_answer, r.m_obtain
FROM mcq_results AS r
JOIN mcq AS m ON r.mcq_id = m.mcq_id
WHERE r.user_id = ? AND r.Exam_id = ?");
$resultsStmt->bind_param("ii", $_SESSION['User_id'], $examId);
$resultsStmt->execute();
$resultsResult = $resultsStmt->get_result();

$results = array();
while ($row = $resultsResult->fetch_assoc()) {
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


// Calculate the percentage
$percentage = ($obtainedWeightage / $totalWeightage) * 100;

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
/* Add your existing styles here */

/* Header styles */
header {
    background-color: #333;
    color: white;
    padding: 10px;
    text-align: center;
}

nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    display: flex;
    align-items: center;
}

.logo img {
    width: 40px;
    margin-right: 10px;
}

.head {
    font-size: 1.5rem;
    font-weight: bold;
}

/* Sidebar styles */
#sidebar {
    width: 250px;
    height: 100%;
    position: fixed;
    left: -250px;
    top: 0;
    background-color: #222;
    padding-top: 50px;
    overflow-x: hidden;
    transition: 0.5s;
}

.sidebar-nav {
    padding: 20px;
}

.menu_items {
    padding: 10px;
}

.logo_name {
    font-size: 1.2rem;
    font-weight: bold;
}

.link {
    color: #fff;
    text-decoration: none;
    display: flex;
    align-items: center;
}

.link i {
    margin-right: 10px;
}

/* Content area styles */
#content {
    margin-left: 250px;
    padding: 20px;
}

.user-details {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.user-details-column {
    width: 24%;
}

.icon-container {
    text-align: right;
}

.icon-container a {
    color: #007bff;
}

/* Exam result card styles */
.card {
    margin-bottom: 20px;
}

.card-header {
    background-color: #007bff;
    color: #fff;
    padding: 10px;
}

.card-body {
    padding: 20px;
}

.table {
    width: 100%;
}

.table th, .table td {
    text-align: center;
}

/* Responsive styles */
@media (max-width: 768px) {
    #sidebar {
        left: 0;
    }

    #content {
        margin-left: 0;
    }
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


            <!-- Display the exam result -->
            <div class="container">
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>MCQ Results</h4>
                </div>
                <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Student Details</h4>
                </div>
                <div class="card-body">
                    <p><strong>Username:</strong> <?php echo $user['User_name']; ?></p>
                    <p><strong>First Name:</strong> <?php echo $user['first_name']; ?></p>
                    <p><strong>Last Name:</strong> <?php echo $user['last_name']; ?></p>
                    <p><strong>Course :</strong> <?php echo $userCourse['Course_name']; ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Exam Details</h4>
                </div>
                <div class="card-body">
                    <p><strong>Exam Name:</strong> <?php echo $exam['Exam_name']; ?></p>
                    <p><strong>Total Marks:</strong> <?php echo $totalWeightage; ?></p>
                    <p><strong>Marks Obtained:</strong> <?php echo $obtainedWeightage; ?></p>
                    <p><strong>Percentage:</strong> <?php echo $percentage; ?>%</p>
                </div>
            </div>
        </div>
    </div>

                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Question</th>
                                <th>Selected Option</th>
                                <th>Correct Option</th>
                                <th>Obtained Marks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $result): ?>
                                <tr>
                                    <td><?php echo $result['question']; ?></td>
                                    <td><?php echo $result['user_answer']; ?></td>
                                    <td><?php echo $result['correct_answer']; ?></td>
                                    <td><?php echo $result['m_obtain']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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
