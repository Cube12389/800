<?php
// 动态功能初始化脚本
require_once 'config.php';

echo "正在设置动态功能...\n";

try {
    $db = getDB();
    
    // 创建动态表
    $sql = "CREATE TABLE IF NOT EXISTS dynamics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        content TEXT NOT NULL,
        content_html TEXT NOT NULL,
        visibility_type ENUM('public', 'all_users', 'specific_users', 'exclude_users') DEFAULT 'public',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    echo "✅ 动态表创建成功\n";
    
    // 创建动态可见范围表
    $sql = "CREATE TABLE IF NOT EXISTS dynamic_visibility (
        id INT AUTO_INCREMENT PRIMARY KEY,
        dynamic_id INT NOT NULL,
        user_id INT NOT NULL,
        FOREIGN KEY (dynamic_id) REFERENCES dynamics(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_dynamic_user (dynamic_id, user_id)
    )";
    $db->exec($sql);
    echo "✅ 动态可见范围表创建成功\n";
    
    // 创建敏感词表
    $sql = "CREATE TABLE IF NOT EXISTS sensitive_words (
        id INT AUTO_INCREMENT PRIMARY KEY,
        word VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "✅ 敏感词表创建成功\n";
    
    // 插入一些默认敏感词
    $defaultWords = ['敏感词1', '敏感词2', '敏感词3'];
    foreach ($defaultWords as $word) {
        try {
            $stmt = $db->prepare("INSERT IGNORE INTO sensitive_words (word) VALUES (?)");
            $stmt->execute([$word]);
        } catch (PDOException $e) {
            // 忽略重复插入错误
        }
    }
    echo "✅ 默认敏感词插入成功\n";
    
    echo "动态功能设置完成！\n";
    
} catch (PDOException $e) {
    echo "❌ 设置失败: " . $e->getMessage() . "\n";
}
?>