<?php
include 'auth.php'; // 引入 Session 验证
require '../db_config.php';

$gift = null;
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM gifts WHERE gift_id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $gift = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>后台 - <?php echo $gift ? '编辑' : '添加'; ?>商品</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
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
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            font-weight: 500;
            margin: 10px 0 5px;
            color: #2c3e50;
        }
        input[type="text"],
        input[type="number"],
        input[type="file"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .ck-editor__editable {
            min-height: 150px;
        }
        button {
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin: 5px 0;
        }
        .submit-btn {
            background-color: #3498db;
            color: #fff;
        }
        .cancel-btn {
            background-color: #95a5a6;
            color: #fff;
        }
        button:hover {
            opacity: 0.9;
        }
        .image-preview {
            margin: 10px 0;
        }
        .image-preview img {
            max-width: 100px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?> <!-- 引入导航栏 -->
    
    <div class="container">
        <h1><?php echo $gift ? '编辑' : '添加'; ?>商品</h1>
        <form id="gift-form" enctype="multipart/form-data">
            <?php if ($gift): ?>
                <input type="hidden" name="action" value="edit_gift">
                <input type="hidden" name="gift_id" value="<?php echo $gift['gift_id']; ?>">
            <?php else: ?>
                <input type="hidden" name="action" value="add_gift">
            <?php endif; ?>
            <label>商品名称</label>
            <input type="text" name="gift_name" value="<?php echo $gift['gift_name'] ?? ''; ?>" required>
            <label>商品图片</label>
            <input type="file" name="gift_image" accept="image/*" onchange="previewImage(this)">
            <?php if ($gift && $gift['gift_image']): ?>
                <div class="image-preview">
                    <img src="<?php echo $gift['gift_image']; ?>" alt="当前图片">
                </div>
            <?php endif; ?>
            <label>所需积分</label>
            <input type="number" name="gift_points" value="<?php echo $gift['gift_points'] ?? 0; ?>" min="0" required>
            <label>库存</label>
            <input type="number" name="gift_stock" value="<?php echo $gift['gift_stock'] ?? 0; ?>" min="0" required>
            <label>商品描述</label>
            <textarea name="gift_description" id="gift_description"><?php echo $gift['gift_description'] ?? ''; ?></textarea>
            <button type="submit" class="submit-btn">保存</button>
            <button type="button" class="cancel-btn" onclick="location.href='manage_gifts.php'">返回</button>
        </form>
    </div>

    <script>
        ClassicEditor
            .create(document.querySelector('#gift_description'), {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList'],
                height: 200
            })
            .catch(error => {
                console.error(error);
            });

        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('.image-preview').html('<img src="' + e.target.result + '" alt="预览图片">');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        $('#gift-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: 'process.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(data) {
                    console.log('Response:', data); // 调试：输出响应
                    if (data.success) {
                        alert('保存成功');
                        location.href = 'manage_gifts.php'; // 保存后跳转
                    } else {
                        alert('保存失败：' + data.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error); // 调试：输出错误
                    console.log('Response Text:', xhr.responseText);
                    alert('请求失败：' + error);
                }
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>