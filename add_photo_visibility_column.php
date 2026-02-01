<?php
// 为photos表添加可见性字段
require_once 'config.php';

try {
    $db = getDB();
    
    // 检查photos表是否存在visibility字段，如果不存在则添加
    $stmt = $db->query("SHOW COLUMNS FROM photos LIKE 'visibility'");
    if (!$stmt->fetch()) {
        $sql = "ALTER TABLE photos ADD COLUMN visibility VARCHAR(20) NOT NULL DEFAULT 'public' AFTER description";
        $db->exec($sql);
        echo "在photos表中添加visibility字段成功<br>";
    } else {
        echo "photos表中已存在visibility字段<br>";
    }
    
    echo "<br>操作完成！";
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage();
}
?>