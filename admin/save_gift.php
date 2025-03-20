<?php
require '../db_config.php';

$giftId = $_POST['gift_id'] ?? 0;
$giftName = $_POST['gift_name'] ?? '';
$giftPoints = $_POST['gift_points'] ?? 0;
$giftImage = $_POST['gift_image'] ?? '';

if ($giftId) {
    $stmt = $conn->prepare("UPDATE gifts SET gift_name = ?, gift_points = ?, gift_image = ? WHERE gift_id = ?");
    $stmt->bind_param("sisi", $giftName, $giftPoints, $giftImage, $giftId);
} else {
    $stmt = $conn->prepare("INSERT INTO gifts (gift_name, gift_points, gift_image, is_enabled, gift_stock) VALUES (?, ?, ?, 1, 100)");
    $stmt->bind_param("sis", $giftName, $giftPoints, $giftImage);
}

if ($stmt->execute()) {
    header("Location: gifts.php");
} else {
    echo "保存失败: " . $conn->error;
}

$stmt->close();
$conn->close();
?>