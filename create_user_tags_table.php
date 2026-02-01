<?php
// 创建用户标签和班内名称相关的数据库表
require_once 'config.php';

try {
    $db = getDB();
    
    // 检查users表是否存在class_name字段，如果不存在则添加
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'class_name'");
    if (!$stmt->fetch()) {
        $sql = "ALTER TABLE users ADD COLUMN class_name VARCHAR(50) NULL DEFAULT NULL AFTER username";
        $db->exec($sql);
        echo "在users表中添加class_name字段成功<br>";
    } else {
        echo "users表中已存在class_name字段<br>";
    }
    
    // 创建用户标签表
    $sql = "CREATE TABLE IF NOT EXISTS user_tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "创建user_tags表成功<br>";
    
    // 创建用户-标签关联表
    $sql = "CREATE TABLE IF NOT EXISTS user_tag_relations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        tag_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES user_tags(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_tag (user_id, tag_id)
    )";
    $db->exec($sql);
    echo "创建user_tag_relations表成功<br>";
    
    // 检查是否存在"班级成员"标签，如果不存在则创建
    $stmt = $db->prepare("SELECT id FROM user_tags WHERE name = ?");
    $stmt->execute(["班级成员"]);
    if (!$stmt->fetch()) {
        $sql = "INSERT INTO user_tags (name, description) VALUES (?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute(["班级成员", "班级正式成员，拥有班级相册访问权限"]);
        echo "创建'班级成员'标签成功<br>";
    } else {
        echo "'班级成员'标签已存在<br>";
    }
    
    echo "<br>所有表结构创建成功！";
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage();
}
?>