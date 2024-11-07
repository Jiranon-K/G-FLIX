<?php
// php/profile.php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../db_connect.php';

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
$conn = connectDB();
$stmt = $conn->prepare("SELECT username, email, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($current_username, $email, $profile_image);
$stmt->fetch();
$stmt->close();

// จัดการการเปลี่ยนชื่อผู้ใช้และรหัสผ่าน
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // เปลี่ยนชื่อผู้ใช้
    if (isset($_POST['new_username'])) {
        $new_username = trim($_POST['new_username']);

        if (empty($new_username)) {
            $_SESSION['error'] = "ชื่อผู้ใช้ใหม่ไม่สามารถว่างเปล่าได้";
            header("Location: profile.php");
            exit();
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $new_username, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $_SESSION['error'] = "ชื่อผู้ใช้ใหม่นี้มีอยู่แล้ว กรุณาเลือกชื่อผู้ใช้อื่น";
            $stmt->close();
            header("Location: profile.php");
            exit();
        }
        $stmt->close();

        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $new_username, $_SESSION['user_id']);

        if ($stmt->execute()) {
            $_SESSION['success'] = "เปลี่ยนชื่อผู้ใช้สำเร็จ!";
            $_SESSION['username'] = $new_username;
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการเปลี่ยนชื่อผู้ใช้ กรุณาลองใหม่ภายหลัง";
        }

        $stmt->close();
    }

    // เปลี่ยนรหัสผ่าน
    if (isset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // ตรวจสอบรหัสผ่านปัจจุบัน
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($password_hash);
        $stmt->fetch();
        $stmt->close();

        if (!password_verify($current_password, $password_hash)) {
            $_SESSION['error'] = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
            header("Location: profile.php");
            exit();
        }

        // ตรวจสอบรหัสผ่านใหม่และการยืนยัน
        if ($new_password !== $confirm_password) {
            $_SESSION['error'] = "รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน";
            header("Location: profile.php");
            exit();
        }

        // อัปเดตรหัสผ่านใหม่
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password_hash, $_SESSION['user_id']);

        if ($stmt->execute()) {
            $_SESSION['success'] = "เปลี่ยนรหัสผ่านสำเร็จ!";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน กรุณาลองใหม่ภายหลัง";
        }

        $stmt->close();
    }

   // จัดการการอัปโหลดรูปโปรไฟล์
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
    // กำหนดโฟลเดอร์สำหรับเก็บรูปโปรไฟล์
    $upload_dir = '../img/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // รับข้อมูลไฟล์
    $file_tmp = $_FILES['profile_image']['tmp_name'];
    $file_name = $_FILES['profile_image']['name'];
    $file_size = $_FILES['profile_image']['size'];
    $file_type = $_FILES['profile_image']['type'];

    // ตรวจสอบขนาดไฟล์ (ไม่เกิน 5MB)
    $max_file_size = 5 * 1024 * 1024; // 5MB
    if ($file_size > $max_file_size) {
        $_SESSION['error'] = "ขนาดไฟล์ต้องไม่เกิน 5MB";
        header("Location: profile.php");
        exit();
    }

    // ตรวจสอบชนิดของไฟล์ (อนุญาตเฉพาะรูปภาพ)
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $detected_type = mime_content_type($file_tmp);

    if (!in_array($detected_type, $allowed_types)) {
        $_SESSION['error'] = "รูปภาพต้องเป็นไฟล์ JPEG, PNG หรือ GIF เท่านั้น";
        header("Location: profile.php");
        exit();
    }

    // สร้างชื่อไฟล์ใหม่เพื่อหลีกเลี่ยงการชนกัน
    $extension = pathinfo($file_name, PATHINFO_EXTENSION);
    $new_file_name = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;

    // ย้ายไฟล์ที่อัปโหลดไปยังโฟลเดอร์เป้าหมาย
    $destination = $upload_dir . $new_file_name;
    if (move_uploaded_file($file_tmp, $destination)) {
        // ลบรูปโปรไฟล์เก่า (ถ้ามี)
        $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($old_profile_image);
        $stmt->fetch();
        $stmt->close();

        if (!empty($old_profile_image) && $old_profile_image !== 'img/default.png') {
            $old_image_path = '../' . $old_profile_image;
            if (file_exists($old_image_path)) {
                unlink($old_image_path);
            }
        }

        // อัปเดตตาราง users ด้วยเส้นทางรูปโปรไฟล์ใหม่
        $profile_image_path = 'img/' . $new_file_name;

        $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
        $stmt->bind_param("si", $profile_image_path, $_SESSION['user_id']);

        if ($stmt->execute()) {
            $_SESSION['success'] = "อัปเดตรูปโปรไฟล์สำเร็จ!";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดตรูปโปรไฟล์ กรุณาลองใหม่ภายหลัง";
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
        header("Location: profile.php");
        exit();
    }
}




    header("Location: profile.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลส่วนตัว - G-FLIX</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo filemtime('../css/style.css'); ?>">
    <link rel="shortcut icon" href="../img/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="pl">
    <!-- Navbar -->
    <nav class="navbar">
        <a href="../index.php">
            <!-- Logo -->
            <div class="logo">
                G-FLIX
            </div>
        </a>

        <!-- Hamburger icon สำหรับมือถือ -->
        <div class="hamburger">
            <i class="fa-solid fa-bars"></i>
        </div>

        <!-- ปุ่มเข้าสู่ระบบและสมัครสมาชิก หรือ แสดงชื่อผู้ใช้และไอคอนผู้ใช้ -->
        <div class="nav-links">
            <?php
            if (isset($_SESSION['user_id'])) {



                $stmt_nav = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
                $stmt_nav->bind_param("i", $_SESSION['user_id']);
                $stmt_nav->execute();
                $stmt_nav->bind_result($navbar_profile_image);
                $stmt_nav->fetch();
                $stmt_nav->close();

                echo '
            <a href="profile.php" class="user-info">
                <span class="username">' . htmlspecialchars($_SESSION['username']) . '</span>';
                if (!empty($navbar_profile_image)) {
                    echo '<img src="../' . htmlspecialchars($navbar_profile_image) . '" alt="Profile Image" class="profile-image">';
                } else {
                    echo '<i class="fas fa-user-circle user-icon"></i>';
                }
                echo '</a>
            <a href="all_anime.php" class="nav-button all-anime-button">
                <i class="fas fa-film"></i> All Anime
            </a>
            <a href="mywhitelist.php" class="nav-button mywhitelist-button">
                <i class="fas fa-list"></i> My Whitelist
            </a>
            <a href="profile.php" class="nav-button profile-button">
                <i class="fas fa-user-circle"></i> Profile
            </a>
            <a href="logout.php" class="nav-button logout-button">
                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
            </a>
            ';
            } else {
                echo '
            <a href="login.php" class="nav-button login-button">
                <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
            </a>
            <a href="register.php" class="nav-button signup-button">
                <i class="fas fa-user-plus"></i> สมัครสมาชิก
            </a>
        ';
            }
            ?>
        </div>
    </nav>



    <video id="background-video" autoplay muted loop>
        <source src="../video/bg.mp4" type="video/mp4">
    </video>

    <div class="overlay"></div>

    <!-- Container สำหรับแสดงข้อความ error และ success -->
    <div class="message-container">
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="error-message">
                    <span class="close-message">&times;</span>
                    ' . htmlspecialchars($_SESSION['error']) . '
                  </div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="success-message">
                    <span class="close-message">&times;</span>
                    ' . htmlspecialchars($_SESSION['success']) . '
                  </div>';
            unset($_SESSION['success']);
        }
        ?>
    </div>

    <div class="form-page-container">

        <!-- คอนเทนเนอร์สำหรับฟอร์มเปลี่ยนชื่อผู้ใช้ -->
        <div class="form-container change-username-form">
            <h2>ข้อมูลส่วนตัว</h2>

            <div class="current-profile-image">
                <?php
                if (!empty($profile_image)) {
                    echo '<img src="../' . htmlspecialchars($profile_image) . '" alt="Profile Image" class="profile-image-large">';
                } else {
                    echo '<i class="fas fa-user-circle user-icon-large"></i>';
                }
                ?>
            </div>

            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <label for="profile_image">อัปโหลดรูปโปรไฟล์ใหม่:</label>
                <input type="file" id="profile_image" name="profile_image" accept="image/*">
                <button type="submit" class="btn update-button">
                    <i class="fas fa-upload"></i> อัปเดตรูปโปรไฟล์
                </button>
            </form>

            <form action="profile.php" method="POST">
                <label for="username">ชื่อผู้ใช้ปัจจุบัน:</label>
                <input type="text" id="current_username" name="current_username" value="<?php echo htmlspecialchars($current_username); ?>" disabled>

                <label for="new_username">ชื่อผู้ใช้ใหม่:</label>
                <input type="text" id="new_username" name="new_username" placeholder="กรอกชื่อผู้ใช้ใหม่" required>

                <button type="submit" class="btn update-button">
                    <i class="fas fa-sync-alt"></i> เปลี่ยนชื่อผู้ใช้
                </button>
            </form>
        </div>

        <!-- คอนเทนเนอร์สำหรับฟอร์มเปลี่ยนรหัสผ่าน -->
        <div class="form-container change-password-form">
            <h2>เปลี่ยนรหัสผ่าน</h2>
            <form action="profile.php" method="POST">
                <label for="current_password">รหัสผ่านปัจจุบัน:</label>
                <input type="password" id="current_password" name="current_password" placeholder="กรอกรหัสผ่านปัจจุบัน" required>

                <label for="new_password">รหัสผ่านใหม่:</label>
                <input type="password" id="new_password" name="new_password" placeholder="กรอกรหัสผ่านใหม่" required>

                <label for="confirm_password">ยืนยันรหัสผ่านใหม่:</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="ยืนยันรหัสผ่านใหม่" required>

                <button type="submit" class="btn update-button">
                    <i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน
                </button>
            </form>
        </div>
    </div>

    <?php
    $conn->close();
    ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const hamburger = document.querySelector('.hamburger');
            const navLinks = document.querySelector('.nav-links');

            hamburger.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });

            // ฟังก์ชันสำหรับปิดข้อความ error และ success เมื่อคลิกที่ปุ่มปิด
            const closeButtons = document.querySelectorAll('.close-message');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    this.parentElement.style.display = 'none';
                });
            });
        });
    </script>
</body>

</html>