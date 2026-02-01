<?php
// 创建敏感词表
require_once 'config.php';

try {
    $db = getDB();
    
    // 创建敏感词表
    $sql = "CREATE TABLE IF NOT EXISTS sensitive_words (
        id INT AUTO_INCREMENT PRIMARY KEY,
        word VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "创建sensitive_words表成功<br>";
    
    echo "<br>操作完成！";
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage();
}
?>