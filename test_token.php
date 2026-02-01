<?php
// 测试token生成和验证功能
require_once 'config.php';

echo "测试token生成和验证功能\n";
echo "================================\n";

// 测试1: 生成token
try {
    $userId = 1;
    $token = generateToken($userId);
    echo "✅ 测试1通过: 生成token成功\n";
    echo "   Token长度: " . strlen($token) . "\n";
    echo "   Token值: " . $token . "\n";
} catch (Exception $e) {
    echo "❌ 测试1失败: " . $e->getMessage() . "\n";
}

// 测试2: 验证token
try {
    $userId = 1;
    $token = generateToken($userId);
    $payload = validateToken($token);
    
    if ($payload && isset($payload['user_id']) && $payload['user_id'] == $userId) {
        echo "✅ 测试2通过: 验证token成功\n";
        echo "   User ID: " . $payload['user_id'] . "\n";
        echo "   Expire Time: " . date('Y-m-d H:i:s', $payload['exp']) . "\n";
    } else {
        echo "❌ 测试2失败: 验证token失败\n";
    }
} catch (Exception $e) {
    echo "❌ 测试2失败: " . $e->getMessage() . "\n";
}

// 测试3: 测试token存储到数据库
try {
    $userId = 1;
    $token = generateToken($userId);
    $expireTime = date('Y-m-d H:i:s', time() + TOKEN_EXPIRE_TIME);
    $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
    
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO user_tokens (user_id, token, device_info, expires_at) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$userId, $token, $deviceInfo, $expireTime]);
    
    if ($result) {
        echo "✅ 测试3通过: token存储到数据库成功\n";
        
        // 测试从数据库中读取并验证
        $stmt = $db->prepare("SELECT token FROM user_tokens WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        $storedToken = $stmt->fetchColumn();
        
        $payload = validateToken($storedToken);
        if ($payload && isset($payload['user_id']) && $payload['user_id'] == $userId) {
            echo "✅ 测试4通过: 从数据库读取并验证token成功\n";
        } else {
            echo "❌ 测试4失败: 从数据库读取并验证token失败\n";
        }
        
        // 清理测试数据
        $stmt = $db->prepare("DELETE FROM user_tokens WHERE user_id = ? AND token = ?");
        $stmt->execute([$userId, $token]);
    } else {
        echo "❌ 测试3失败: token存储到数据库失败\n";
    }
} catch (PDOException $e) {
    echo "❌ 测试3失败: " . $e->getMessage() . "\n";
}

echo "================================\n";
echo "测试完成！\n";
?>