<?php
define('DB_HOST', ''); // 数据库服务器地址，本地通常为 localhost
define('DB_USER', '');      // 数据库用户名，例如 root
define('DB_PASSWORD', '');  // 数据库密码，如果没设置密码通常为空字符串 ''
define('DB_NAME', '');        // 你创建的数据库名称，例如 dingtalk_points_db

$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}

// 设置字符集，通常设置为 utf8mb4，支持中文和 emoji 等
$conn->set_charset("utf8mb4");
?>