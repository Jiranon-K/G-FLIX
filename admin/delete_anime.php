<?php
// delete_anime.php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit;
}
if (!isset($_GET['id'])) {
    header("Location: admin_panel.php?message=Invalid anime ID.");
    exit;
}
require_once '../db_connect.php';
$conn = connectDB();

$id = intval($_GET['id']);

$stmt = $conn->prepare("DELETE FROM animes WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: admin_panel.php?message=Anime deleted successfully.");
} else {
    header("Location: admin_panel.php?message=Failed to delete anime.");
}
$stmt->close();
$conn->close();
exit;
?>
