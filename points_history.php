<?php
require 'db_config.php';

$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;

if ($employee_id <= 0) {
    die("无效的员工 ID");
}

$stmt = $conn->prepare("
    SELECT points_amount, transaction_type, transaction_description, transaction_date, operator
    FROM points
    WHERE employee_id = ?
    ORDER BY transaction_date DESC
");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$points_history = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 获取员工信息
$stmt = $conn->prepare("SELECT employee_name FROM employees WHERE employee_id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>积分流水 - <?php echo htmlspecialchars($employee['employee_name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
            transition: background-color 0.3s, color 0.3s;
            position: relative;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .history-list {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 10px;
            transition: background-color 0.3s;
        }
        .history-item {
            display: flex;
            flex-wrap: wrap;
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            color: #555;
            line-height: 1.5;
        }
        .history-item div {
            flex: 1 1 100%;
            margin-bottom: 5px;
        }
        @media (min-width: 600px) {
            .history-item div {
                flex: 1 1 20%;
                margin-bottom: 0;
            }
        }
        .back-btn {
            display: block;
            text-align: center;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #ecb4ac;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .back-btn:hover {
            background-color: #2980b9;
        }
        .back-icon {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 24px; /* 增大字体以匹配箭头大小 */
            color: #fff;
            cursor: pointer;
            background: linear-gradient(135deg, #ecb4ac, #ecb4ac);
            width: 44px;
            height: 44px;
            line-height: 44px;
            text-align: center;
            border-radius: 50%;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            text-decoration: none;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .back-icon:before {
            content: "<"; /* 使用 "<" 模拟箭头 */
            font-size: 24px; /* 调整箭头大小 */
            line-height: 44px; /* 垂直居中 */
            transform: translateX(2px); /* 微调位置 */
        }
        .back-icon:hover {
            transform: scale(1.1); /* 仅放大，不旋转 */
            box-shadow: 0 6px 15px rgba(0,0,0,0.3);
        }

        /* 深色模式样式 */
        .dark-mode {
            background-color: #1e1e1e;
            color: #fff;
        }
        .dark-mode h2 {
            color: #fff;
        }
        .dark-mode .history-list {
            background-color: #2e2e2e;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        .dark-mode .history-item {
            color: #ddd;
            border-bottom: 1px solid #444;
        }
        .dark-mode .history-list p {
            color: #aaa;
        }
        .dark-mode .back-btn {
            background-color: #b37c74;
        }
        .dark-mode .back-btn:hover {
            background-color: #2980b9;
        }
        .dark-mode .back-icon {
            background: linear-gradient(135deg, #b37c74, #b37c74);
        }
        .dark-mode .back-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 15px rgba(0,0,0,0.4);
        }
    </style>
</head>
<body>
    <a href="index.html" class="back-icon" title="返回"></a>
    <h2><?php echo htmlspecialchars($employee['employee_name']); ?> 的积分流水</h2>
    <div class="history-list">
        <?php if (empty($points_history)): ?>
            <p style="text-align: center; color: #888;">暂无积分流水记录</p>
        <?php else: ?>
            <?php foreach ($points_history as $record): ?>
                <div class="history-item">
                    <div>积分: <?php echo htmlspecialchars($record['points_amount']); ?></div>
                    <div>类型: <?php echo htmlspecialchars($record['transaction_type']); ?></div>
                    <div>描述: <?php echo htmlspecialchars($record['transaction_description']); ?></div>
                    <div>日期: <?php echo htmlspecialchars($record['transaction_date']); ?></div>
                    <div>操作人: <?php echo htmlspecialchars($record['operator']); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <a href="index.html" class="back-btn">返回</a>

    <script>
        // 应用主题函数
        function applyTheme() {
            const savedTheme = localStorage.getItem('themeMode');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }
        }

        // 页面加载时应用主题
        document.addEventListener('DOMContentLoaded', function() {
            applyTheme();
        });
    </script>
</body>
</html>