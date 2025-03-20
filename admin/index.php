<?php
include 'auth.php'; // 引入 Session 验证
require '../db_config.php';

// 处理筛选参数
$filter_name = isset($_GET['filter_name']) ? $_GET['filter_name'] : '';
$filter_dept = isset($_GET['filter_dept']) ? $_GET['filter_dept'] : '';

// 获取所有部门（用于筛选下拉框）
$dept_query = "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' AND is_deleted = 0";
$dept_result = $conn->query($dept_query);
$departments = [];
while ($row = $dept_result->fetch_assoc()) {
    $departments[] = $row['department'];
}

// 获取员工和积分数据（仅显示未删除的员工）
$sql = "SELECT e.employee_id, e.employee_name, e.department, SUM(p.points_amount) as total_points 
        FROM employees e 
        LEFT JOIN points p ON e.employee_id = p.employee_id 
        WHERE e.is_deleted = 0";

if ($filter_name) {
    $sql .= " AND e.employee_name LIKE '%" . $conn->real_escape_string($filter_name) . "%'";
}
if ($filter_dept) {
    $sql .= " AND e.department = '" . $conn->real_escape_string($filter_dept) . "'";
}

$sql .= " GROUP BY e.employee_id, e.employee_name, e.department";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$employees = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>后台 - 员工积分管理</title>
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
        .filter-section {
            margin-bottom: 20px;
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .filter-section label {
            margin-right: 10px;
            font-weight: 500;
        }
        .filter-section input, .filter-section select {
            padding: 6px;
            margin-right: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filter-section button {
            padding: 6px 15px;
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .filter-section button:hover {
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
        .actions-container {
            display: flex;
            flex-direction: row; /* 默认横向排列 */
            gap: 10px; /* 按钮间距 */
            justify-content: center; /* 水平居中 */
            align-items: center; /* 垂直居中 */
            flex-wrap: nowrap; /* 默认不换行 */
        }
        .action-btn {
            padding: 6px 12px;
            margin: 0; /* 移除默认 margin */
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 60px; /* 固定宽度 */
            text-align: center; /* 文字居中 */
        }
        .action-btn.add {
            background-color: #2ecc71;
            color: #fff;
        }
        .action-btn.subtract {
            background-color: #e74c3c;
            color: #fff;
        }
        .action-btn.delete {
            background-color: #e74c3c;
            color: #fff;
        }
        .action-btn:hover {
            opacity: 0.9;
        }
        /* 屏幕宽度小于 768px 时竖向排列 */
        @media (max-width: 768px) {
            .actions-container {
                flex-direction: column; /* 竖向排列 */
                gap: 8px; /* 减小间距 */
            }
            .action-btn {
                width: 80px; /* 竖向时略宽 */
            }
        }
        #popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            width: 320px;
            z-index: 2000;
        }
        #popup h3 {
            margin: 0 0 15px;
            font-size: 20px;
            color: #2c3e50;
        }
        #popup input, #popup textarea {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        #popup textarea {
            height: 80px;
            resize: none;
        }
        #popup button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            transition: background-color 0.3s;
        }
        #popup .submit-btn {
            background-color: #3498db;
            color: #fff;
        }
        #popup .cancel-btn {
            background-color: #95a5a6;
            color: #fff;
        }
        #popup button:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?> <!-- 引入导航栏 -->

    <div class="container">
        <h1>员工积分管理</h1>

        <!-- 筛选区域 -->
        <div class="filter-section">
            <form method="GET" action="">
                <label>姓名:</label>
                <input type="text" name="filter_name" value="<?php echo htmlspecialchars($filter_name); ?>" placeholder="输入姓名">
                
                <label>部门:</label>
                <select name="filter_dept">
                    <option value="">全部部门</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $filter_dept === $dept ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">筛选</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>员工ID</th>
                    <th>姓名</th>
                    <th>部门</th>
                    <th>当前积分</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                        <td><?php echo htmlspecialchars($employee['employee_name']); ?></td>
                        <td><?php echo htmlspecialchars($employee['department'] ?? '未分配部门'); ?></td>
                        <td><?php echo htmlspecialchars($employee['total_points'] ?? 0); ?></td>
                        <td>
                            <div class="actions-container">
                                <button class="action-btn add" onclick="showPopup(<?php echo $employee['employee_id']; ?>, 'add')">添加</button>
                                <button class="action-btn subtract" onclick="showPopup(<?php echo $employee['employee_id']; ?>, 'subtract')">减少</button>
                                <button class="action-btn delete" onclick="deleteEmployee(<?php echo $employee['employee_id']; ?>)">删除</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="popup">
        <h3 id="popup-title"></h3>
        <input type="number" id="points-input" placeholder="请输入积分数量" min="1">
        <textarea id="description-input" placeholder="请输入备注"></textarea>
        <button class="submit-btn" onclick="submitPoints()">提交</button>
        <button class="cancel-btn" onclick="$('#popup').hide()">取消</button>
        <input type="hidden" id="employee-id">
        <input type="hidden" id="action-type">
    </div>

    <script>
        function showPopup(employeeId, action) {
            $('#popup-title').text(action === 'add' ? '添加积分' : '减少积分');
            $('#employee-id').val(employeeId);
            $('#action-type').val(action);
            $('#points-input').val('');
            $('#description-input').val('');
            $('#popup').show();
        }

        function submitPoints() {
            let employeeId = $('#employee-id').val();
            let action = $('#action-type').val();
            let points = $('#points-input').val();
            let description = $('#description-input').val();

            if (!points || isNaN(points) || points <= 0) {
                alert('请输入有效的积分数量');
                return;
            }
            if (!description) {
                alert('请输入备注');
                return;
            }

            let amount = action === 'add' ? parseInt(points) : -parseInt(points);
            $.ajax({
                url: 'process.php',
                type: 'POST',
                dataType: 'json',
                data: { 
                    action: 'modify_points', 
                    employee_id: employeeId, 
                    points: amount,
                    transaction_type: action === 'add' ? '增加' : '减少',
                    transaction_description: description
                },
                success: function(data) {
                    if (data.success) {
                        alert('积分修改成功');
                        $('#popup').hide();
                        location.reload();
                    } else {
                        alert('操作失败：' + data.error);
                    }
                },
                error: function(xhr, status, error) {
                    alert('请求失败：' + error);
                }
            });
        }

        function deleteEmployee(employeeId) {
            if (confirm('确定删除此员工吗？此操作将标记员工为已删除状态。')) {
                $.ajax({
                    url: 'process.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { 
                        action: 'delete_employee', 
                        employee_id: employeeId 
                    },
                    success: function(data) {
                        if (data.success) {
                            alert('员工删除成功');
                            location.reload();
                        } else {
                            alert('删除失败：' + data.error);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('请求失败：' + error);
                    }
                });
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>