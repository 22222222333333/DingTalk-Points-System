<?php
require '../db_config.php';

$stmt = $conn->prepare("
    SELECT gr.redemption_id, e.employee_name, g.gift_name, gr.points_deducted, gr.redemption_date, gr.redemption_status
    FROM gift_redemptions gr
    JOIN employees e ON gr.employee_id = e.employee_id
    JOIN gifts g ON gr.gift_id = g.gift_id
    ORDER BY gr.redemption_date DESC
");
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>后台 - 订单管理</title>
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
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background-color: #2ecc71;
            color: #fff;
            transition: background-color 0.3s;
        }
        .action-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="index.php">员工积分管理</a>
        <a href="manage_gifts.php">管理商品</a>
        <a href="manage_orders.php">管理订单</a>
        <a href="logs.php">查看日志</a>
    </div>

    <div class="container">
        <h1>订单管理</h1>
        <table>
            <thead>
                <tr>
                    <th>订单ID</th>
                    <th>员工</th>
                    <th>礼品</th>
                    <th>兑换积分</th>
                    <th>兑换日期</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo $order['redemption_id']; ?></td>
                        <td><?php echo $order['employee_name']; ?></td>
                        <td><?php echo $order['gift_name']; ?></td>
                        <td><?php echo $order['points_deducted']; ?></td>
                        <td><?php echo $order['redemption_date']; ?></td>
                        <td><?php echo $order['redemption_status']; ?></td>
                        <td>
                            <?php if ($order['redemption_status'] === '待处理'): ?>
                                <button class="action-btn" onclick="verifyOrder(<?php echo $order['redemption_id']; ?>)">核销</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        function verifyOrder(orderId) {
            if (confirm('确定核销此订单吗？')) {
                $.ajax({
                    url: 'process.php',
                    type: 'POST',
                    data: { action: 'verify_order', redemption_id: orderId },
                    success: function(response) {
                        let data = JSON.parse(response);
                        if (data.success) {
                            alert('核销成功');
                            location.reload();
                        } else {
                            alert('核销失败：' + data.error);
                        }
                    }
                });
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>