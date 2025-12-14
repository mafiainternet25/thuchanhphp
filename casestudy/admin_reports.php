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

// Handle search filters
$user = isset($_GET['user']) ? trim($_GET['user']) : '';
$from = isset($_GET['from']) ? $_GET['from'] : '';
$to = isset($_GET['to']) ? $_GET['to'] : '';
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 0;

$where = [];
$params = [];
$types = '';

if ($user !== '') { $where[] = '(u.Username LIKE ? OR u.Name LIKE ?)'; $types .= 'ss'; $params[] = "%$user%"; $params[] = "%$user%"; }
if ($from !== '') { $where[] = 'm.created_at >= ?'; $types .= 's'; $params[] = $from . ' 00:00:00'; }
if ($to !== '') { $where[] = 'm.created_at <= ?'; $types .= 's'; $params[] = $to . ' 23:59:59'; }
if ($min_price > 0) { $where[] = 'm.price >= ?'; $types .= 'i'; $params[] = $min_price; }
if ($max_price > 0) { $where[] = 'm.price <= ?'; $types .= 'i'; $params[] = $max_price; }

$sql = 'SELECT m.ID, m.title, m.price, m.created_at, u.Username FROM MOTEL m LEFT JOIN `USER` u ON m.user_id = u.ID';
if (count($where)>0) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY m.created_at DESC LIMIT 500';

$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($types!=='') {
        $bind = [$types];
        foreach($params as $k=>$v) $bind[] = & $params[$k];
        call_user_func_array([$stmt,'bind_param'],$bind);
    }
    $stmt->execute();
    $res = $stmt->get_result();
} else { $res = false; }

// Count per month (simple)
$stats = [];
$statRes = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt FROM MOTEL GROUP BY ym ORDER BY ym DESC LIMIT 12");
if ($statRes) { while ($r = $statRes->fetch_assoc()) $stats[] = $r; }

?>
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="utf-8"><title>Báo cáo</title>
<style>body{font-family:Arial;padding:18px} table{width:100%;border-collapse:collapse} th,td{border:1px solid #ddd;padding:8px} th{background:#007bff;color:#fff} form input, form select {padding:6px;margin-right:6px}</style>
</head>
<body>
    <h2>Báo cáo & Thống kê</h2>
    <p><a href="admin.php">&larr; Quay lại</a></p>

    <h3>Tìm kiếm tin đăng</h3>
    <form method="GET" action="admin_reports.php">
        Người đăng (username/tên): <input name="user" value="<?php echo htmlspecialchars($user); ?>">
        Từ: <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>">
        Đến: <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>">
        Giá từ: <input type="number" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>"> đến <input type="number" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>">
        <button type="submit">Tìm</button>
    </form>

    <h4>Kết quả tìm kiếm</h4>
    <table>
        <thead><tr><th>ID</th><th>Tiêu đề</th><th>Giá</th><th>Ngày đăng</th><th>Người đăng</th></tr></thead>
        <tbody>
        <?php if ($res && $res->num_rows>0): while($r=$res->fetch_assoc()): ?>
            <tr>
                <td><?php echo $r['ID']; ?></td>
                <td><?php echo htmlspecialchars($r['title']); ?></td>
                <td><?php echo number_format($r['price']); ?></td>
                <td><?php echo $r['created_at']; ?></td>
                <td><?php echo htmlspecialchars($r['Username']); ?></td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="5">Không có kết quả</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <h3>Thống kê số tin đăng theo tháng (12 tháng gần nhất)</h3>
    <table>
        <thead><tr><th>Tháng</th><th>Số tin</th></tr></thead>
        <tbody>
            <?php foreach($stats as $s): ?>
                <tr><td><?php echo $s['ym']; ?></td><td><?php echo $s['cnt']; ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
