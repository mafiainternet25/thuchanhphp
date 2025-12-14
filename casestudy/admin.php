<?php
session_start();
require_once __DIR__ . '/config.php';

// Only admin (Role == 1) can access
if (!isset($_SESSION['user_id'])) {
    // not logged in -> send to login
    header('Location: login.php');
    exit();
}
if (($_SESSION['user_role'] ?? 0) != 1) {
    // show informative message instead of silent redirect
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head><meta charset="utf-8"><title>Truy cập bị từ chối</title></head>
    <body style="font-family:Arial;padding:20px;">
        <h2>Truy cập bị từ chối</h2>
        <p>Bạn không có quyền truy cập trang quản trị. Vui lòng đăng nhập bằng tài khoản Admin.</p>
        <p><a href="login.php">Đăng nhập</a> &nbsp;|&nbsp; <a href="index.php">Về trang chính</a></p>
    </body>
    </html>
    <?php
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Trang quản trị</title>
    <style>
        body{font-family:Arial;padding:20px}
        .nav a{display:inline-block;margin-right:12px;padding:8px 12px;background:#007bff;color:#fff;text-decoration:none;border-radius:4px}
    </style>
</head>
<body>
    <h1>Quản trị hệ thống</h1>
    <div class="nav">
        <a href="admin_motels.php">Quản lý tin đăng</a>
        <a href="admin_users.php">Quản lý người dùng</a>
        <a href="admin_reports.php">Báo cáo & Thống kê</a>
        <a href="index.php">Về trang chính</a>
    </div>

    <p>Xin chào, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> (Admin)</p>

</body>
</html>
