<?php
include 'auth.php'; // 引入 Session 验证
require '../db_config.php';

// 查询日志数据，直接读取 points 表的 operator 字段
$stmt = $conn->prepare("
    SELECT p.point_id, e.employee_name AS target_employee, p.points_amount, p.transaction_type, 
           p.transaction_description, p.transaction_date, p.operator
    FROM points p
    JOIN employees e ON p.employee_id = e.employee_id
    ORDER BY p.transaction_date DESC
");
$stmt->execute();
$result = $stmt->get_result();
$logs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>后台 - 积分操作日志</title>
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
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?> <!-- 引入导航栏 -->

    <div class="container">
        <h1>积分操作日志</h1>
        <table>
            <thead>
                <tr>
                    <th>日志ID</th>
                    <th>操作者</th>
                    <th>目标员工</th>
                    <th>积分变化</th>
                    <th>操作类型</th>
                    <th>原因</th>
                    <th>时间</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['point_id']); ?></td>
                        <td><?php echo htmlspecialchars($log['operator']); ?></td>
                        <td><?php echo htmlspecialchars($log['target_employee']); ?></td>
                        <td><?php echo htmlspecialchars($log['points_amount']); ?></td>
                        <td><?php echo htmlspecialchars($log['transaction_type']); ?></td>
                        <td><?php echo htmlspecialchars($log['transaction_description']); ?></td>
                        <td><?php echo htmlspecialchars($log['transaction_date']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php $conn->close(); ?>