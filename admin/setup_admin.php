<?php
// 包含配置文件
require_once '../config.php';

echo "正在设置管理员系统...\n";

try {
    $db = getDB();
    
    // 创建管理员表
    $sql = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        is_super TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "✅ 管理员表创建成功\n";
    
    // 检查初始账号是否存在
    $stmt = $db->prepare("SELECT id FROM admins WHERE username = 'root'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if (!$admin) {
        // 创建初始账号root
        $passwordHash = hashPassword('124536');
        $stmt = $db->prepare("INSERT INTO admins (username, password, is_super) VALUES (?, ?, ?)");
        $stmt->execute(['root', $passwordHash, 1]); // is_super=1表示超级管理员，不可被其他管理员管理
        echo "✅ 初始账号root创建成功\n";
    } else {
        echo "⚠️ 初始账号root已存在\n";
    }
    
    echo "管理员系统设置完成！\n";
    
} catch (PDOException $e) {
    echo "❌ 设置失败: " . $e->getMessage() . "\n";
}
?>