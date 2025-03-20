<?php
require '../db_config.php';

header('Content-Type: application/json');

$redemptionId = $_POST['redemption_id'] ?? 0;

$stmt = $conn->prepare("UPDATE gift_redemptions SET redemption_status = '已兑换' WHERE redemption_id = ? AND redemption_status = '待兑换'");
$stmt->bind_param("i", $redemptionId);
if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error ?: '订单已核销或不存在']);
}

$stmt->close();
$conn->close();
?>