<?php
// 创建用户操作日志表
require_once 'config.php';

try {
    $db = getDB();
    
    // 创建用户操作日志表
    $sql = "CREATE TABLE IF NOT EXISTS user_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    
    echo "用户操作日志表创建成功！";
} catch (PDOException $e) {
    echo "创建用户操作日志表失败：" . $e->getMessage();
}
?>