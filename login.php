<?php
// 包含配置文件
require_once 'config.php';

// 检查是否已经登录，如果是则重定向到首页
if (isLoggedIn()) {
    redirect('index.php');
}

// 检查是否存在未过期的token，如果有则自动登录
if (isset($_COOKIE['token'])) {
    $token = $_COOKIE['token'];
    $payload = validateToken($token);
    
    if ($payload) {
        $db = getDB();
        $stmt = $db->prepare("SELECT user_id FROM user_tokens WHERE user_id = ? AND token = ? AND expires_at > NOW()");
        $stmt->execute([$payload['user_id'], $token]);
        $tokenInfo = $stmt->fetch();
        
        if ($tokenInfo) {
            // 延长token过期时间
            $newExpireTime = date('Y-m-d H:i:s', time() + TOKEN_EXPIRE_TIME);
            $stmt = $db->prepare("UPDATE user_tokens SET expires_at = ? WHERE user_id = ? AND token = ?");
            $stmt->execute([$newExpireTime, $tokenInfo['user_id'], $token]);
            
            // 更新cookie过期时间
            setcookie('token', $token, time() + TOKEN_EXPIRE_TIME, '/', '', false, true);
            
            redirect('index.php');
        }
    }
}

$error = '';

// 处理登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $identifier = $_POST['identifier'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // 验证表单数据
    if (empty($identifier) || empty($password)) {
        $error = '请输入用户名/邮箱/手机号和密码';
    } else {
        try {
            $db = getDB();
            
            // 查找用户（通过用户名、邮箱或手机号）
            $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ? OR phone = ?");
            $stmt->execute([$identifier, $identifier, $identifier]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password'])) {
                // 生成新token
                $token = generateToken($user['id']);
                $expireTime = date('Y-m-d H:i:s', time() + TOKEN_EXPIRE_TIME);
                
                // 获取设备信息
                $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
                
                // 将token存储到user_tokens表中
                $stmt = $db->prepare("INSERT INTO user_tokens (user_id, token, device_info, expires_at) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user['id'], $token, $deviceInfo, $expireTime]);
                
                // 设置cookie
                setcookie('token', $token, time() + TOKEN_EXPIRE_TIME, '/', '', false, true);
                
                // 登录成功，重定向到首页
                redirect('index.php');
            } else {
                $error = '用户名/邮箱/手机号或密码错误';
            }
        } catch (PDOException $e) {
            $error = '登录失败，请稍后重试';
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
    <title>登录 - 班级网站</title>
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
            margin: 100px auto;
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
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
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
        
        @media screen and (max-width: 480px) {
            .container {
                margin: 50px 20px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>用户登录</h1>
        
        <?php if ($error): ?>
            <div class="error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="identifier">用户名/邮箱/手机号</label>
                <input type="text" id="identifier" name="identifier" required>
            </div>
            
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">登录</button>
        </form>
        
        <div class="link">
            <p>还没有账号？ <a href="register.php">立即注册</a></p>
        </div>
    </div>
</body>
</html>