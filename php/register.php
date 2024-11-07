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
    <title>สมัครสมาชิก - G-FLIX</title>
    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo filemtime('../css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- วิดีโอพื้นหลัง -->
    <video id="background-video" autoplay muted loop>
        <source src="../img/bg5.mp4" type="video/mp4">
    </video>

    <!-- Layer เพื่อทำพื้นหลังมืด -->
    <div class="overlay"></div>

    <!-- ฟอร์มสมัครสมาชิก -->
    <div class="form-container register-container">
        <h2>สมัครสมาชิก</h2>

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

        <form action="register_handler.php" method="POST">
            <label for="username">ชื่อผู้ใช้:</label>
            <input type="text" id="username" name="username" placeholder="กรอกชื่อผู้ใช้ของคุณ" required>

            <label for="email">อีเมล:</label>
            <input type="email" id="email" name="email" placeholder="กรอกอีเมลของคุณ" required>

            <label for="password">รหัสผ่าน:</label>
            <input type="password" id="password" name="password" placeholder="กรอกรหัสผ่านของคุณ" required>

            <label for="confirm_password">ยืนยันรหัสผ่าน:</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="ยืนยันรหัสผ่านของคุณ" required>

            <button type="submit" class="btn signup-button">
                <i class="fas fa-user-plus"></i> สมัครสมาชิก
            </button>
        </form>
        <p>มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
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
