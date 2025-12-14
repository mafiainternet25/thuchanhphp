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

// Handle actions: toggle approve, delete
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'toggle') {
        $stmt = $conn->prepare('UPDATE MOTEL SET approve = 1 - approve WHERE ID = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        header('Location: admin_motels.php'); exit();
    }
    if ($_GET['action'] === 'delete') {
        $stmt = $conn->prepare('DELETE FROM MOTEL WHERE ID = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        header('Location: admin_motels.php'); exit();
    }
}

$res = $conn->query('SELECT m.*, u.Name as PosterName, d.Name as DistrictName FROM MOTEL m LEFT JOIN `USER` u ON m.user_id = u.ID LEFT JOIN DISTRICTS d ON m.district_id = d.ID ORDER BY m.ID DESC');

?>
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="utf-8"><title>Quản lý tin đăng</title>
<style>body{font-family:Arial;padding:18px} table{width:100%;border-collapse:collapse} th,td{border:1px solid #ddd;padding:8px} th{background:#007bff;color:#fff}</style>
</head>
<body>
    <h2>Quản lý tin đăng</h2>
    <p><a href="admin.php">&larr; Quay lại</a></p>
    <table>
        <thead>
            <tr><th>ID</th><th>Tiêu đề</th><th>Người đăng</th><th>Quận</th><th>Giá</th><th>Trạng thái</th><th>Hành động</th></tr>
        </thead>
        <tbody>
        <?php if ($res && $res->num_rows>0): while($r=$res->fetch_assoc()): ?>
            <tr>
                <td><?php echo $r['ID']; ?></td>
                <td><?php echo htmlspecialchars($r['title']); ?></td>
                <td><?php echo htmlspecialchars($r['PosterName']?:''); ?></td>
                <td><?php echo htmlspecialchars($r['DistrictName']?:''); ?></td>
                <td><?php echo number_format($r['price']); ?></td>
                <td><?php echo $r['approve']?'<strong style="color:green">Đã duyệt</strong>':'<span style="color:orange">Chưa duyệt</span>'; ?></td>
                <td>
                    <a href="motel.php?id=<?php echo $r['ID']; ?>" target="_blank">Xem</a> |
                    <a href="admin_motels.php?action=toggle&id=<?php echo $r['ID']; ?>">Duyệt/Ẩn</a> |
                    <a href="admin_motels.php?action=delete&id=<?php echo $r['ID']; ?>" onclick="return confirm('Xóa tin đăng này?')">Xóa</a>
                </td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="7">Chưa có tin đăng</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
