<?php
// checkAdmin.php
session_start();
require 'db_config.php';

// 配置参数（根据你的钉钉应用调整）
$corpId = '';
$appKey = '';
$appSecret = '';

// 获取临时授权码
$code = $_POST['code'];

// 获取 access_token
$tokenUrl = "https://oapi.dingtalk.com/gettoken?appkey=$appKey&appsecret=$appSecret";
$tokenData = json_decode(file_get_contents($tokenUrl), true);
if ($tokenData['errcode'] != 0) {
    die(json_encode(['errcode' => $tokenData['errcode'], 'errmsg' => $tokenData['errmsg']]));
}
$accessToken = $tokenData['access_token'];

// 获取用户信息
$userUrl = "https://oapi.dingtalk.com/user/getuserinfo?access_token=$accessToken&code=$code";
$userData = json_decode(file_get_contents($userUrl), true);
if ($userData['errcode'] != 0) {
    die(json_encode(['errcode' => $userData['errcode'], 'errmsg' => $userData['errmsg']]));
}

// 检查是否为管理员
$dingtalkUserId = $userData['userid'];
$stmt = $conn->prepare("SELECT admin_name FROM admins WHERE dingtalk_user_id = ?");
$stmt->bind_param("s", $dingtalkUserId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if ($row) {
    // 设置 Session
    $_SESSION['is_admin'] = true;
    $_SESSION['admin_name'] = $row['admin_name'];
    echo json_encode(['errcode' => 0, 'is_admin' => true]);
} else {
    echo json_encode(['errcode' => 0, 'is_admin' => false]);
}

$conn->close();
?>