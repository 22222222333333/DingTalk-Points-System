<?php
require '../db_config.php';

// 启动 Session 以获取管理员信息
session_start();

// 从 Session 获取当前管理员姓名
$operator = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : '未知管理员';

// 禁止任何非 JSON 输出
ob_start();
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'modify_points':
        $employeeId = $_POST['employee_id'] ?? 0;
        $points = $_POST['points'] ?? 0;
        $transactionType = $_POST['transaction_type'] ?? '未知';
        $transactionDescription = $_POST['transaction_description'] ?? '无描述';

        if ($employeeId && $points && $transactionType && $transactionDescription) {
            $stmt = $conn->prepare("INSERT INTO points (employee_id, points_amount, transaction_type, transaction_description, transaction_date, operator) 
                                   VALUES (?, ?, ?, ?, NOW(), ?)");
            $stmt->bind_param("iisss", $employeeId, $points, $transactionType, $transactionDescription, $operator);
            $success = $stmt->execute();
            $error = $stmt->error;
            $stmt->close();
            echo json_encode(['success' => $success, 'error' => $success ? '' : $error]);
        } else {
            echo json_encode(['success' => false, 'error' => '参数不完整']);
        }
        break;

    case 'add_gift':
        $giftName = $_POST['gift_name'] ?? '';
        $giftPoints = $_POST['gift_points'] ?? 0;
        $giftStock = $_POST['gift_stock'] ?? 0;
        $giftDescription = $_POST['gift_description'] ?? '';

        if ($giftName && $giftPoints >= 0 && $giftStock >= 0 && isset($_FILES['gift_image'])) {
            $image = $_FILES['gift_image'];
            $uploadDir = '../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $imagePath = $uploadDir . uniqid() . '_' . basename($image['name']);
            if (move_uploaded_file($image['tmp_name'], $imagePath)) {
                $stmt = $conn->prepare("INSERT INTO gifts (gift_name, gift_description, gift_points, gift_stock, gift_image, is_enabled, is_deleted) 
                                       VALUES (?, ?, ?, ?, ?, 1, 0)");
                $stmt->bind_param("ssiis", $giftName, $giftDescription, $giftPoints, $giftStock, $imagePath);
                $success = $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => $success, 'error' => $success ? '' : $conn->error]);
            } else {
                echo json_encode(['success' => false, 'error' => '图片上传失败']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => '参数不完整或无效']);
        }
        break;

    case 'edit_gift':
        $giftId = $_POST['gift_id'] ?? 0;
        $giftName = $_POST['gift_name'] ?? '';
        $giftPoints = $_POST['gift_points'] ?? 0;
        $giftStock = $_POST['gift_stock'] ?? 0;
        $giftDescription = $_POST['gift_description'] ?? '';

        if ($giftId && $giftName && $giftPoints >= 0 && $giftStock >= 0) {
            $imagePath = null;
            if (isset($_FILES['gift_image']) && $_FILES['gift_image']['size'] > 0) {
                $image = $_FILES['gift_image'];
                $uploadDir = '../uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $imagePath = $uploadDir . uniqid() . '_' . basename($image['name']);
                if (!move_uploaded_file($image['tmp_name'], $imagePath)) {
                    echo json_encode(['success' => false, 'error' => '图片上传失败']);
                    exit;
                }
            }
            if ($imagePath) {
                $stmt = $conn->prepare("UPDATE gifts SET gift_name = ?, gift_description = ?, gift_points = ?, gift_stock = ?, gift_image = ? WHERE gift_id = ? AND is_deleted = 0");
                $stmt->bind_param("ssiisi", $giftName, $giftDescription, $giftPoints, $giftStock, $imagePath, $giftId);
            } else {
                $stmt = $conn->prepare("UPDATE gifts SET gift_name = ?, gift_description = ?, gift_points = ?, gift_stock = ? WHERE gift_id = ? AND is_deleted = 0");
                $stmt->bind_param("ssiis", $giftName, $giftDescription, $giftPoints, $giftStock, $giftId);
            }
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success, 'error' => $success ? '' : $conn->error]);
        } else {
            echo json_encode(['success' => false, 'error' => '参数不完整或无效']);
        }
        break;

    case 'delete_gift':
        $giftId = $_POST['gift_id'] ?? 0;
        if ($giftId) {
            $stmt = $conn->prepare("UPDATE gifts SET is_deleted = 1 WHERE gift_id = ?");
            $stmt->bind_param("i", $giftId);
            $success = $stmt->execute();
            $error = $stmt->error;
            $stmt->close();
            echo json_encode(['success' => $success, 'error' => $success ? '' : $error]);
        } else {
            echo json_encode(['success' => false, 'error' => '无效的商品 ID']);
        }
        break;

    case 'verify_order':
        $redemptionId = $_POST['redemption_id'] ?? 0;
        $operator = $_POST['operator'] ?? $operator; // 从 POST 或 Session 获取操作人姓名

        if ($redemptionId <= 0) {
            error_log("Invalid redemption_id: " . $redemptionId);
            echo json_encode(['success' => false, 'error' => '无效的订单 ID']);
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE gift_redemptions 
            SET redemption_status = '已兑换', 
                redeemed_by = ?, 
                updated_at = NOW() 
            WHERE redemption_id = ? AND redemption_status = '待兑换'
        ");
        if ($stmt === false) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'error' => '准备更新状态失败: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("si", $operator, $redemptionId); // redeemed_by 存操作人姓名
        $success = $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $error = $stmt->error;
        $stmt->close();

        if ($success && $affectedRows > 0) {
            error_log("Order " . $redemptionId . " verified successfully by " . $operator);
            echo json_encode(['success' => true, 'error' => '']);
        } else {
            error_log("Verification failed for order " . $redemptionId . ": " . ($error ?: '状态可能已更改'));
            echo json_encode(['success' => false, 'error' => '核销失败，订单状态可能已更改']);
        }
        break;

    case 'cancel_order':
        $redemptionId = $_POST['redemption_id'] ?? 0;
        $employeeId = $_POST['employee_id'] ?? 0;
        $giftId = $_POST['gift_id'] ?? 0;
        $pointsDeducted = $_POST['points_deducted'] ?? 0;
        $giftName = $_POST['gift_name'] ?? '未知礼品';
        $operator = $_POST['operator'] ?? $operator; // 从 POST 或 Session 获取操作人姓名

        if ($redemptionId <= 0 || $employeeId <= 0 || $giftId <= 0 || $pointsDeducted <= 0) {
            error_log("Invalid parameters for cancel: redemption_id=" . $redemptionId . ", employee_id=" . $employeeId . ", gift_id=" . $giftId . ", points_deducted=" . $pointsDeducted);
            echo json_encode(['success' => false, 'error' => '参数错误']);
            exit;
        }

        $conn->begin_transaction();

        // 更新订单状态并记录操作人
        $stmt = $conn->prepare("
            UPDATE gift_redemptions 
            SET redemption_status = '取消兑换', 
                redeemed_by = ?, 
                updated_at = NOW() 
            WHERE redemption_id = ? AND redemption_status = '待兑换'
        ");
        if ($stmt === false) {
            error_log("Prepare failed: " . $conn->error);
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => '准备更新状态失败: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("si", $operator, $redemptionId); // redeemed_by 存操作人姓名
        $success = $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $error = $stmt->error;
        $stmt->close();

        if ($success && $affectedRows > 0) {
            // 归还积分
            $returnPoints = abs($pointsDeducted);
            $transactionType = '取消兑换';
            $transactionDescription = '取消兑换+' . $giftName;

            $stmt = $conn->prepare("INSERT INTO points (employee_id, points_amount, transaction_type, transaction_description, transaction_date, operator) 
                                   VALUES (?, ?, ?, ?, NOW(), ?)");
            if ($stmt === false) {
                error_log("Prepare failed: " . $conn->error);
                $conn->rollback();
                echo json_encode(['success' => false, 'error' => '准备归还积分失败: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param("iisss", $employeeId, $returnPoints, $transactionType, $transactionDescription, $operator);
            $success = $stmt->execute();
            $error = $stmt->error;
            $stmt->close();

            if (!$success) {
                $conn->rollback();
                error_log("Error returning points: " . $error);
                echo json_encode(['success' => false, 'error' => '归还积分失败: ' . $error]);
                exit;
            }

            // 加锁检查和归还库存
            $stmt = $conn->prepare("SELECT gift_stock FROM gifts WHERE gift_id = ? FOR UPDATE");
            if ($stmt === false) {
                error_log("Prepare failed: " . $conn->error);
                $conn->rollback();
                echo json_encode(['success' => false, 'error' => '准备锁定库存失败: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param("i", $giftId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $currentStock = $row['gift_stock'] ?? 0;
            $stmt->close();

            // 更新库存
            $stmt = $conn->prepare("UPDATE gifts SET gift_stock = gift_stock + 1 WHERE gift_id = ?");
            if ($stmt === false) {
                error_log("Prepare failed: " . $conn->error);
                $conn->rollback();
                echo json_encode(['success' => false, 'error' => '准备归还库存失败: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param("i", $giftId);
            $success = $stmt->execute();
            $error = $stmt->error;
            $stmt->close();

            if ($success) {
                $conn->commit();
                error_log("Order " . $redemptionId . " canceled successfully by " . $operator . ". Points returned: " . $returnPoints . ", Stock restored for gift_id: " . $giftId . " (from $currentStock to " . ($currentStock + 1) . ")");
                echo json_encode(['success' => true, 'error' => '']);
            } else {
                $conn->rollback();
                error_log("Error restoring stock: " . $error);
                echo json_encode(['success' => false, 'error' => '归还库存失败: ' . $error]);
            }
        } else {
            $conn->rollback();
            error_log("Cancellation failed for order " . $redemptionId . ": " . ($error ?: '状态可能已更改'));
            echo json_encode(['success' => false, 'error' => '取消核销失败，订单状态可能已更改']);
        }
        break;

    case 'delete_employee':
        $employeeId = $_POST['employee_id'] ?? 0;
        if ($employeeId) {
            // 软删除员工
            $stmt = $conn->prepare("UPDATE employees SET is_deleted = 1 WHERE employee_id = ?");
            $stmt->bind_param("i", $employeeId);
            $success = $stmt->execute();
            $error = $stmt->error;
            $stmt->close();

            if ($success) {
                // 记录删除操作到 points 表
                $stmt = $conn->prepare("INSERT INTO points (employee_id, points_amount, transaction_type, transaction_description, transaction_date, operator) 
                                       VALUES (?, 0, '删除', '员工被软删除', NOW(), ?)");
                $stmt->bind_param("is", $employeeId, $operator);
                $stmt->execute();
                $stmt->close();
            }
            echo json_encode(['success' => $success, 'error' => $success ? '' : $error]);
        } else {
            echo json_encode(['success' => false, 'error' => '无效的员工 ID']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => '未知操作']);
}

ob_end_flush();
$conn->close();
exit;
?>