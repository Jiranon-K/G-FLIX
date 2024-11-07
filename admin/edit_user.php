<?php
// edit_user.php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit;
}

require_once '../db_connect.php';
$conn = connectDB();

// Get user ID from GET or POST
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
} else {
    $user_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
}

// Redirect if user ID is invalid
if ($user_id <= 0) {
    header("Location: admin_panel.php?message=Invalid user ID.");
    exit;
}

$username = $email = $is_admin = "";
$username_err = $email_err = $is_admin_err = "";

// Fetch user data if GET request
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $stmt = $conn->prepare("SELECT username, email, is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if (!$stmt->execute()) {
        echo "Error fetching user data: (" . $stmt->errno . ") " . $stmt->error;
        exit;
    }

    $stmt->bind_result($username, $email, $is_admin);
    if (!$stmt->fetch()) {
        // User not found
        header("Location: admin_panel.php?message=User not found.");
        exit;
    }
    $stmt->close();
} else {
    // Update user information
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $is_admin = isset($_POST["is_admin"]) ? intval($_POST["is_admin"]) : 0; 

    // Validate username
    if (empty($username)) {
        $username_err = "Please enter a username.";
    }

    // Validate email
    if (empty($email)) {
        $email_err = "Please enter an email.";
    } elseif (strpos($email, '@') === false) {
        $email_err = "Email must contain an '@' symbol.";
    }

    // If no errors, update the user data
    if (empty($username_err) && empty($email_err)) {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, is_admin = ? WHERE id = ?");
        if (!$stmt) {
            echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
            exit;
        }

        $stmt->bind_param("ssii", $username, $email, $is_admin, $user_id);

        if ($stmt->execute()) {
            header("Location: admin_panel.php?message=User updated successfully.");
            exit;
        } else {
            echo "Something went wrong while updating the user. Please try again.";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="admin_style.css?v=<?= time(); ?>">
    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon">
</head>

<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
            </div>
            <ul>
                <li><a href="admin_panel.php#dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="admin_panel.php#anime-section"><i class="fas fa-film"></i> Anime Management</a></li>
                <li><a href="admin_panel.php#user-section"><i class="fas fa-users"></i> User Management</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <h2>Edit User</h2>
            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="admin-form">
                <!-- Hidden input to store user ID -->
                <input type="hidden" name="id" value="<?= $user_id; ?>">

                <div class="form-group">
                    <label>Username <span class="required">*</span></label>
                    <input type="text" name="username" value="<?= htmlspecialchars($username); ?>">
                    <span class="error"><?= $username_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="text" name="email" value="<?= htmlspecialchars($email); ?>">
                    <span class="error"><?= $email_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Admin Status</label>
                    <select name="is_admin">
                        <option value="0" <?= ($is_admin == 0) ? 'selected' : ''; ?>>Regular User</option>
                        <option value="1" <?= ($is_admin == 1) ? 'selected' : ''; ?>>Admin</option>
                    </select>
                    <span class="error"><?= $is_admin_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn" value="Update User">
                    <a href="admin_panel.php#user-section" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Include Font Awesome CDN for icons (if not already included) -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>



</body>

</html>