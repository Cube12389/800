<?php
// 测试多设备同时登录功能
require_once 'config.php';

echo "测试多设备同时登录功能\n";
echo "================================\n";

// 测试1: 检查user_tokens表是否存在
try {
    $db = getDB();
    $stmt = $db->query("SHOW TABLES LIKE 'user_tokens'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ 测试1通过: user_tokens表存在\n";
    } else {
        echo "❌ 测试1失败: user_tokens表不存在\n";
    }
} catch (PDOException $e) {
    echo "❌ 测试1失败: " . $e->getMessage() . "\n";
}

// 测试2: 检查users表是否已移除token和token_expire字段
try {
    $db = getDB();
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'token'");
    $tokenColumnExists = $stmt->rowCount() > 0;
    
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'token_expire'");
    $tokenExpireColumnExists = $stmt->rowCount() > 0;
    
    if (!$tokenColumnExists && !$tokenExpireColumnExists) {
        echo "✅ 测试2通过: users表已移除token和token_expire字段\n";
    } else {
        echo "❌ 测试2失败: users表仍包含token或token_expire字段\n";
    }
} catch (PDOException $e) {
    echo "❌ 测试2失败: " . $e->getMessage() . "\n";
}

// 测试3: 测试generateToken函数
try {
    $token = generateToken(1);
    if ($token && strlen($token) <= 128) {
        echo "✅ 测试3通过: generateToken函数正常工作，token长度为 " . strlen($token) . "\n";
    } else {
        echo "❌ 测试3失败: generateToken函数异常\n";
    }
} catch (Exception $e) {
    echo "❌ 测试3失败: " . $e->getMessage() . "\n";
}

echo "================================\n";
echo "测试完成！\n";
?>