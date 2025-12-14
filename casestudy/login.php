<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    die('Database connection not available. Check `casestudy/config.php` and mysqli extension.');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';
$success = '';
if (!isset($_SESSION['login_fail_count'])) {
    $_SESSION['login_fail_count'] = 0;
}

$captcha_threshold = 3;
if ($_SESSION['login_fail_count'] >= $captcha_threshold && !isset($_SESSION['captcha_answer'])) {
    $a = rand(1, 9);
    $b = rand(1, 9);
    $_SESSION['captcha_q'] = "$a + $b = ?";
    $_SESSION['captcha_answer'] = $a + $b;
}

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($_SESSION['login_fail_count'] >= $captcha_threshold) {
        $captcha_input = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';
        if ($captcha_input === '' || !isset($_SESSION['captcha_answer']) || intval($captcha_input) !== intval($_SESSION['captcha_answer'])) {
            $error = 'Vui lòng hoàn thành xác thực không phải là máy đúng cách.';
            $a = rand(1, 9);
            $b = rand(1, 9);
            $_SESSION['captcha_q'] = "$a + $b = ?";
            $_SESSION['captcha_answer'] = $a + $b;
        }
    }

    if ($error === '') {
        $stmt = $conn->prepare("SELECT `ID`, `Name`, `Password`, `Role` FROM `USER` WHERE `Username` = ?");
        if (!$stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $stored = $row['Password'];

                $verified = false;
                if (password_verify($password, $stored)) {
                    $verified = true;
                } elseif ($password === $stored) {
                    $verified = true;
                    $needs_rehash = true;
                }

                if ($verified) {
                    if (!empty($needs_rehash) && $needs_rehash === true) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $upd = $conn->prepare("UPDATE `USER` SET `Password` = ? WHERE `ID` = ?");
                        if ($upd) {
                            $upd->bind_param("si", $newHash, $row['ID']);
                            $upd->execute();
                            $upd->close();
                        }
                    }

                    $_SESSION['login_fail_count'] = 0;
                    unset($_SESSION['captcha_answer'], $_SESSION['captcha_q']);
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $row['ID'];
                    $_SESSION['user_name'] = $row['Name'];
                    $_SESSION['user_role'] = $row['Role'];

                    header("Location: index.php");
                    exit();
                } else {
                    $_SESSION['login_fail_count']++;
                    $error = "Mật khẩu không chính xác! (Lần thử: " . $_SESSION['login_fail_count'] . ")";
                    if ($_SESSION['login_fail_count'] >= $captcha_threshold) {
                        $a = rand(1, 9);
                        $b = rand(1, 9);
                        $_SESSION['captcha_q'] = "$a + $b = ?";
                        $_SESSION['captcha_answer'] = $a + $b;
                    }
                }
            } else {
                $_SESSION['login_fail_count']++;
                $error = "Tài khoản không tồn tại! (Lần thử: " . $_SESSION['login_fail_count'] . ")";
                if ($_SESSION['login_fail_count'] >= $captcha_threshold) {
                    $a = rand(1, 9);
                    $b = rand(1, 9);
                    $_SESSION['captcha_q'] = "$a + $b = ?";
                    $_SESSION['captcha_answer'] = $a + $b;
                }
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; margin-top: 50px; }
        .container { width: 500px; border: 1px solid #ccc; }
        .header { background-color: #007bff; color: white; padding: 10px; font-weight: bold; }
        .form-group { margin: 20px; display: flex; align-items: center; }
        .form-group label { width: 100px; text-align: right; margin-right: 20px; font-weight: bold; }
        .form-group input { flex: 1; padding: 5px; }
        .btn-container { text-align: center; margin-bottom: 20px; padding-left: 120px; display: flex;}
        .btn { background-color: #007bff; color: white; border: none; padding: 8px 30px; cursor: pointer; font-weight: bold;}
        .btn:hover { background-color: #0056b3; }
        .error-msg { color: red; text-align: center; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Đăng nhập</div>
        
        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Tài khoản:</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Mật khẩu:</label>
                <input type="password" name="password" required>
            </div>
                <?php if (isset($_SESSION['login_fail_count']) && $_SESSION['login_fail_count'] >= $captcha_threshold): ?>
                <div class="form-group">
                    <label>Xác thực:</label>
                    <div style="flex:1; display:flex; gap:8px; align-items:center;">
                        <input type="text" name="captcha" placeholder="<?php echo htmlspecialchars($_SESSION['captcha_q'] ?? ''); ?>" required>
                        <small style="color:#666;">Vui lòng nhập kết quả để chứng minh bạn không phải là máy</small>
                    </div>
                </div>
                <?php endif; ?>
            <div class="btn-container">
                <button type="submit" class="btn">Đăng nhập</button>
                <a href="register.php" class="btn btn-secondary" style="margin-left:10px; text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">Đăng ký</a>
            </div>
        </form>
    </div>
</body>
</html>