<?php

session_start();
include '../db_connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

$user_id = $_SESSION['user_id'];

// ตรวจสอบว่า anime_id ถูกส่งมาและเป็นจำนวนเต็มที่ถูกต้องหรือไม่
if (isset($_POST['anime_id']) && filter_var($_POST['anime_id'], FILTER_VALIDATE_INT)) {
    $anime_id = intval($_POST['anime_id']);

    $conn = connectDB();

    // ตรวจสอบว่า anime_id นั้นมีอยู่ในตาราง animes จริงหรือไม่
    $anime_check_stmt = $conn->prepare("SELECT id FROM animes WHERE id = ?");
    if ($anime_check_stmt) {
        $anime_check_stmt->bind_param("i", $anime_id);
        $anime_check_stmt->execute();
        $anime_check_stmt->store_result();

        if ($anime_check_stmt->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid anime ID']);
            $anime_check_stmt->close();
            $conn->close();
            exit();
        }

        $anime_check_stmt->close();
    }

    // ตรวจสอบว่าผู้ใช้นี้มีอนิเมะอยู่ใน whitelist หรือไม่
    $stmt = $conn->prepare("SELECT id FROM whitelist WHERE user_id = ? AND anime_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $anime_id);
        $stmt->execute();
        $stmt->store_result();

        $is_whitelisted = $stmt->num_rows > 0;

        $stmt->close();
        $conn->close();

        echo json_encode(['status' => 'success', 'whitelisted' => $is_whitelisted]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
}

?>
