<?php
// 检查avatar字段是否存在
require_once 'config.php';

try {
    $db = getDB();
    
    // 检查avatar字段是否已存在
    $stmt = $db->prepare("SHOW COLUMNS FROM users LIKE 'avatar'");
    $stmt->execute();
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "avatar字段已存在，默认值为: " . $columnExists['Default'];
    } else {
        echo "avatar字段不存在";
    }
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage();
}
?>