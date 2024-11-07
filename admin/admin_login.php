<?php
session_start();
if (isset($_SESSION['admin'])) {
    header("Location: admin_panel.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login.css?v=<?= time(); ?>">
    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">
    <title>Admin Login</title>
</head>

<body>
    <div class="form-container">
        <h2>Admin Login</h2>
        <form action="admin_login_handler.php" method="POST">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="btn">Login</button>
        </form>
    </div>

    <!-- Custom Modal for login error -->
    <div id="errorModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModal">&times;</span>
            <p>Invalid username or password. Please try again.</p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        // Show custom modal if login_error is true
        <?php if (isset($_GET['login_error']) && $_GET['login_error'] == 'true'): ?>
            $(document).ready(function() {
                document.getElementById('errorModal').style.display = 'block';
            });
        <?php endif; ?>

        // Close modal when user clicks on 'x' or outside the modal
        document.getElementById('closeModal').onclick = function() {
            document.getElementById('errorModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('errorModal')) {
                document.getElementById('errorModal').style.display = 'none';
            }
        }
    </script>
</body>

</html>