<?php
// remove_from_whitelist.php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo 'error';
    exit();
}

$user_id = $_SESSION['user_id'];
$anime_id = $_POST['anime_id'];

$conn = connectDB();

$stmt = $conn->prepare("DELETE FROM whitelist WHERE user_id = ? AND anime_id = ?");
$stmt->bind_param("ii", $user_id, $anime_id);

if ($stmt->execute()) {
    echo 'success';
} else {
    echo 'error';
}

$stmt->close();
$conn->close();
?>
