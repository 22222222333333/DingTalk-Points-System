<?php
require 'db_config.php'; // 引入数据库配置文件

// 检查数据库连接
if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    echo json_encode(['errcode' => 1, 'errmsg' => '数据库连接失败']);
    exit;
}

// 获取 POST 请求中的参数，使用 ?? 运算符提供默认值
$employeeId = $_POST['employee_id'] ?? 0;
$giftId = $_POST['gift_id'] ?? 0;
$giftPoints = $_POST['gift_points'] ?? 0;

// 错误日志记录，方便调试
error_log("Redeem request: employee_id=" . $employeeId . ", gift_id=" . $giftId . ", gift_points=" . $giftPoints);

// 检查参数是否有效
if ($employeeId <= 0 || $giftId <= 0 || $giftPoints <= 0) {
    error_log("Invalid parameters: employee_id=" . $employeeId . ", gift_id=" . $giftId . ", gift_points=" . $giftPoints);
    echo json_encode(['errcode' => 1, 'errmsg' => '参数错误']);
    exit; // 终止脚本执行
}

try {
    // 开启事务，确保数据一致性
    $conn->begin_transaction();

    // 获取用户当前积分总额
    $stmt = $conn->prepare("SELECT SUM(points_amount) as total_points FROM points WHERE employee_id = ?");
    if ($stmt === false) {
        throw new Exception("准备查询积分总额失败: " . $conn->error);
    }
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $totalPoints = $row['total_points'] ?? 0;
    $stmt->close();

    error_log("Total points for employee " . $employeeId . ": " . $totalPoints);

    // 检查积分是否足够
    if ($totalPoints < $giftPoints) {
        error_log("Insufficient points for employee " . $employeeId);
        echo json_encode(['errcode' => 1, 'errmsg' => '积分不足']);
        $conn->rollback();
        exit;
    }

    // 检查并获取礼品信息（包括库存）
    $stmt = $conn->prepare("SELECT gift_name, gift_stock FROM gifts WHERE gift_id = ? FOR UPDATE"); // 使用 FOR UPDATE 锁定行
    if ($stmt === false) {
        throw new Exception("准备查询礼品信息失败: " . $conn->error);
    }
    $stmt->bind_param("i", $giftId);
    $stmt->execute();
    $result = $stmt->get_result();
    $gift = $result->fetch_assoc();
    $stmt->close();

    if (!$gift) {
        throw new Exception("礼品不存在");
    }

    $giftName = $gift['gift_name'];
    $giftStock = $gift['gift_stock'];

    // 检查库存是否足够
    if ($giftStock <= 0) {
        error_log("Gift out of stock: gift_id=" . $giftId);
        echo json_encode(['errcode' => 1, 'errmsg' => '礼品库存不足']);
        $conn->rollback();
        exit;
    }

    // 获取用户姓名作为 operator
    $stmt = $conn->prepare("SELECT employee_name FROM employees WHERE employee_id = ?");
    if ($stmt === false) {
        throw new Exception("准备查询用户姓名失败: " . $conn->error);
    }
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
    $operator = $employee ? $employee['employee_name'] : '未知用户';
    $stmt->close();

    $transactionType = '兑换';
    $transactionDescription = "用户兑换 $giftName";

    // 写入礼品兑换记录
    $status = '待兑换';
    $stmt = $conn->prepare("INSERT INTO gift_redemptions (employee_id, gift_id, points_deducted, redemption_date, redemption_status) VALUES (?, ?, ?, NOW(), ?)");
    if ($stmt === false) {
        throw new Exception("准备插入兑换记录失败: " . $conn->error);
    }
    $stmt->bind_param("iiis", $employeeId, $giftId, $giftPoints, $status);
    if (!$stmt->execute()) {
        throw new Exception("创建兑换记录失败: " . $stmt->error);
    }
    $stmt->close();

    // 扣减用户积分
    $deductPoints = -$giftPoints;
    $stmt = $conn->prepare("INSERT INTO points (employee_id, points_amount, transaction_type, transaction_description, transaction_date, operator) VALUES (?, ?, ?, ?, NOW(), ?)");
    if ($stmt === false) {
        throw new Exception("准备扣减积分失败: " . $conn->error);
    }
    $stmt->bind_param("iisss", $employeeId, $deductPoints, $transactionType, $transactionDescription, $operator);
    if (!$stmt->execute()) {
        throw new Exception("扣减积分失败: " . $stmt->error);
    }
    $stmt->close();

    // 扣减库存
    $stmt = $conn->prepare("UPDATE gifts SET gift_stock = gift_stock - 1 WHERE gift_id = ?");
    if ($stmt === false) {
        throw new Exception("准备更新库存失败: " . $conn->error);
    }
    $stmt->bind_param("i", $giftId);
    if (!$stmt->execute()) {
        throw new Exception("更新库存失败: " . $stmt->error);
    }
    $stmt->close();

    // 提交事务
    $conn->commit();

    error_log("Redemption successful: employee_id=" . $employeeId . ", gift_id=" . $giftId);
    echo json_encode(['errcode' => 0, 'errmsg' => '兑换成功']);

} catch (Exception $e) {
    // 回滚事务
    $conn->rollback();

    error_log("Transaction failed: " . $e->getMessage());
    echo json_encode(['errcode' => 1, 'errmsg' => $e->getMessage()]);
}

$conn->close();
?>