<?php
require 'db_config.php';

// 配置参数
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

// 获取用户详情
$userDetailUrl = "https://oapi.dingtalk.com/user/get?access_token=$accessToken&userid=" . $userData['userid'];
$userDetail = json_decode(file_get_contents($userDetailUrl), true);

if ($userDetail['errcode'] != 0) {
    die(json_encode(['errcode' => $userDetail['errcode'], 'errmsg' => $userDetail['errmsg']]));
}

// 获取部门名称
$departmentIds = $userDetail['department']; // 部门 ID 数组
$departments = [];
if (!empty($departmentIds)) {
    foreach ($departmentIds as $deptId) {
        $deptUrl = "https://oapi.dingtalk.com/department/get?access_token=$accessToken&id=$deptId";
        $deptData = json_decode(file_get_contents($deptUrl), true);
        if ($deptData['errcode'] == 0) {
            $departments[] = $deptData['name']; // 只保存部门名称
        }
    }
}

// 同步用户信息到数据库并获取 employee_id
$dingtalkUserId = $userData['userid'];
$stmt = $conn->prepare("SELECT employee_id FROM employees WHERE dingtalk_user_id = ?");
$stmt->bind_param("s", $dingtalkUserId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

// 设置默认头像如果为空
$avatar = !empty($userDetail['avatar']) ? $userDetail['avatar'] : './uploads/20250311154809.jpg';

if ($row) {
    $employeeId = $row['employee_id'];
    // 更新用户信息，包括头像和部门名称
    $stmt = $conn->prepare("UPDATE employees SET employee_name = ?, avatar = ?, department = ? WHERE dingtalk_user_id = ?");
    $deptString = implode(',', $departments); // 部门名称数组转为逗号分隔字符串
    $stmt->bind_param("ssss", $userDetail['name'], $avatar, $deptString, $dingtalkUserId);
    $stmt->execute();
    $stmt->close();
} else {
    // 插入新用户，包括头像和部门名称
    $stmt = $conn->prepare("INSERT INTO employees (dingtalk_user_id, employee_name, avatar, department, join_date) VALUES (?, ?, ?, ?, CURDATE())");
    $deptString = implode(',', $departments); // 部门名称数组转为逗号分隔字符串
    $stmt->bind_param("ssss", $dingtalkUserId, $userDetail['name'], $avatar, $deptString);
    $stmt->execute();
    $employeeId = $conn->insert_id;
    $stmt->close();
}

// 获取用户积分
$stmt = $conn->prepare("SELECT SUM(points_amount) as total_points FROM points WHERE employee_id = ?");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$points = $result->fetch_assoc()['total_points'] ?? 0;
$stmt->close();

echo json_encode([
    'errcode' => 0,
    'name' => $userDetail['name'],
    'avatar' => $avatar,
    'employee_id' => $employeeId,
    'points' => $points,
    'department' => $departments // 返回部门名称数组
]);

$conn->close();
?>