<?php
require '../db_config.php';

header('Content-Type: application/json');

$giftId = $_POST['gift_id'] ?? 0;

$stmt = $conn->prepare("DELETE FROM gifts WHERE gift_id = ?");
$stmt->bind_param("i", $giftId);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$stmt->close();
$conn->close();
?>