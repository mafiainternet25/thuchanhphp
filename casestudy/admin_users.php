<?php
session_start();
require_once __DIR__ . '/config.php';

// Admin only
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
if (($_SESSION['user_role'] ?? 0) != 1) {
    echo '<p style="font-family:Arial;padding:20px;">Bạn không có quyền truy cập trang quản trị. <a href="login.php">Đăng nhập</a> | <a href="index.php">Về trang chính</a></p>';
    exit();
}

// Actions: delete
if (isset($_GET['action']) && $_GET['action']=='delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare('DELETE FROM `USER` WHERE ID = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    header('Location: admin_users.php'); exit();
}

$res = $conn->query('SELECT ID, Name, Username, Email, Role, Phone FROM `USER` ORDER BY ID');

?>
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="utf-8"><title>Quản lý người dùng</title>
<style>body{font-family:Arial;padding:18px} table{width:100%;border-collapse:collapse} th,td{border:1px solid #ddd;padding:8px} th{background:#007bff;color:#fff}</style>
</head>
<body>
    <h2>Quản lý người dùng</h2>
    <p><a href="admin.php">&larr; Quay lại</a> | <a href="register.php">Thêm người dùng</a></p>
    <table>
        <thead><tr><th>ID</th><th>Họ tên</th><th>Username</th><th>Email</th><th>Phone</th><th>Role</th><th>Hành động</th></tr></thead>
        <tbody>
            <?php if ($res && $res->num_rows>0): while($u=$res->fetch_assoc()): ?>
            <tr>
                <td><?php echo $u['ID']; ?></td>
                <td><?php echo htmlspecialchars($u['Name']); ?></td>
                <td><?php echo htmlspecialchars($u['Username']); ?></td>
                <td><?php echo htmlspecialchars($u['Email']); ?></td>
                <td><?php echo htmlspecialchars($u['Phone']); ?></td>
                <td><?php echo $u['Role']==1? 'Admin':'User'; ?></td>
                <td>
                    <a href="admin_users.php?action=delete&id=<?php echo $u['ID']; ?>" onclick="return confirm('Xóa tài khoản?')">Xóa</a>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="7">Chưa có người dùng</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
