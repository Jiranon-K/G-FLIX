<?php
session_start();
require_once '../db_connect.php'; 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = connectDB();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password_hash, is_admin FROM users WHERE username = ?");
    $stmt->bind_param("s", $username); 
    $stmt->execute();

    $stmt->bind_result($id, $password_hash, $is_admin);
    $stmt->fetch();

    if ($id && password_verify($password, $password_hash) && $is_admin == 1) {
        $_SESSION['admin'] = $id;
        header("Location: admin_panel.php");
        exit;
    } else {
        header("Location: admin_login.php?login_error=true");  // Redirect back with error flag
        exit;
    }

    $stmt->close();
    $conn->close();
}
?>