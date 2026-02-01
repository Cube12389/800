<?php
// 头像上传页面
require_once 'config.php';

requireLogin();

$userId = getCurrentUserId();
$error = '';
$success = '';

// 获取用户信息
function getUserInfo($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

// 处理头像上传
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $userInfo = getUserInfo($userId);
    
    if (!$userInfo) {
        $error = '获取用户信息失败';
    } else {
        $file = $_FILES['avatar'];
        
        // 检查文件是否上传成功
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = '头像文件过大，请选择小于2MB的文件';
                    break;
                default:
                    $error = '文件上传失败，请稍后重试';
                    break;
            }
        } else {
            // 检查文件类型
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                $error = '只支持JPEG、PNG和GIF格式的图片';
            } else {
                // 检查文件大小
                if ($file['size'] > 2 * 1024 * 1024) {
                    $error = '头像文件过大，请选择小于2MB的文件';
                } else {
                    // 生成唯一文件名
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $fileName = 'user_' . $userId . '_' . time() . '.' . $extension;
                    $uploadPath = 'asstes/UrseIcon/' . $fileName;
                    
                    // 确保目录存在
                    if (!is_dir('asstes/UrseIcon')) {
                        mkdir('asstes/UrseIcon', 0777, true);
                    }
                    
                    // 移动上传文件
                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        // 更新数据库中的头像路径
                        try {
                            $db = getDB();
                            $stmt = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                            $stmt->execute([$fileName, $userId]);
                            
                            $success = '头像上传成功';
                        } catch (PDOException $e) {
                            // 删除上传的文件
                            if (file_exists($uploadPath)) {
                                unlink($uploadPath);
                            }
                            $error = '更新头像信息失败，请稍后重试';
                        }
                    } else {
                        $error = '文件保存失败，请稍后重试';
                    }
                }
            }
        }
    }
}

// 获取用户当前头像
$userInfo = getUserInfo($userId);
$currentAvatar = $userInfo['avatar'] ?? 'BeginUrse.jfif';
$avatarPath = 'asstes/UrseIcon/' . $currentAvatar;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>上传头像 - 班级网站</title>
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
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #007bff;
        }
        
        .avatar-preview {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .avatar-display {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #007bff;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f8f9fa;
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
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-right: 10px;
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
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .form-actions {
            margin-top: 30px;
            text-align: center;
        }
        
        .hint {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        @media screen and (max-width: 768px) {
            .container {
                margin: 30px 20px;
                padding: 20px;
            }
            
            .avatar-display {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>上传头像</h1>
        
        <?php if ($error): ?>
            <div class="error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <div class="avatar-preview">
            <img src="<?php echo $avatarPath; ?>" alt="当前头像" class="avatar-display">
            <p>当前头像</p>
        </div>
        
        <div class="hint">
            <h3>上传提示</h3>
            <ul>
                <li>支持 JPEG、PNG、GIF 格式的图片</li>
                <li>文件大小不超过 2MB</li>
                <li>建议使用正方形图片，以便更好地显示</li>
            </ul>
        </div>
        
        <form method="POST" action="avatar_upload.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="avatar">选择新头像</label>
                <input type="file" id="avatar" name="avatar" accept="image/*" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn">上传头像</button>
                <a href="user_home.php" class="btn btn-secondary">取消</a>
            </div>
        </form>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>