<?php
include 'auth.php'; // 引入 Session 验证
require '../db_config.php';

// 处理添加管理员
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $employee_id = $_POST['employee_id'];
    $stmt = $conn->prepare("SELECT dingtalk_user_id, employee_name FROM employees WHERE employee_id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
    $stmt->close();

    if ($employee) {
        $stmt = $conn->prepare("INSERT IGNORE INTO admins (dingtalk_user_id, admin_name) VALUES (?, ?)");
        $stmt->bind_param("ss", $employee['dingtalk_user_id'], $employee['employee_name']);
        $stmt->execute();
        $stmt->close();
    }
}

// 处理删除管理员
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_admin'])) {
    $dingtalk_user_id = $_POST['dingtalk_user_id'];
    $stmt = $conn->prepare("DELETE FROM admins WHERE dingtalk_user_id = ?");
    $stmt->bind_param("s", $dingtalk_user_id);
    $stmt->execute();
    $stmt->close();
}

// 获取所有员工（用于选择）
$employees_query = "SELECT employee_id, employee_name, dingtalk_user_id FROM employees WHERE is_deleted = 0";
$employees_result = $conn->query($employees_query);

// 获取当前管理员列表
$admins_query = "SELECT dingtalk_user_id, admin_name FROM admins";
$admins_result = $conn->query($admins_query);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>后台 - 管理员设置</title>
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
        .section {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .section label {
            font-weight: 500;
            margin-right: 10px;
        }
        .section select {
            padding: 6px;
            margin-right: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .section button {
            padding: 6px 15px;
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .section button:hover {
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
        .delete-btn {
            padding: 6px 12px;
            background-color: #e74c3c;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .delete-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
     <?php include 'navbar.php'; ?> <!-- 引入导航栏 -->

    <div class="container">
        <h1>管理员设置</h1>

        <!-- 添加管理员 -->
        <div class="section">
            <h2>添加管理员</h2>
            <form method="POST" action="">
                <label>选择员工:</label>
                <select name="employee_id" required>
                    <option value="">请选择员工</option>
                    <?php while ($employee = $employees_result->fetch_assoc()): ?>
                        <option value="<?php echo $employee['employee_id']; ?>">
                            <?php echo htmlspecialchars($employee['employee_name']) . " (" . $employee['dingtalk_user_id'] . ")"; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" name="add_admin">添加</button>
            </form>
        </div>

        <!-- 当前管理员列表 -->
        <div class="section">
            <h2>当前管理员</h2>
            <table>
                <thead>
                    <tr>
                        <th>钉钉用户ID</th>
                        <th>姓名</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($admin = $admins_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($admin['dingtalk_user_id']); ?></td>
                            <td><?php echo htmlspecialchars($admin['admin_name']); ?></td>
                            <td>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="dingtalk_user_id" value="<?php echo htmlspecialchars($admin['dingtalk_user_id']); ?>">
                                    <button type="submit" name="delete_admin" class="delete-btn">删除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>