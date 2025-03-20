<?php
include 'auth.php'; // 引入 Session 验证
require '../db_config.php';

// 查询商品数据
$result = $conn->query("SELECT * FROM gifts WHERE is_deleted = 0");
$gifts = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
 <meta charset="UTF-8">
 <title>后台 - 商品管理</title>
 <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
 <style>
 body {
 font-family: 'Segoe UI', Arial, sans-serif;
 margin: 0;
 padding: 0;
 background-color: #f4f7f9;
 color: #333;
 }
 .navbar {
 background-color: #2c3e50;
 padding: 15px 20px;
 box-shadow: 0 2px 5px rgba(0,0,0,0.1);
 position: fixed;
 top: 0;
 width: 100%;
 z-index: 1000;
 }
 .navbar a {
 color: #ecf0f1;
 text-decoration: none;
 margin: 0 20px;
 font-weight: 500;
 transition: color 0.3s;
 }
 .navbar a:hover {
 color: #3498db;
 }
 .container {
 margin-top: 80px;
 padding: 20px;
 max-width: 1200px;
 margin-left: auto;
 margin-right: auto;
 }
 h1 {
 font-size: 28px;
 color: #2c3e50;
 margin-bottom: 20px;
 }
 .add-btn {
 padding: 10px 20px;
 background-color: #2ecc71;
 color: #fff;
 text-decoration: none;
 border-radius: 4px;
 display: inline-block;
 margin-bottom: 20px;
 }
 .add-btn:hover {
 opacity: 0.9;
 }
 table {
 width: 100%;
 border-collapse: collapse;
 background-color: #fff;
 box-shadow: 0 1px 3px rgba(0,0,0,0.1);
 border-radius: 8px;
 overflow: hidden;
 }
 th, td {
 padding: 12px 15px;
 text-align: center;
 border-bottom: 1px solid #e0e0e0;
 }
 th {
 background-color: #3498db;
 color: #fff;
 font-weight: 600;
 }
 tr:hover {
 background-color: #f9f9f9;
 }
 .action-btn {
 padding: 6px 12px; /* 统一 padding */
 margin: 0 5px;
 border: none;
 border-radius: 10px;
 cursor: pointer;
 transition: background-color 0.3s;
 font-size: 14px; /* 统一字体大小 */
 display: inline-block; /* 确保一致性 */
 text-align: center; /* 文本居中 */
 width: 60px; /* 固定宽度，确保大小一致 */
 box-sizing: border-box; /* 确保 padding 不影响宽度 */
 text-decoration: none; /* 移除 <a> 的下划线 */
 color: #fff; /* 统一文字颜色 */
 }
 .action-btn.edit {
 background-color: #f39c12;
 }
 .action-btn.delete {
 background-color: #e74c3c;
 }
 .action-btn:hover {
 opacity: 0.9;
 }
 </style>
</head>
<body>
 <?php include 'navbar.php'; ?> <!-- 引入导航栏 -->

 <div class="container">
 <h1>商品管理</h1>
 <a href="edit_gift.php" class="add-btn">添加新商品</a>
 <table>
 <thead>
 <tr>
 <th>ID</th>
 <th>名称</th>
 <th>图片</th>
 <th>所需积分</th>
 <th>库存</th>
 <th>操作</th>
 </tr>
 </thead>
 <tbody>
 <?php foreach ($gifts as $gift): ?>
 <tr>
 <td><?php echo htmlspecialchars($gift['gift_id']); ?></td>
 <td><?php echo htmlspecialchars($gift['gift_name']); ?></td>
 <td><img src="<?php echo htmlspecialchars($gift['gift_image']); ?>" style="width: 50px;"></td>
 <td><?php echo htmlspecialchars($gift['gift_points']); ?></td>
 <td><?php echo htmlspecialchars($gift['gift_stock']); ?></td>
 <td>
 <button class="action-btn edit" onclick="window.location.href='edit_gift.php?id=<?php echo htmlspecialchars($gift['gift_id']); ?>'">编辑</button>
 <button class="action-btn delete" onclick="deleteGift(<?php echo htmlspecialchars($gift['gift_id']); ?>)">删除</button>
 </td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table>
 </div>

 <script>
 function deleteGift(giftId) {
 if (confirm('确定删除此商品吗？')) {
 $.ajax({
 url: 'process.php',
 type: 'POST',
 dataType: 'json',
 data: { action: 'delete_gift', gift_id: giftId },
 success: function(data) {
 console.log('Delete Response:', data);
 if (data.success) {
 alert('删除成功');
 location.reload();
 } else {
 alert('删除失败：' + data.error);
 }
 },
 error: function(xhr, status, error) {
 console.error('AJAX Error:', status, error);
 console.log('Response Text:', xhr.responseText);
 alert('请求失败：' + error);
 }
 });
 }
 }
 </script>
</body>
</html>
<?php $conn->close(); ?>