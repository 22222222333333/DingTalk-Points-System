<?php
require 'db_config.php';

// 配置参数
$corpId = '';
$appKey = '';
$appSecret = '';

// 获取 access_token
$tokenUrl = "https://oapi.dingtalk.com/gettoken?appkey=$appKey&appsecret=$appSecret";
$tokenData = json_decode(file_get_contents($tokenUrl), true);
if ($tokenData['errcode'] != 0) {
    die(json_encode(['errcode' => $tokenData['errcode'], 'errmsg' => $tokenData['errmsg']]));
}
$accessToken = $tokenData['access_token'];

// 获取所有部门 ID
function getAllDeptIds($accessToken) {
    $deptIds = [];
    $url = "https://oapi.dingtalk.com/department/list?access_token=$accessToken";
    $deptData = json_decode(file_get_contents($url), true);
    if ($deptData['errcode'] == 0) {
        foreach ($deptData['department'] as $dept) {
            $deptIds[] = $dept['id'];
        }
    }
    return $deptIds;
}

// 获取部门下的员工列表
function getUsersByDept($accessToken, $deptId) {
    $users = [];
    $offset = 0;
    $size = 100; // 每页最多 100 条

    do {
        $url = "https://oapi.dingtalk.com/topapi/v2/user/list?access_token=$accessToken";
        $params = json_encode([
            'dept_id' => $deptId,
            'cursor' => $offset,
            'size' => $size
        ]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($data['errcode'] != 0) {
            file_put_contents('sync_error.log', "获取部门 $deptId 用户失败: " . $data['errmsg'] . "\n", FILE_APPEND);
            break;
        }

        $users = array_merge($users, $data['result']['list']);
        $offset += $size;
    } while ($data['result']['has_more']);

    return $users;
}

// 获取部门名称
function getDeptName($accessToken, $deptId) {
    $url = "https://oapi.dingtalk.com/department/get?access_token=$accessToken&id=$deptId";
    $deptData = json_decode(file_get_contents($url), true);
    return $deptData['errcode'] == 0 ? $deptData['name'] : '未知部门';
}

// 主逻辑：同步员工信息到数据库
$deptIds = getAllDeptIds($accessToken);
$allUsers = [];

foreach ($deptIds as $deptId) {
    $users = getUsersByDept($accessToken, $deptId);
    $allUsers = array_merge($allUsers, $users);
}

// 去重（一个员工可能属于多个部门）
$uniqueUsers = [];
foreach ($allUsers as $user) {
    $uniqueUsers[$user['userid']] = $user;
}

foreach ($uniqueUsers as $user) {
    $dingtalkUserId = $user['userid'];
    $name = $user['name'];
    $avatar = !empty($user['avatar']) ? $user['avatar'] : './uploads/20250311154809.jpg';

    // 获取部门名称（用户可能属于多个部门，取第一个为主部门）
    $deptIds = $user['dept_id_list'];
    $departments = [];
    foreach ($deptIds as $deptId) {
        $departments[] = getDeptName($accessToken, $deptId);
    }
    $deptString = implode(',', $departments);

    // 检查员工是否已存在
    $stmt = $conn->prepare("SELECT employee_id FROM employees WHERE dingtalk_user_id = ?");
    $stmt->bind_param("s", $dingtalkUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
        // 更新现有员工信息
        $employeeId = $row['employee_id'];
        $stmt = $conn->prepare("UPDATE employees SET employee_name = ?, avatar = ?, department = ? WHERE dingtalk_user_id = ?");
        $stmt->bind_param("ssss", $name, $avatar, $deptString, $dingtalkUserId);
        $stmt->execute();
        $stmt->close();
    } else {
        // 插入新员工
        $stmt = $conn->prepare("INSERT INTO employees (dingtalk_user_id, employee_name, avatar, department, join_date) VALUES (?, ?, ?, ?, CURDATE())");
        $stmt->bind_param("ssss", $dingtalkUserId, $name, $avatar, $deptString);
        $stmt->execute();
        $stmt->close();
    }

    // 记录同步日志
    file_put_contents('sync.log', "同步员工: $dingtalkUserId, 姓名: $name, 部门: $deptString, 头像: $avatar\n", FILE_APPEND);
}

$conn->close();
echo json_encode(['errcode' => 0, 'errmsg' => '员工信息同步完成']);
?>