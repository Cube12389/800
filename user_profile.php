<?php
// 包含配置文件
require_once 'config.php';

// 检查用户是否登录
requireLogin();

$userId = getCurrentUserId();
$error = '';
$success = '';

// 获取用户信息
function getUserInfo($userId) {
    try {
        $db = getDB();
        
        // 获取用户基本信息
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // 获取用户详细信息
        $stmt = $db->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
        
        return [
            'user' => $user,
            'profile' => $profile
        ];
    } catch (PDOException $e) {
        return false;
    }
}

// 获取用户信息
$userInfo = getUserInfo($userId);
if (!$userInfo) {
    $error = '获取用户信息失败';
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        // 获取表单数据
        $name = $_POST['name'] ?? '';
        $bio = $_POST['bio'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        
        try {
            $db = getDB();
            
            // 开始事务
            $db->beginTransaction();
            
            // 更新用户表中的邮箱和手机号
            $stmt = $db->prepare("UPDATE users SET email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$email, $phone, $userId]);
            
            // 更新用户详细信息
            $stmt = $db->prepare("UPDATE user_profiles SET name = ?, bio = ? WHERE user_id = ?");
            $stmt->execute([$name, $bio, $userId]);
            
            // 提交事务
            $db->commit();
            
            $success = '用户信息更新成功';
            // 记录操作日志
            logUserAction('update_profile', "更新个人资料成功");
            
            // 重新获取用户信息
            $userInfo = getUserInfo($userId);
        } catch (PDOException $e) {
            // 回滚事务
            $db->rollBack();
            $error = '更新用户信息失败，请稍后重试';
        }
    }
    
    // 处理密码修改
    elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = '请输入当前密码、新密码和确认密码';
        } elseif ($newPassword !== $confirmPassword) {
            $error = '两次输入的新密码不一致';
        } else {
            try {
                $db = getDB();
                
                // 验证当前密码
                $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if (!verifyPassword($currentPassword, $user['password'])) {
                    $error = '当前密码错误';
                } else {
                    // 更新密码
                    $hashedPassword = hashPassword($newPassword);
                    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $userId]);
                    
                    $success = '密码修改成功';
                }
            } catch (PDOException $e) {
                $error = '修改密码失败，请稍后重试';
            }
        }
    }
    
    // 处理注销请求
    elseif (isset($_POST['action']) && $_POST['action'] === 'logout') {
        logout();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户中心 - 班级网站</title>
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
        
        h2 {
            margin-top: 40px;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="password"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="tel"]:focus,
        input[type="password"]:focus,
        textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
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
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .form-actions {
            margin-top: 30px;
        }
        
        .user-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 30px;
        }
        
        .user-info h3 {
            margin-bottom: 15px;
            color: #007bff;
        }
        
        .user-info p {
            margin-bottom: 10px;
        }
        
        .user-info .label {
            font-weight: bold;
            color: #495057;
        }
        
        @media screen and (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 20px;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            h2 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>用户中心</h1>
        
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
        
        <?php if ($userInfo): ?>
            <div class="user-info">
                <h3>当前用户信息</h3>
                <p><span class="label">用户ID：</span><?php echo $userId; ?></p>
                <p><span class="label">用户名：</span><?php echo $userInfo['user']['username']; ?></p>
                <p><span class="label">邮箱：</span><?php echo $userInfo['user']['email'] ?? '未设置'; ?></p>
                <p><span class="label">手机号：</span><?php echo $userInfo['user']['phone'] ?? '未设置'; ?></p>
                <p><span class="label">注册时间：</span><?php echo $userInfo['user']['created_at']; ?></p>
            </div>
            
            <h2>修改个人资料</h2>
            <form method="POST" action="user_profile.php">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label for="name">姓名</label>
                    <input type="text" id="name" name="name" value="<?php echo $userInfo['profile']['name'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="bio">个人简介</label>
                    <textarea id="bio" name="bio"><?php echo $userInfo['profile']['bio'] ?? ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="email">邮箱</label>
                    <input type="email" id="email" name="email" value="<?php echo $userInfo['user']['email'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">手机号</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo $userInfo['user']['phone'] ?? ''; ?>">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn">保存修改</button>
                </div>
            </form>
            
            <h2>修改密码</h2>
            <form method="POST" action="user_profile.php">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="current_password">当前密码</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">新密码</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">确认新密码</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn">修改密码</button>
                </div>
            </form>
            
            <h2>账号管理</h2>
            <form method="POST" action="user_profile.php" onsubmit="return confirm('确定要注销登录吗？');">
                <input type="hidden" name="action" value="logout">
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger">注销登录</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>