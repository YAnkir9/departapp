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
$userStmt = $conn->prepare("SELECT first_name, last_name, User_name, User_id FROM users WHERE User_id = ?");
$userStmt->bind_param("i", $_SESSION['User_id']);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();
?>
<?php
// Check if the credential has been selected
if (isset($_POST['credential'])) {
  $selectedCredential = $_POST['credential'];

  // List of unapproved users based on the selected credential
  $unapprovedStmt = $conn->prepare("SELECT * FROM users WHERE is_approved = 0 AND credential = ?");
  $unapprovedStmt->bind_param("s", $selectedCredential);
  $unapprovedStmt->execute();
  $unapprovedResult = $unapprovedStmt->get_result();
  $users = $unapprovedResult->fetch_all(MYSQLI_ASSOC);
  $unapprovedStmt->close();
} else {
  // List of all unapproved users
  $unapprovedStmt = $conn->prepare("SELECT * FROM users WHERE is_approved = 0");
  $unapprovedStmt->execute();
  $unapprovedResult = $unapprovedStmt->get_result();
  $users = $unapprovedResult->fetch_all(MYSQLI_ASSOC);
  $unapprovedStmt->close();
}

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // List of approved user IDs
  $approved_ids = isset($_POST['is_approved']) ? $_POST['is_approved'] : [];
  // List of removed user IDs
  $removed_ids = isset($_POST['remove_user']) ? $_POST['remove_user'] : [];

  // Update the users table with the approval status
  if (!empty($approved_ids)) {
    $id_list = implode(",", $approved_ids);

    $approveStmt = $conn->prepare("UPDATE users SET is_approved = 1 WHERE user_id IN ($id_list)");
    $approveStmt->execute();

    if ($approveStmt->error) {
      printf("Error: %s.\n", $approveStmt->error);
    } else {
      printf("%d users approved.\n", $approveStmt->affected_rows);
    }
    $approveStmt->close();
  }

  // Delete the selected users from the users table
  if (!empty($removed_ids)) {
    $id_list = implode(",", $removed_ids);

    $removeStmt = $conn->prepare("DELETE FROM users WHERE user_id IN ($id_list)");
    $removeStmt->execute();

    if ($removeStmt->error) {
      printf("Error: %s.\n", $removeStmt->error);
    } else {
      printf("%d users removed.\n", $removeStmt->affected_rows);
    }
    $removeStmt->close();
  }

  if (!empty($approved_ids) || !empty($removed_ids)) {
    // Success message
    $_SESSION['message'] = 'Action performed successfully.';
    // Redirect to the admin page
    header('Location: admin_home.php');
    exit;
  } else {
    // Error message if no users were selected
    $_SESSION['message'] = 'Please select at least one user to approve or remove.';
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<link rel="stylesheet" href="indx.css">
<link rel="stylesheet" href="aproval_pending.css">

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
<div style="display: flex; justify-content: center;"><b>List of Registration Requests</b></div>

<?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-info"><?php echo $_SESSION['message']; ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="credential" class="form-label">Select Credential:</label>
                <select id="credential" name="credential" class="form-select">
                    <option value="">All Credentials</option>
                    <option value="student">Student</option>
                    <option value="faculty">Faculty</option>
                </select>
            </div>
            <button type="credential" class="btn btn-primary">Filter</button>
        </form>

        <form method="POST" action="">
            <div class="mt-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">User ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Credential</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <th scope="row"><?php echo $user['User_id']; ?></th>
                                <td><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></td>
                                <td><?php echo $user['Email']; ?></td>
                                <td><?php echo $user['credential']; ?></td>
                                <td>
                                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_approved[]" value="<?php echo $user['User_id']; ?>" id="approve_<?php echo $user['User_id']; ?>">
                    <label class="form-check-label" for="approve_<?php echo $user['User_id']; ?>">Approve</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remove_user[]" value="<?php echo $user['User_id']; ?>" id="remove_<?php echo $user['User_id']; ?>">
                    <label class="form-check-label" for="remove_<?php echo $user['User_id']; ?>">Remove</label>
                </div>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button name="submit" class="btn btn-primary">Perform Actions</button>
            </div>
        </form>
      </div>

<script>
    // Validate that only one checkbox is selected for each user
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.form-check-input');

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                // Get the name of the clicked checkbox group (is_approved or remove_user)
                const groupName = this.name;

                // Deselect all other checkboxes in the same group
                checkboxes.forEach(function (otherCheckbox) {
                    if (otherCheckbox.name === groupName && otherCheckbox !== checkbox) {
                        otherCheckbox.checked = false;
                    }
                });
            });
        });
    });
</script>
    </body>
</html>
