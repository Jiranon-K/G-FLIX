<?php
// delete_user.php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit;
}

require_once '../db_connect.php';
$conn = connectDB();

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($user_id > 0) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        header("Location: admin_panel.php?message=User deleted successfully.");
    } else {
        header("Location: admin_panel.php?message=Failed to delete user.");
    }
    $stmt->close();
} else {
    header("Location: admin_panel.php?message=Invalid user ID.");
}

$conn->close();
?>
