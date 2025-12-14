<?php
require_once __DIR__ . '/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $re_password = isset($_POST['re_password']) ? $_POST['re_password'] : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

    if ($username === '' || $password === '' || $email === '' || $name === '' || $phone === '') {
        $error = 'Vui lòng điền đầy đủ thông tin.';
    } elseif (!preg_match('/^[0-9+\s\-]{7,20}$/', $phone)) {
        $error = 'Số điện thoại không hợp lệ.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } elseif ($password !== $re_password) {
        $error = 'Mật khẩu nhập lại không khớp!';
    } else {
        $check = $conn->prepare("SELECT `ID` FROM `USER` WHERE `Username` = ? OR `Email` = ?");
        if (!$check) {
            $error = 'Database error: ' . $conn->error;
        } else {
            $check->bind_param("ss", $username, $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = 'Tài khoản hoặc Email đã tồn tại!';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO `USER` (`Name`, `Username`, `Email`, `Password`, `Role`, `Phone`) VALUES (?, ?, ?, ?, 0, ?)");
                if (!$stmt) {
                    $error = 'Database error: ' . $conn->error;
                } else {
                    $stmt->bind_param("sssss", $name, $username, $email, $password_hash, $phone);
                    if ($stmt->execute()) {
                        header('Location: login.php?registered=1');
                        exit();
                    } else {
                        $error = 'Có lỗi xảy ra: ' . $conn->error;
                    }
                    $stmt->close();
                }
            }
            $check->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký thành viên mới</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; margin-top: 50px; }
        .container { width: 500px; border: 1px solid #ccc; }
        .header { background-color: #007bff; color: white; padding: 10px; font-weight: bold; }
        .form-group { margin: 15px; display: flex; align-items: center; }
        .form-group label { width: 150px; text-align: right; margin-right: 15px; font-weight: bold; font-size: 14px; }
        .form-group input { flex: 1; padding: 5px; }
        .btn-container { text-align: center; margin-bottom: 20px; padding-left: 165px; display: flex; }
        .btn { background-color: #007bff; color: white; border: none; padding: 8px 20px; cursor: pointer; }
        .btn:hover { background-color: #0056b3; }
        .message { text-align: center; margin: 10px; color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Đăng kí thành viên mới</div>
        
        <?php if ($error): ?> <div class="message"><?php echo $error; ?></div> <?php endif; ?>
        <?php if ($success): ?> <div class="message success"><?php echo $success; ?></div> <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Tài khoản:</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Mật khẩu:</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Nhập lại mật khẩu:</label>
                <input type="password" name="re_password" required>
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Tên hiển thị:</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Số điện thoại:</label>
                <input type="text" name="phone" required placeholder="Ví dụ: 0901234567">
            </div>
            <div class="btn-container">
                <button type="submit" class="btn">Đăng kí</button>
            </div>
        </form>
    </div>
</body>
</html>