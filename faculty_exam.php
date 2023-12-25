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
$stmt = $conn->prepare("SELECT first_name, last_name, User_name, User_id FROM users WHERE User_id = ?");
$stmt->bind_param("i", $_SESSION['User_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index Page</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<link rel="stylesheet" href="indx.css">
<!-- <link rel="stylesheet" href="home.css"> -->

<script src="close.js"></script>
<style>/* Custom card styles */
.custom-card {
    background-color: #f5f5f5; /* Background color for cards */
    border: 1px solid #ddd; /* Border for cards */
    border-radius: 5px; /* Rounded corners */
    margin-bottom: 20px; /* Spacing between cards */
}

.custom-card .card-body {
    padding: 20px; /* Padding inside the card body */
}

.custom-card .card-link {
    font-size: 18px; /* Font size for card links */
    color: #333; /* Text color for card links */
    text-decoration: none; /* Remove default underline */
}

.custom-card .card-link:hover {
    text-decoration: underline; /* Add underline on hover */
}

/* Custom tooltip styles (replace with your tooltip library or custom CSS) */
[data-tooltip] {
    position: relative;
    cursor: pointer;
}

[data-tooltip]:before {
    content: attr(data-tooltip);
    position: absolute;
    visibility: hidden;
    background-color: #333; /* Tooltip background color */
    color: #fff; /* Tooltip text color */
    padding: 5px 10px;
    border-radius: 5px;
    white-space: nowrap;
    z-index: 1;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

[data-tooltip]:hover:before {
    visibility: visible;
    opacity: 1;
}
</style>
</head>
<body>
    <!-- Top navigation bar with toggle button (for mobile) -->
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
                <span class="logo_name">Faculty</span>
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
            <i class="fas fa-chalkboard-teacher"></i>
        </div>
         
        <!-- Your content goes here -->
        <div class="user-details">
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
    <div class="row text-center">
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-body text-center">
                    <a href="faculty_create_exam.php" class="card-link">Create Exam</a>
                </div>
            </div>
        </div>
    
        <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-body text-center">
                    <a href="faculty_add_question.php" class="card-link" data-tooltip="Add Questions for each topics of subjects">Add Questions</a>
                </div>
            </div>
        </div>
    
            <div class="col-md-12">
            <div class="card custom-card">
                <div class="card-body text-center">
                    <a href="faculty_result_select.php" class="card-link">Result</a>
                </div>
            </div>
        </div>
    </div>

</div>
<div class="chart-container">
<div id="chart">
    <?php
        // Include the chart content 
        include 'faculty_avg_exam_chart.php';
    ?>
</div>
<div claidss="chart1">
    <?php
        // Include the chart content 
        include 'faculty_student_exam_chart.php';
    ?>
</div>
                                </div>
</body>
</html>

