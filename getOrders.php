<?php
require 'db_config.php';

$employeeId = $_POST['employee_id'] ?? 0;

$stmt = $conn->prepare("
    SELECT gr.redemption_id, g.gift_name, gr.points_deducted, gr.redemption_date, gr.redemption_status
    FROM gift_redemptions gr
    JOIN gifts g ON gr.gift_id = g.gift_id
    WHERE gr.employee_id = ?
    ORDER BY gr.redemption_date DESC
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

echo json_encode(['errcode' => 0, 'orders' => $orders]);

$stmt->close();
$conn->close();
?>