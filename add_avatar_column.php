<?php
// 添加avatar字段到users表
require_once 'config.php';

try {
    $db = getDB();
    
    // 检查avatar字段是否已存在
    $stmt = $db->prepare("SHOW COLUMNS FROM users LIKE 'avatar'");
    $stmt->execute();
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        // 添加avatar字段
        $stmt = $db->prepare("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT 'BeginUrse.jfif' AFTER username");
        $stmt->execute();
        echo "添加avatar字段成功"; 
    } else {
        echo "avatar字段已存在"; 
    }
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage();
}
?>