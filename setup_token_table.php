<?php
// 创建用户token表
require_once 'config.php';

try {
    $db = getDB();
    
    // 创建用户token表
    $sql = "CREATE TABLE IF NOT EXISTS user_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(128) NOT NULL,
        device_info VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_token (token)
    )";
    
    $db->exec($sql);
    
    // 更新users表，移除旧的token和token_expire字段
    // 注意：如果字段不存在，这些语句会失败，但我们可以忽略这些错误
    // 因为这些字段的存在不会影响多设备登录功能的正常工作
    try {
        // 尝试移除token字段
        $stmt = $db->prepare("SHOW COLUMNS FROM users LIKE 'token'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $sql = "ALTER TABLE users DROP COLUMN token";
            $db->exec($sql);
        }
        
        // 尝试移除token_expire字段
        $stmt = $db->prepare("SHOW COLUMNS FROM users LIKE 'token_expire'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $sql = "ALTER TABLE users DROP COLUMN token_expire";
            $db->exec($sql);
        }
    } catch (PDOException $e) {
        // 忽略错误
    }
    
    echo "用户token表创建成功，users表更新成功！";
} catch (PDOException $e) {
    echo "创建表失败: " . $e->getMessage();
}
?>