<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    if ($msg === 'added') {
        $message = 'Thêm phòng trọ thành công!';
        $message_type = 'success';
    } elseif ($msg === 'updated') {
        $message = 'Cập nhật phòng trọ thành công!';
        $message_type = 'success';
    } elseif ($msg === 'deleted') {
        $message = 'Xóa phòng trọ thành công!';
        $message_type = 'success';
    }
}

$sql = "SELECT m.*, d.Name as DistrictName 
        FROM MOTEL m 
        LEFT JOIN DISTRICTS d ON m.district_id = d.ID 
        WHERE m.user_id = ? 
        ORDER BY m.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$districts = [];
$dr = $conn->query('SELECT ID, Name FROM DISTRICTS');
if ($dr) { 
    while ($r = $dr->fetch_assoc()) {
        $districts[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý phòng trọ của tôi</title>
    <style>
        body { 
            font-family: sans-serif; 
            padding: 20px; 
            background: #f6f7fb; 
            color: #222; 
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 12px;
        }
        .greeting { 
            font-size: 18px; 
            color: #333; 
        }
        .actions { 
            display: flex; 
            gap: 8px; 
            align-items: center; 
        }
        .btn { 
            text-decoration: none; 
            padding: 10px 18px; 
            border-radius: 6px; 
            font-weight: 600; 
            color: #fff; 
            transition: transform .12s ease, box-shadow .12s ease; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.06); 
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary { background: #007bff; }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
        .btn-secondary { background: #6c757d; }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(0,0,0,0.12); 
        }
        .message {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background-color: #007bff;
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .img-thumb {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .action-buttons {
            display: flex;
            gap: 6px;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .empty-state p {
            color: #666;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="greeting">
            <h2 style="margin: 0;">Quản lý phòng trọ của tôi</h2>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Xin chào, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></p>
        </div>
        <div class="actions">
            <a href="add_motel.php" class="btn btn-success">+ Thêm phòng trọ mới</a>
            <a href="index.php" class="btn btn-secondary">Trang chủ</a>
            <a href="logout.php" class="btn btn-danger">Đăng xuất</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Hình ảnh</th>
                    <th>Tiêu đề</th>
                    <th>Giá</th>
                    <th>Diện tích</th>
                    <th>Quận</th>
                    <th>Lượt xem</th>
                    <th>Ngày đăng</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php
                                $img = '';
                                if (!empty($row['images'])) {
                                    $parts = explode(',', $row['images']);
                                    $img = trim($parts[0]);
                                }
                                if ($img) {
                                    $imgPath = __DIR__ . '/image/' . $img;
                                    if (file_exists($imgPath)) {
                                        echo '<img src="image/' . htmlspecialchars($img) . '" alt="" class="img-thumb">';
                                    } else {
                                        echo '<img src="https://via.placeholder.com/80x60.png?text=No+Image" alt="" class="img-thumb">';
                                    }
                                } else {
                                    echo '<img src="https://via.placeholder.com/80x60.png?text=No+Image" alt="" class="img-thumb">';
                                }
                            ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                            <br>
                            <small style="color: #666;"><?php echo htmlspecialchars(substr($row['address'], 0, 50)); ?>...</small>
                        </td>
                        <td><?php echo number_format($row['price']); ?> VNĐ</td>
                        <td><?php echo $row['area']; ?> m²</td>
                        <td><?php echo htmlspecialchars($row['DistrictName'] ?: 'N/A'); ?></td>
                        <td><?php echo (int)$row['count_view']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <?php if ($row['approve'] == 1): ?>
                                <span style="color: #28a745; font-weight: 600;">✓ Đã duyệt</span>
                            <?php else: ?>
                                <span style="color: #ffc107; font-weight: 600;">⏳ Chờ duyệt</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="motel.php?id=<?php echo $row['ID']; ?>" class="btn btn-primary btn-small" target="_blank">Xem</a>
                                <a href="edit_motel.php?id=<?php echo $row['ID']; ?>" class="btn btn-success btn-small">Sửa</a>
                                <a href="delete_motel.php?id=<?php echo $row['ID']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Bạn có chắc chắn muốn xóa phòng trọ này?');">Xóa</a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <p>Bạn chưa đăng phòng trọ nào.</p>
            <a href="add_motel.php" class="btn btn-success">Thêm phòng trọ đầu tiên</a>
        </div>
    <?php endif; ?>
</body>
</html>

