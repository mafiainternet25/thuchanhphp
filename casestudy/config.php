<?php
// config.php
$servername = "localhost";
$username = "root"; // Mặc định của XAMPP là root
$password = "";     // Mặc định của XAMPP là rỗng
$dbname = "casestudydb";

// Tạo kết nối

// Check that the mysqli extension is loaded (important for Apache/PHP module vs CLI differences)
if (!extension_loaded('mysqli')) {
    // This will show in the browser when the config is included; helps diagnose environment problems.
    die("PHP mysqli extension is not loaded. Enable mysqli in your php.ini and restart Apache/XAMPP.");
}

$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập charset tiếng Việt
$conn->set_charset("utf8");
?>