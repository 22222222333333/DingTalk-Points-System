<?php
require 'db_config.php';

$query = "SELECT gift_id, gift_name, gift_points, gift_image, gift_description FROM gifts WHERE is_enabled = 1 AND gift_stock > 0 AND is_deleted = 0";
$result = $conn->query($query);

if ($result === false) {
    die(json_encode(['errcode' => 1, 'errmsg' => '查询礼品失败: ' . $conn->error]));
}

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

if (empty($items)) {
    $items[] = ['gift_id' => 0, 'gift_name' => '暂无可用礼品', 'gift_points' => 0, 'gift_image' => './uploads/loading.gif', 'gift_description' => '暂无描述']; // 添加 gift_description
}

echo json_encode(['errcode' => 0, 'gifts' => $items]);

$conn->close();
?>