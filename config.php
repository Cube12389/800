<?php
// 数据库配置
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'urse');
define('DB_PASSWORD', 'WncKxEIkqtvCCkeH');
define('DB_NAME', 'urse');

// Token配置
define('TOKEN_EXPIRE_TIME', 2592000); // Token过期时间（秒），30天
define('TOKEN_SECRET', 'your_secret_key_here'); // Token签名密钥

// 邮件配置（用于验证码发送）
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@example.com');
define('SMTP_PASSWORD', 'your_email_password');
define('FROM_EMAIL', 'no-reply@example.com');
define('FROM_NAME', '班级网站');

// 短信配置（用于验证码发送）
define('SMS_API_KEY', 'your_sms_api_key');
define('SMS_API_SECRET', 'your_sms_api_secret');

// 其他配置
define('SITE_URL', 'http://localhost');
define('MAX_LOGIN_ATTEMPTS', 5); // 最大登录尝试次数
define('LOCKOUT_DURATION', 300); // 登录失败锁定时间（秒）

// 数据库连接函数
function getDB() {
    $dbConnection = null;
    
    try {
        $dbConnection = new PDO(
            "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USERNAME,
            DB_PASSWORD
        );
        $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbConnection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        echo "数据库连接错误: " . $e->getMessage();
    }
    
    return $dbConnection;
}

// 生成随机字符串函数
function generateRandomString($length = 32) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// 生成Token函数
function generateToken($userId) {
    $payload = [
        'user_id' => $userId,
        'iat' => time(),
        'exp' => time() + TOKEN_EXPIRE_TIME,
        'jti' => substr(uniqid(), 0, 8) // 使用更短的唯一标识符
    ];
    
    // 使用md5作为签名算法，生成更短的签名（32字符）
    $base64Payload = base64_encode(json_encode($payload));
    $signature = md5(json_encode($payload) . TOKEN_SECRET);
    
    // 生成token
    $token = $base64Payload . '.' . $signature;
    
    // 确保token长度不超过128个字符
    if (strlen($token) > 128) {
        // 如果仍然太长，我们使用更紧凑的方式
        $shortPayload = [
            'u' => $userId, // 缩短字段名
            'i' => time(),
            'e' => time() + TOKEN_EXPIRE_TIME,
            'j' => substr(uniqid(), 0, 4)
        ];
        $base64Payload = base64_encode(json_encode($shortPayload));
        $signature = md5(json_encode($shortPayload) . TOKEN_SECRET);
        $token = $base64Payload . '.' . $signature;
    }
    
    return $token;
}

// 验证Token函数
function validateToken($token) {
    try {
        $parts = explode('.', $token);
        if (count($parts) != 2) {
            return false;
        }
        
        $payload = json_decode(base64_decode($parts[0]), true);
        $signature = $parts[1];
        
        // 验证签名
        $expectedSignature = md5(json_encode($payload) . TOKEN_SECRET);
        if ($signature !== $expectedSignature) {
            return false;
        }
        
        // 验证过期时间
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        // 处理短字段名的情况
        if (isset($payload['u'])) {
            // 转换短字段名为标准字段名
            return [
                'user_id' => $payload['u'],
                'iat' => $payload['i'],
                'exp' => $payload['e'],
                'jti' => $payload['j']
            ];
        }
        
        return $payload;
    } catch (Exception $e) {
        return false;
    }
}

// 密码哈希函数
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// 验证密码函数
function verifyPassword($password, $hashedPassword) {
    return password_verify($password, $hashedPassword);
}

// 重定向函数
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// 检查用户是否登录函数
function isLoggedIn() {
    if (isset($_COOKIE['token'])) {
        $token = $_COOKIE['token'];
        $payload = validateToken($token);
        
        if ($payload) {
            // 检查Token是否在数据库中存在且未过期
            $db = getDB();
            $stmt = $db->prepare("SELECT expires_at FROM user_tokens WHERE user_id = ? AND token = ?");
            $stmt->execute([$payload['user_id'], $token]);
            $tokenInfo = $stmt->fetch();
            
            if ($tokenInfo && strtotime($tokenInfo['expires_at']) > time()) {
                return true;
            }
        }
    }
    return false;
}

// 获取当前登录用户ID函数
function getCurrentUserId() {
    if (isset($_COOKIE['token'])) {
        $token = $_COOKIE['token'];
        $payload = validateToken($token);
        
        if ($payload) {
            return $payload['user_id'];
        }
    }
    return null;
}

// 获取用户权限函数
function getUserPermissions($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT permission FROM user_permissions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $permissions;
    } catch (PDOException $e) {
        return [];
    }
}

// 检查用户是否具有特定权限函数
function hasPermission($userId, $permission) {
    $permissions = getUserPermissions($userId);
    return in_array($permission, $permissions);
}

// 检查当前登录用户是否具有特定权限函数
function currentUserHasPermission($permission) {
    $userId = getCurrentUserId();
    if (!$userId) {
        return false;
    }
    return hasPermission($userId, $permission);
}

// 注销函数
function logout() {
    if (isset($_COOKIE['token'])) {
        // 从数据库中删除当前设备的token
        $token = $_COOKIE['token'];
        $payload = validateToken($token);
        
        if ($payload) {
            $db = getDB();
            $stmt = $db->prepare("DELETE FROM user_tokens WHERE user_id = ? AND token = ?");
            $stmt->execute([$payload['user_id'], $token]);
        }
        
        // 删除cookie
        setcookie('token', '', time() - 3600, '/', '', false, true);
    }
    
    redirect('login.php');
}

// 需要登录的中间件函数
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

// 需要特定权限的中间件函数
function requirePermission($permission) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    
    if (!currentUserHasPermission($permission)) {
        // 没有权限，重定向到首页或显示错误页面
        redirect('index.php');
    }
}
?>