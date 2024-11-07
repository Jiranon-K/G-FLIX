<?php
// whitelist.php
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

// ตรวจสอบว่ามีการส่ง anime_id และ action มาหรือไม่
if (isset($_POST['anime_id']) && isset($_POST['action'])) {
    $anime_id = intval($_POST['anime_id']);
    $action = $_POST['action'];

    $conn = connectDB();

    if ($action === 'add') {
        // ตรวจสอบว่าอนิเมะยังไม่อยู่ใน whitelist ของผู้ใช้
        $stmt = $conn->prepare("SELECT id FROM whitelist WHERE user_id = ? AND anime_id = ?");
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param("ii", $user_id, $anime_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            // เพิ่มอนิเมะเข้า whitelist
            $stmt_insert = $conn->prepare("INSERT INTO whitelist (user_id, anime_id, created_at) VALUES (?, ?, NOW())");
            if (!$stmt_insert) {
                echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
                exit();
            }
            $stmt_insert->bind_param("ii", $user_id, $anime_id);
            if ($stmt_insert->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Added to whitelist']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to add to whitelist']);
            }
            $stmt_insert->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Already in whitelist']);
        }

        $stmt->close();
    } elseif ($action === 'remove') {
        // ลบอนิเมะออกจาก whitelist
        $stmt_delete = $conn->prepare("DELETE FROM whitelist WHERE user_id = ? AND anime_id = ?");
        if (!$stmt_delete) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
            exit();
        }
        $stmt_delete->bind_param("ii", $user_id, $anime_id);
        if ($stmt_delete->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Removed from whitelist']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to remove from whitelist']);
        }
        $stmt_delete->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
}
?>
