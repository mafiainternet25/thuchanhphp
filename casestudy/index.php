<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 0;
$district = isset($_GET['district']) ? (int)$_GET['district'] : 0;
$utility = isset($_GET['utility']) ? trim($_GET['utility']) : '';

$where = [];
$params = [];
$types = '';

if ($min_price > 0) { $where[] = 'm.price >= ?'; $types .= 'i'; $params[] = $min_price; }
if ($max_price > 0) { $where[] = 'm.price <= ?'; $types .= 'i'; $params[] = $max_price; }
if ($district > 0) { $where[] = 'm.district_id = ?'; $types .= 'i'; $params[] = $district; }
if ($utility !== '') { $where[] = 'm.utilities LIKE ?'; $types .= 's'; $params[] = '%' . $utility . '%'; }

if ($tab === 'near_vinh') {
    $where[] = '(m.address LIKE ? OR m.title LIKE ?)';
    $types .= 'ss';
    $params[] = '%Vinh%';
    $params[] = '%Vinh%';
}

if ($tab === 'most_viewed') {
    $order = 'm.count_view DESC';
} elseif ($tab === 'newest') {
    $order = 'm.created_at DESC';
} else {
    $order = 'm.ID DESC';
}

$sql = "SELECT m.*, d.Name as DistrictName, u.Name as PosterName FROM MOTEL m LEFT JOIN DISTRICTS d ON m.district_id = d.ID LEFT JOIN `USER` u ON m.user_id = u.ID";
if (count($where) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= " ORDER BY $order LIMIT 200";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($types !== '') {
        $bind_names[] = $types;
        for ($i=0;$i<count($params);$i++) {
            $bind_names[] = & $params[$i];
        }
        call_user_func_array(array($stmt,'bind_param'), $bind_names);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    die('Query error: ' . $conn->error);
}

$districts = [];
$dr = $conn->query('SELECT ID, Name FROM DISTRICTS');
if ($dr) { while ($r = $dr->fetch_assoc()) $districts[] = $r; }
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách Phòng trọ</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background:#f6f7fb; color:#222; }
        .welcome { display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; gap:12px; }
        .greeting { font-size:16px; color:#333; }
        .actions { display:flex; gap:8px; align-items:center; }
        .btn { text-decoration:none; padding:8px 14px; border-radius:20px; font-weight:600; color:#fff; transition: transform .12s ease, box-shadow .12s ease; box-shadow:0 4px 12px rgba(0,0,0,0.06); display:inline-block; }
        .btn-home { background:#dc3545; }
        .btn-manage { background:#007bff; }
        .btn-logout { background:#6c757d; }
        .btn:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,0.12); }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #007bff; color: white; }
    </style>
</head>
<body>
    <div class="welcome">
        <div class="greeting">Xin chào, <strong><?php echo $_SESSION['user_name']; ?></strong>!</div>
        <div class="actions">
            <a href="my_rooms.php" class="btn btn-manage">Phòng trọ của tôi</a>
            <a href="../casestudy/index.php" class="btn btn-home">Trang chủ</a>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1): ?>
                <a href="admin.php" class="btn btn-manage">Quản lý</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-logout">Đăng xuất</a>
        </div>
    </div>

    <h2 style="margin-top:0;">PHÒNG TRỌ MỚI ĐĂNG NHẤT</h2>

    <div class="search-row">
        <form method="GET" action="index.php">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
            <input type="number" name="min_price" placeholder="Giá từ" value="<?php echo htmlspecialchars($min_price); ?>">
            <input type="number" name="max_price" placeholder="đến" value="<?php echo htmlspecialchars($max_price); ?>">
            <select name="district">
                <option value="0">--Tất cả quận--</option>
                <?php foreach ($districts as $d): ?>
                    <option value="<?php echo $d['ID']; ?>" <?php if ($d['ID']==$district) echo 'selected'; ?>><?php echo htmlspecialchars($d['Name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="utility" placeholder="Tiện ích (ví dụ: wifi)" value="<?php echo htmlspecialchars($utility); ?>">
            <button class="btn" type="submit">Tìm kiếm</button>
        </form>
    </div>

    <div class="grid">
        <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <?php
                        $img = '';
                        if (!empty($row['images'])) {
                            $parts = explode(',', $row['images']);
                            $img = trim($parts[0]);
                        }
                        $tag = '';
                        if (!empty($row['utilities']) && stripos($row['utilities'], 'ghép') !== false) $tag = 'Ở ghép';
                        elseif (stripos($row['title'], 'Nhà cấp') !== false) $tag = 'Nhà cấp 4';
                    ?>
                    <div class="card">
                        <div class="thumb">
                                <?php if ($img):
                                    $imgPath = __DIR__ . '/image/' . $img;
                                    if (file_exists($imgPath)):
                                ?>
                                    <img src="image/<?php echo htmlspecialchars($img); ?>" alt="">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/400x260.png?text=No+Image" alt="">
                                <?php endif; else: ?>
                                    <img src="https://via.placeholder.com/400x260.png?text=No+Image" alt="">
                                <?php endif; ?>
                            <?php if ($tag): ?><div class="badge"><?php echo $tag; ?></div><?php endif; ?>
                            <div class="price-tag"><?php echo number_format($row['price']); ?> VNĐ</div>
                        </div>
                        <div class="info">
                            <h3 class="title"><a href="motel.php?id=<?php echo $row['ID']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></h3>
                            <div class="meta">Người đăng: <?php echo htmlspecialchars($row['PosterName'] ?: 'Ẩn'); ?> &nbsp;|&nbsp; <?php echo htmlspecialchars($row['DistrictName']); ?> &nbsp;|&nbsp; Diện tích: <?php echo $row['area']; ?> m²</div>
                            <p class="small-meta">Lượt xem: <?php echo (int)$row['count_view']; ?> &nbsp;•&nbsp; <?php echo date('d/m/Y', strtotime($row['created_at'])); ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
        <?php else: ?>
            <p>Chưa có phòng trọ nào.</p>
        <?php endif; ?>
    </div>

</body>
</html>

<style>
    .search-row { background:#fff; padding:12px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.05); margin-bottom:18px; }
    .search-row form { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .search-row input[type="number"], .search-row input[type="text"], .search-row select { padding:8px 10px; border:1px solid #ddd; border-radius:4px; }
    .search-row .btn{ background:#ff6b6b; border:none; color:white; padding:8px 14px; border-radius:4px; cursor:pointer; }

    .grid{ display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:18px; }
    .card{ background:white; border-radius:8px; overflow:hidden; box-shadow:0 6px 18px rgba(0,0,0,0.06); display:flex; flex-direction:column; }
    .thumb{ position:relative; height:180px; overflow:hidden; }
    .thumb img{ width:100%; height:100%; object-fit:cover; display:block; }
    .badge{ position:absolute; left:8px; top:8px; background:rgba(255,99,71,0.95); color:white; padding:4px 8px; font-size:12px; border-radius:4px; }
    .price-tag{ position:absolute; right:8px; bottom:8px; background:rgba(0,0,0,0.7); color:#fff; padding:6px 8px; border-radius:4px; font-weight:bold; font-size:13px; }
    .info{ padding:12px; }
    .title{ margin:0 0 8px 0; font-size:18px; }
    .title a{ color:#333; text-decoration:none; }
    .title a:hover{ color:#007bff; }
    .meta{ color:#777; font-size:13px; margin-bottom:6px; }
    .small-meta{ color:#999; font-size:12px; }

    @media (min-width: 1000px){ .grid{ grid-template-columns: repeat(4, 1fr); } }
</style>