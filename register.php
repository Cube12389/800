<?php
// 包含配置文件
require_once 'config.php';

// 检查是否已经登录，如果是则重定向到首页
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

// 处理注册表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // 验证表单数据
    if (empty($username) || empty($password) || empty($confirmPassword)) {
        $error = '请输入用户名、密码和确认密码';
    } elseif ($password !== $confirmPassword) {
        $error = '两次输入的密码不一致';
    } elseif (empty($email) && empty($phone)) {
        $error = '请至少输入邮箱或手机号中的一项';
    } else {
        try {
            $db = getDB();
            
            // 检查用户名是否已存在
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                $error = '用户名已存在';
            } else {
                // 检查邮箱是否已存在（如果提供了邮箱）
                if (!empty($email)) {
                    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->rowCount() > 0) {
                        $error = '邮箱已被注册';
                    }
                }
                
                // 检查手机号是否已存在（如果提供了手机号）
                if (!empty($phone) && empty($error)) {
                    $stmt = $db->prepare("SELECT id FROM users WHERE phone = ?");
                    $stmt->execute([$phone]);
                    if ($stmt->rowCount() > 0) {
                        $error = '手机号已被注册';
                    }
                }
                
                // 如果没有错误，创建用户
                if (empty($error)) {
                    // 哈希密码
                    $hashedPassword = hashPassword($password);
                    
                    // 插入用户数据
                    $stmt = $db->prepare("INSERT INTO users (username, password, email, phone) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $hashedPassword, $email, $phone]);
                    
                    $userId = $db->lastInsertId();
                    
                    // 生成token
                    $token = generateToken($userId);
                    $expireTime = date('Y-m-d H:i:s', time() + TOKEN_EXPIRE_TIME);
                    
                    // 获取设备信息
                    $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
                    
                    // 将token存储到user_tokens表中
                    $stmt = $db->prepare("INSERT INTO user_tokens (user_id, token, device_info, expires_at) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$userId, $token, $deviceInfo, $expireTime]);
                    
                    // 创建用户基本信息记录
                    $stmt = $db->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
                    $stmt->execute([$userId]);
                    
                    // 创建默认权限
                    $stmt = $db->prepare("INSERT INTO user_permissions (user_id, permission) VALUES (?, ?)");
                    $stmt->execute([$userId, 'user']);
                    
                    // 设置cookie
                    setcookie('token', $token, time() + TOKEN_EXPIRE_TIME, '/', '', false, true);
                    
                    // 注册成功，重定向到首页
                    redirect('index.php');
                }
            }
        } catch (PDOException $e) {
            $error = '注册失败，请稍后重试';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <link rel="icon" href="asstes/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册 - 班级网站</title>
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
            max-width: 400px;
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="password"],
        input[type="email"],
        input[type="tel"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="email"]:focus,
        input[type="tel"]:focus {
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
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn:hover {
            background-color: #0069d9;
        }
        
        .link {
            text-align: center;
            margin-top: 20px;
        }
        
        .link a {
            color: #007bff;
            text-decoration: none;
        }
        
        .link a:hover {
            text-decoration: underline;
        }
        
        .hint {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        @media screen and (max-width: 480px) {
            .container {
                margin: 20px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>用户注册</h1>
        
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
        
        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">确认密码</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label for="email">邮箱（选填）</label>
                <input type="email" id="email" name="email">
                <div class="hint">用于找回密码和接收通知</div>
            </div>
            
            <div class="form-group">
                <label for="phone">手机号（选填）</label>
                <input type="tel" id="phone" name="phone">
                <div class="hint">用于找回密码和接收验证码</div>
            </div>
            
            <button type="submit" class="btn">注册</button>
        </form>
        
        <div class="link">
            <p>已有账号？ <a href="login.php">立即登录</a></p>
        </div>
    </div>
</body>
</html>