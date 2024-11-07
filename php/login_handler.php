<?php
// login_handler.php

// เริ่ม session
session_start();


include '../db_connect.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  
    $email = trim($_POST['email']);
    $password = $_POST['password'];

   
    $conn = connectDB();


    $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

   
    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $username, $password_hash);
        $stmt->fetch();

      
        if (password_verify($password, $password_hash)) {
          
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;

          
            header("Location: ../index.php");
            exit();
        } else {
            $_SESSION['error'] = "รหัสผ่านไม่ถูกต้อง";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "ไม่พบผู้ใช้ที่มีอีเมลนี้";
        header("Location: login.php");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: login.php");
    exit();
}
?>
