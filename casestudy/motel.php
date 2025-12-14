<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('ID phòng trọ không hợp lệ.');
}

$stmt = $conn->prepare("SELECT m.*, d.Name as DistrictName, u.Name as PosterName, u.Phone as PosterPhone, u.Email as PosterEmail
                        FROM MOTEL m
                        LEFT JOIN DISTRICTS d ON m.district_id = d.ID
                        LEFT JOIN `USER` u ON m.user_id = u.ID
                        WHERE m.ID = ?");
if (!$stmt) { die('Query error: ' . $conn->error); }
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) { die('Không tìm thấy phòng trọ.'); }
$row = $res->fetch_assoc();
$stmt->close();

$conn->query("UPDATE MOTEL SET count_view = count_view + 1 WHERE ID = " . $row['ID']);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($row['title']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .container { max-width: 900px; margin:auto; }
        .gallery img { max-width:100%; height:auto; }
        .info { border:1px solid #ddd; padding:12px; margin-top:12px; }
        .contact { background:#f9f9f9; padding:10px; border-radius:4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($row['title']); ?></h1>
        <div style="display:flex; gap:20px;">
            <div style="flex:2">
                <div class="gallery">
                    <?php
                        $img = '';
                        if (!empty($row['images'])) {
                            $parts = explode(',', $row['images']);
                            $img = trim($parts[0]);
                        }
                        if ($img) {
                            $imgPath = __DIR__ . '/image/' . $img;
                            if (file_exists($imgPath)) {
                                echo '<img src="image/' . htmlspecialchars($img) . '" alt="">';
                            } else {
                                echo '<img src="https://via.placeholder.com/800x400.png?text=No+Image" alt="">';
                            }
                        } else {
                            echo '<img src="https://via.placeholder.com/800x400.png?text=No+Image" alt="">';
                        }
                    ?>
                </div>
                <div class="info">
                    <p><strong>Giá:</strong> <?php echo number_format($row['price']); ?> VNĐ</p>
                    <p><strong>Diện tích:</strong> <?php echo $row['area']; ?> m²</p>
                    <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($row['address']); ?> (<?php echo htmlspecialchars($row['DistrictName']); ?>)</p>
                    <p><strong>Tiện ích:</strong> <?php echo htmlspecialchars($row['utilities']); ?></p>
                    <p><strong>Mô tả:</strong><br><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                </div>
            </div>
            <div style="flex:1">
                <div class="contact">
                    <h3>Thông tin người đăng</h3>
                    <p><strong><?php echo htmlspecialchars($row['PosterName']); ?></strong></p>
                    <?php if (!empty($row['PosterPhone'])): ?><p>Điện thoại: <?php echo htmlspecialchars($row['PosterPhone']); ?></p><?php endif; ?>
                    <?php if (!empty($row['PosterEmail'])): ?><p>Email: <?php echo htmlspecialchars($row['PosterEmail']); ?></p><?php endif; ?>
                    <p><strong>Lượt xem:</strong> <?php echo (int)$row['count_view']; ?></p>
                </div>
            </div>
        </div>
        <p><a href="index.php">&larr; Quay lại danh sách</a></p>
    </div>
</body>
</html>
