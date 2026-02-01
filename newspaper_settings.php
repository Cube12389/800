<?php
// 报社设置页面
require_once 'news_column_config.php';
require_once 'config.php';

// 检查用户是否登录
requireLogin();

// 获取报社ID
if (!isset($_GET['id'])) {
    redirect('news_column.php');
}

$newspaperId = intval($_GET['id']);

// 获取报社信息
$newspaper = getNewspaper($newspaperId);
if (!$newspaper) {
    redirect('news_column.php');
}

// 获取当前用户ID
$currentUserId = getCurrentUserId();

// 检查用户是否有权限管理报社
if (!canManageNewspaper($newspaperId, $currentUserId)) {
    redirect('newspaper_home.php?id=' . $newspaperId);
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $avatar = $newspaper['avatar'];
    
    // 处理头像上传
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $targetDir = 'asstes/UrseIcon/';
        $fileName = basename($_FILES['avatar']['name']);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
        
        // 允许的文件类型
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array(strtolower($fileType), $allowedTypes)) {
            // 上传文件
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFilePath)) {
                $avatar = $fileName;
            }
        }
    }
    
    // 更新报社信息
    if (updateNewspaper($newspaperId, $name, $description, $avatar)) {
        // 重定向到报社主页
        redirect('newspaper_home.php?id=' . $newspaperId);
    } else {
        $error = '更新失败，请重试';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>报社设置 - <?php echo htmlspecialchars($newspaper['name']); ?></title>
    <link rel="stylesheet" href="css/navbar.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 0 20px;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #007bff;
        }
        
        .settings-form {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #495057;
        }
        
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .avatar-upload {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .current-avatar {
            width: 128px;
            height: 128px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #007bff;
        }
        
        .avatar-input {
            flex: 1;
        }
        
        .form-actions {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn:hover {
            background-color: #0069d9;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .error-message {
            color: #dc3545;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
        }
        
        @media screen and (max-width: 768px) {
            .container {
                margin: 30px auto;
            }
            
            .settings-form {
                padding: 20px;
            }
            
            .avatar-upload {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .form-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>报社设置</h1>
        
        <div class="settings-form">
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">报社名称</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($newspaper['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">报社描述</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($newspaper['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>报社头像</label>
                    <div class="avatar-upload">
                        <img src="asstes/UrseIcon/<?php echo htmlspecialchars($newspaper['avatar']); ?>" alt="当前头像" class="current-avatar">
                        <div class="avatar-input">
                            <input type="file" name="avatar" accept="image/*">
                            <small>支持 JPG、PNG、GIF 格式</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="newspaper_home.php?id=<?php echo $newspaperId; ?>" class="btn btn-secondary">取消</a>
                    <button type="submit" class="btn">保存更改</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>