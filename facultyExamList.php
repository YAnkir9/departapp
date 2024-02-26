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

// Check if form is submitted for updating the exam status or deleting the exam
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["action"])) {
        if ($_POST["action"] === "updateStatus") {
            if (isset($_POST["examId"]) && isset($_POST["currentStatus"])) {
                $examId = $_POST["examId"];
                $currentStatus = $_POST["currentStatus"];

                // Update the status in the database
                $newStatus = ($currentStatus == 0) ? 1 : 0;
                $updateStmt = $conn->prepare("UPDATE exam SET Available = ? WHERE Exam_id = ?");
                $updateStmt->bind_param("ii", $newStatus, $examId);
                $updateStmt->execute();

                // Redirect to the same page to reflect changes
                header("Location: {$_SERVER['PHP_SELF']}");
                exit();
            }
        } elseif ($_POST["action"] === "deleteExam") {
            if (isset($_POST["examId"])) {
                $examId = $_POST["examId"];

                // Delete the exam from the database
                $deleteStmt = $conn->prepare("DELETE FROM exam WHERE Exam_id = ?");
                $deleteStmt->bind_param("i", $examId);
                $deleteStmt->execute();

                // Redirect to the same page after successful deletion
                header("Location: {$_SERVER['PHP_SELF']}");
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Status Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="indx.css">
    <link rel="stylesheet" href="home.css">
    <style>
        .exam-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .exam-card {
            border: 1px solid #ddd;
            padding: 10px;
            width: 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .status-button {
            margin-top: 10px;
            cursor: pointer;
            padding: 5px 10px;
        }

        .status-active {
            background-color: green;
            color: white;
        }

        .status-inactive {
            background-color: red;
            color: white;
        }

        .delete-button {
            margin-top: 10px;
            cursor: pointer;
            padding: 5px 10px;
            background-color: #ff6347; /* Tomato color */
            color: white;
            border: none;
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
    </aside>

    <!-- Content Area on the Right Side -->
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
        <main>
            <div class="exam-container">
                <?php
                // Fetch exam data from the database
                $sql = "SELECT Exam_id, Exam_name, total_marks, Available,Create_time FROM exam ORDER by Create_time DESC";
                $result = $conn->query($sql);

                // Check if there are results
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        ?>
                        <div class="exam-card" data-exam-id="<?php echo $row['Exam_id']; ?>">
                            <h3><?php echo $row["Exam_name"]; ?></h3>
                            <p>Total Marks: <?php echo $row["total_marks"]; ?></p>
                            <?php
                            $statusClass = ($row["Available"] == 1) ? 'status-active' : 'status-inactive';
                            $statusText = ($row["Available"] == 1) ? 'Active' : 'Inactive';
                            ?>
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="action" value="updateStatus">
                                <input type="hidden" name="examId" value="<?php echo $row['Exam_id']; ?>">
                                <input type="hidden" name="currentStatus" value="<?php echo $row['Available']; ?>">
                                <button type="submit" class="status-button <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </button>
                            </form>
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="action" value="deleteExam">
                                <input type="hidden" name="examId" value="<?php echo $row['Exam_id']; ?>">
                                <button type="submit" class="delete-button">Delete</button>
                            </form>
                        </div>
                        <?php
                    }
                } else {
                    echo "No exams found";
                }
                ?>
            </div>
        </main>
    </div>
</body>

</html>

<?php
// Close the database connection
$conn->close();
?>
