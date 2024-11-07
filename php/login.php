<?php

session_start();


if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - G-FLIX</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo filemtime('../css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon">
</head>

<body>
   
    <video id="background-video" autoplay muted loop>
        <source src="../img/bg5.mp4" type="video/mp4">
    </video>

  
    <div class="overlay"></div>

  
    <div class="form-container login-container">
        <h2>เข้าสู่ระบบ</h2>

        <?php
        if (isset($_SESSION['error'])) {
            echo '<p class="error-message">' . htmlspecialchars($_SESSION['error']) . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p class="success-message">' . htmlspecialchars($_SESSION['success']) . '</p>';
            unset($_SESSION['success']);
        }
        ?>

        <form action="login_handler.php" method="POST">
            <label for="email">อีเมล:</label>
            <input type="email" id="email" name="email" placeholder="กรอกอีเมลของคุณ" required>

            <label for="password">รหัสผ่าน:</label>
            <input type="password" id="password" name="password" placeholder="กรอกรหัสผ่านของคุณ" required>

            <button type="submit" class="btn login-button">
                <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
            </button>
        </form>
        <p>ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></p>
        <!-- <span><a href="../admin/admin_login.php">ADMIN PANEL</a></span> -->
    </div>

    
    <script>
        const hamburger = document.querySelector('.hamburger');
        const navLinks = document.querySelector('.nav-links');

        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    </script>
</body>

</html>
