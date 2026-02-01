<?php
// 创建点赞和评论表
require_once 'config.php';

try {
    $db = getDB();
    
    // 创建点赞表
    $stmt = $db->prepare("
        CREATE TABLE IF NOT EXISTS dynamic_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dynamic_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_like (dynamic_id, user_id),
            FOREIGN KEY (dynamic_id) REFERENCES dynamics(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $stmt->execute();
    echo "创建点赞表成功\n";
    
    // 创建评论表
    $stmt = $db->prepare("
        CREATE TABLE IF NOT EXISTS dynamic_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dynamic_id INT NOT NULL,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (dynamic_id) REFERENCES dynamics(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $stmt->execute();
    echo "创建评论表成功\n";
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage();
}
?>