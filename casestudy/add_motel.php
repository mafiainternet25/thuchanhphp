<?php
session_start();
require_once __DIR__ . '/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Kiểm tra và sửa AUTO_INCREMENT cho bảng MOTEL nếu cần
$check_auto = $conn->query("SHOW COLUMNS FROM MOTEL WHERE Field = 'ID' AND Extra LIKE '%auto_increment%'");
if ($check_auto && $check_auto->num_rows == 0) {
    // Thêm AUTO_INCREMENT cho cột ID
    $conn->query("ALTER TABLE MOTEL MODIFY ID int(10) NOT NULL AUTO_INCREMENT");
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Load districts
$districts = [];
$dr = $conn->query('SELECT ID, Name FROM DISTRICTS ORDER BY Name');
if ($dr) { 
    while ($r = $dr->fetch_assoc()) {
        $districts[] = $r;
    }
}

// Xử lý form submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $price = isset($_POST['price']) ? (int)$_POST['price'] : 0;
    $area = isset($_POST['area']) ? (int)$_POST['area'] : 0;
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $district_id = isset($_POST['district_id']) ? (int)$_POST['district_id'] : 0;
    $utilities = isset($_POST['utilities']) ? trim($_POST['utilities']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $latlng = isset($_POST['latlng']) ? trim($_POST['latlng']) : '';
    
    // Validation
    if (empty($title)) {
        $error = 'Vui lòng nhập tiêu đề phòng trọ.';
    } elseif ($price <= 0) {
        $error = 'Vui lòng nhập giá phòng trọ hợp lệ.';
    } elseif ($area <= 0) {
        $error = 'Vui lòng nhập diện tích hợp lệ.';
    } elseif (empty($address)) {
        $error = 'Vui lòng nhập địa chỉ.';
    } elseif ($district_id <= 0) {
        $error = 'Vui lòng chọn quận/huyện.';
    } else {
        // Xử lý upload ảnh
        $image_name = '';
        $image_dir = __DIR__ . '/image/';
        
        // Kiểm tra và tạo thư mục image nếu chưa tồn tại
        if (!is_dir($image_dir)) {
            if (!mkdir($image_dir, 0755, true)) {
                $error = 'Không thể tạo thư mục lưu ảnh.';
            }
        }
        
        // Kiểm tra quyền ghi vào thư mục
        if (empty($error) && !is_writable($image_dir)) {
            $error = 'Thư mục image không có quyền ghi. Vui lòng kiểm tra quyền thư mục.';
        }
        
        if (empty($error) && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Kiểm tra các lỗi upload
            $upload_error = $_FILES['image']['error'];
            if ($upload_error !== UPLOAD_ERR_OK) {
                switch ($upload_error) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $error = 'File ảnh quá lớn. Kích thước tối đa: ' . ini_get('upload_max_filesize');
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $error = 'File chỉ được upload một phần.';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $error = 'Thiếu thư mục tạm để upload.';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $error = 'Không thể ghi file vào đĩa.';
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $error = 'Upload bị chặn bởi extension PHP.';
                        break;
                    default:
                        $error = 'Lỗi upload không xác định.';
                }
            } else {
                // Kiểm tra định dạng file
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_ext, $allowed)) {
                    $error = 'Định dạng ảnh không hợp lệ. Chỉ chấp nhận: ' . implode(', ', $allowed);
                } else {
                    // Kiểm tra kích thước file (5MB)
                    $max_size = 5 * 1024 * 1024; // 5MB
                    if ($_FILES['image']['size'] > $max_size) {
                        $error = 'File ảnh quá lớn. Kích thước tối đa: 5MB';
                    } else {
                        // Kiểm tra file có phải là ảnh thật không
                        $image_info = @getimagesize($_FILES['image']['tmp_name']);
                        if ($image_info === false) {
                            $error = 'File không phải là ảnh hợp lệ.';
                        } else {
                            // Tạo tên file mới
                            $image_name = uniqid() . '_' . time() . '.' . $file_ext;
                            $upload_path = $image_dir . $image_name;
                            
                            // Upload file
                            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                                $error = 'Không thể upload ảnh. Vui lòng kiểm tra quyền thư mục hoặc liên hệ quản trị viên.';
                                // Debug: Kiểm tra thông tin thêm
                                if (!is_writable($image_dir)) {
                                    $error .= ' (Thư mục không có quyền ghi)';
                                } elseif (!file_exists($_FILES['image']['tmp_name'])) {
                                    $error .= ' (File tạm không tồn tại)';
                                }
                            }
                        }
                    }
                }
            }
        }
        
        if (empty($error)) {
            // Insert vào database
            $sql = "INSERT INTO MOTEL (title, description, price, area, address, latlng, images, user_id, district_id, utilities, phone, approve) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param('ssiisssiiss', $title, $description, $price, $area, $address, $latlng, $image_name, $user_id, $district_id, $utilities, $phone);
                
                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: my_rooms.php?msg=added");
                    exit();
                } else {
                    $error = 'Lỗi khi thêm phòng trọ: ' . $stmt->error;
                    $stmt->close();
                }
            } else {
                $error = 'Lỗi chuẩn bị câu lệnh: ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm phòng trọ mới</title>
    <style>
        body { 
            font-family: sans-serif; 
            padding: 20px; 
            background: #f6f7fb; 
            color: #222; 
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        h2 {
            margin: 0;
            color: #333;
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
        .btn-secondary { background: #6c757d; }
        .btn-success { background: #28a745; }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(0,0,0,0.12); 
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="file"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        .form-group small {
            color: #666;
            font-size: 12px;
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
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        .required {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Thêm phòng trọ mới</h2>
            <a href="my_rooms.php" class="btn btn-secondary">← Quay lại</a>
        </div>

        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label>Tiêu đề <span class="required">*</span></label>
                <input type="text" name="title" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
            </div>

            <div class="form-group">
                <label>Mô tả</label>
                <textarea name="description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Giá (VNĐ) <span class="required">*</span></label>
                    <input type="number" name="price" required min="0" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Diện tích (m²) <span class="required">*</span></label>
                    <input type="number" name="area" required min="0" value="<?php echo isset($_POST['area']) ? htmlspecialchars($_POST['area']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Địa chỉ <span class="required">*</span></label>
                <input type="text" name="address" required value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
            </div>

            <div class="form-group">
                <label>Quận/Huyện <span class="required">*</span></label>
                <select name="district_id" required>
                    <option value="0">-- Chọn quận/huyện --</option>
                    <?php foreach ($districts as $d): ?>
                        <option value="<?php echo $d['ID']; ?>" <?php echo (isset($_POST['district_id']) && $_POST['district_id'] == $d['ID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['Name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Tiện ích</label>
                <input type="text" name="utilities" placeholder="Ví dụ: Điện, nước, wifi, máy lạnh" value="<?php echo isset($_POST['utilities']) ? htmlspecialchars($_POST['utilities']) : ''; ?>">
                <small>Nhập các tiện ích cách nhau bởi dấu phẩy</small>
            </div>

            <div class="form-group">
                <label>Số điện thoại</label>
                <input type="text" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>

            <div class="form-group">
                <label>Tọa độ (Lat, Lng)</label>
                <input type="text" name="latlng" placeholder="Ví dụ: 10.7769,106.7009" value="<?php echo isset($_POST['latlng']) ? htmlspecialchars($_POST['latlng']) : ''; ?>">
                <small>Để trống nếu chưa có</small>
            </div>

            <div class="form-group">
                <label>Hình ảnh</label>
                <input type="file" name="image" accept="image/*">
                <small>Chấp nhận: JPG, PNG, GIF (tối đa 5MB)</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-success">Thêm phòng trọ</button>
                <a href="my_rooms.php" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
</body>
</html>

