<?php
require '../db_config.php';

header('Content-Type: application/json');

$employeeId = $_POST['employee_id'] ?? 0;
$points = $_POST['points'] ?? 0;

if ($employeeId && $points) {
    $stmt = $conn->prepare("INSERT INTO points (employee_id, points_amount, points_date) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $employeeId, $points);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => '参数错误']);
}

$conn->close();
?>