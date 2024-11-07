<?php
// register_handler.php
session_start();
include '../db_connect.php';

$conn = connectDB();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าจากฟอร์ม
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // ตรวจสอบว่ารหัสผ่านตรงกันหรือไม่
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน";
        header("Location: register.php");
        exit();
    }

    // ตรวจสอบว่าชื่อผู้ใช้หรืออีเมลมีอยู่แล้วหรือไม่
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = "ชื่อผู้ใช้หรืออีเมลนี้มีอยู่แล้ว กรุณาเลือกชื่อผู้ใช้หรืออีเมลอื่น";
        $stmt->close();
        header("Location: register.php");
        exit();
    }
    $stmt->close();

    // แฮชรหัสผ่าน
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // สร้างผู้ใช้ใหม่ในฐานข้อมูลโดยยังไม่กำหนดรูปโปรไฟล์
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password_hash);

    if ($stmt->execute()) {
        // สมัครสมาชิกสำเร็จ
        $user_id = $conn->insert_id;
        // ไม่ต้องตั้งค่า $_SESSION['user_id'] และ $_SESSION['username']

        // สร้างชื่อไฟล์รูปโปรไฟล์ใหม่
        $extension = 'png'; // นามสกุลไฟล์ (ปรับตามไฟล์ default ของคุณ)
        $new_file_name = 'profile_' . $user_id . '_' . time() . '.' . $extension;
        $destination = '../img/' . $new_file_name;

        // คัดลอกรูป default.png ไปยังไฟล์ใหม่
        if (copy('../img/default.png', $destination)) {
            // อัปเดตเส้นทางรูปโปรไฟล์ในฐานข้อมูล
            $profile_image_path = 'img/' . $new_file_name;
            $stmt_update = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt_update->bind_param("si", $profile_image_path, $user_id);
            $stmt_update->execute();
            $stmt_update->close();
        } else {
            // หากการคัดลอกไฟล์ล้มเหลว จัดการข้อผิดพลาด
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการตั้งค่ารูปโปรไฟล์เริ่มต้น";
            header("Location: register.php");
            exit();
        }

        // ปิดการเชื่อมต่อฐานข้อมูล
        $stmt->close();
        $conn->close();

        // ตั้งค่า $_SESSION['success'] เพื่อแจ้งผู้ใช้ว่าการสมัครสมาชิกสำเร็จแล้ว
        $_SESSION['success'] = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
        header("Location: login.php");
        exit();
    } else {
        // เกิดข้อผิดพลาดในการสมัครสมาชิก
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการสมัครสมาชิก กรุณาลองใหม่ภายหลัง";
        $stmt->close();
        $conn->close();
        header("Location: register.php");
        exit();
    }
} else {
    // ถ้าไม่ได้ส่งข้อมูลผ่าน POST
    header("Location: register.php");
    exit();
}
?>
