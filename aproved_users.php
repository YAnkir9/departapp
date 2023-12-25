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
<?php 

// Query for the first table
$sql1 = "SELECT * FROM users where credential='student' and is_approved=1";

$result1 = $conn->query($sql1);

// Query for the second table
$sql2 = "SELECT * FROM users where credential='faculty' and is_approved=1";

$result2 = $conn->query($sql2);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<link rel="stylesheet" href="indx.css">
<link rel="stylesheet" href="aproved_user.css">

<script src="close.js"></script>
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
                <span class="logo_name">Admin</span>
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
            <i class="fas fa-user-cog admin-icon"></i>
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
  <!-- Top header -->
  <div class="row header">
    <div class="col">
      <button id="studentBtn" class="btn tab-button active">Students</button>
    </div>
    <div class="col">
      <button id="facultyBtn" class="btn tab-button">Faculty</button>
    </div>
  </div>

  <!-- Tab content -->
  <div class="row">
    <div class="col-md-12">
      <div id="studentTable" class="tab-content active">
        <!-- Student table content goes here -->
         <h2>Students (Approved)</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>User Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result1->num_rows > 0): ?>
                                <?php while ($student = $result1->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $student['User_id']; ?></td>
                                        <td><?php echo $student['first_name']; ?></td>
                                        <td><?php echo $student['last_name']; ?></td>
                                        <td><?php echo $student['Email']; ?></td>
                                        <td><?php echo $student['User_name']; ?></td>
                                        <td>
                                            <a class="btn btn-info" href="aproved_user_update.php?id=<?php echo $student['User_id']; ?>">Edit</a>
                                            <a class="btn btn-danger" href="aproved_user_delete.php?id=<?php echo $student['User_id']; ?>">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </table>
      </div>

      <div id="facultyTable" class="tab-content">
        <!-- Faculty table content goes here -->
        <h2>Faculty Members (Approved)</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>User</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result2->num_rows > 0): ?>
                                <?php while ($faculty = $result2->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $faculty['User_id']; ?></td>
                                        <td><?php echo $faculty['first_name']; ?></td>
                                        <td><?php echo $faculty['last_name']; ?></td>
                                        <td><?php echo $faculty['Email']; ?></td>
                                        <td><?php echo $faculty['User_name']; ?></td>
                                        <td>
                                            <a class="btn btn-info" href="aproved_user_update.php?id=<?php echo $faculty['User_id']; ?>">Edit</a>
                                            <a class="btn btn-danger" href="aproved_user_delete.php?id=<?php echo $faculty['User_id']; ?>">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
  // JavaScript to handle tab switching
  $(document).ready(function() {
    $("#studentBtn").click(function() {
      $("#studentBtn").addClass("active");
      $("#facultyBtn").removeClass("active");
      $("#studentTable").addClass("active");
      $("#facultyTable").removeClass("active");
    });

    $("#facultyBtn").click(function() {
      $("#facultyBtn").addClass("active");
      $("#studentBtn").removeClass("active");
      $("#facultyTable").addClass("active");
      $("#studentTable").removeClass("active");
    });
  });
</script>
    </body>
</html>

