<?php
// add_user.php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db_connect.php';
$conn = connectDB();

$username = $email = $password_hash = $is_admin = "";
$username_err = $email_err = $password_err = $is_admin_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Assign and validate email
    $email = trim($_POST["email"]);
    if (empty($email)) {
        $email_err = "Please enter an email.";
    } elseif (strpos($email, '@') === false) {
        $email_err = "Email must contain an '@' symbol.";
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } else {
        // Use $password_hash instead of $password
        $password_hash = password_hash(trim($_POST["password"]), PASSWORD_DEFAULT); // Hash password
    }

    // Validate is_admin
    if (!isset($_POST["is_admin"])) {
        $is_admin_err = "Please select admin status.";
    } else {
        $is_admin = $_POST["is_admin"] ? 1 : 0;
    }

    // Check for errors before inserting into the database
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($is_admin_err)) {
        // Corrected SQL statement with backticks around `password_hash`
        $stmt = $conn->prepare("INSERT INTO users (username, email, `password_hash`, is_admin) VALUES (?, ?, ?, ?)");

        // Check if the statement was prepared successfully
        if (!$stmt) {
            echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
            exit();
        }

        // Update bind_param to use $password_hash
        $stmt->bind_param("sssi", $username, $email, $password_hash, $is_admin);

        if ($stmt->execute()) {
            $message = "User added successfully.";
            header("Location: admin_panel.php?message=" . urlencode($message));
            exit;
        }
        $stmt->close();
    }

    $conn->close();
}
?>

<?php if (isset($_GET['message'])): ?>
    <script>
        showToast("<?= addslashes($_GET['message']); ?>", 'success');
    </script>
<?php endif; ?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add New User</title>
    <link rel="stylesheet" href="admin_style.css?v=<?= time(); ?>">
    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon">
</head>

<body>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <h2>Admin Panel</h2>
        </div>
        <ul>
            <li><a href="#dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="#anime-section"><i class="fas fa-film"></i> Anime Management</a></li>
            <li><a href="#user-section"><i class="fas fa-users"></i> User Management</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <h2>Add New User</h2>
        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="admin-form">
            <!-- Form fields -->
            <!-- Username -->
            <div class="form-group">
                <label>Username <span class="required">*</span></label>
                <input type="text" name="username" value="<?= htmlspecialchars($username); ?>">
                <span class="error"><?= $username_err; ?></span>
            </div>
            <!-- Email -->
            <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input type="text" name="email" value="<?= htmlspecialchars($email); ?>">
                <span class="error"><?= $email_err; ?></span>
            </div>
            <!-- Password -->
            <div class="form-group">
                <label>Password <span class="required">*</span></label>
                <input type="password" name="password" value="" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                <span class="error"><?= $password_err; ?></span>
            </div>
            <!-- Admin Status -->
            <div class="form-group">
                <label>Admin Status <span class="required">*</span></label>
                <select name="is_admin">
                    <option value="0">Regular User</option>
                    <option value="1">Admin</option>
                </select>
                <span class="error"><?= $is_admin_err; ?></span>
            </div>
            <!-- Submit Button -->
            <div class="form-group">
                <input type="submit" class="btn" value="Add User">
                <a href="admin_panel.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    </div>
</body>

</html>