<?php
session_start();
require_once __DIR__ . '/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy ID phòng trọ
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: my_rooms.php");
    exit();
}

// Kiểm tra phòng trọ có thuộc về user này không
$stmt = $conn->prepare("SELECT images FROM MOTEL WHERE ID = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$motel = $result->fetch_assoc();
$stmt->close();

if (!$motel) {
    header("Location: my_rooms.php");
    exit();
}

// Xóa ảnh nếu có
if (!empty($motel['images'])) {
    $image_path = __DIR__ . '/image/' . $motel['images'];
    if (file_exists($image_path)) {
        @unlink($image_path);
    }
}

// Xóa phòng trọ khỏi database
$stmt = $conn->prepare("DELETE FROM MOTEL WHERE ID = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$stmt->close();

// Chuyển hướng về trang quản lý với thông báo
header("Location: my_rooms.php?msg=deleted");
exit();

