<?php
// /admin/auth.php
session_start();
require '../db_config.php'; // 数据库配置文件

// 检查是否已通过管理员验证
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    // 未登录或非管理员，跳转到前端页面
    header("Location: /index.html");
    exit("无权限访问此页面，请先通过前端授权");
}
?>