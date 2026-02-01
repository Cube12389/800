<?php
// 创建班级相册相关的数据库表
require_once 'config.php';

try {
    $db = getDB();
    
    // 创建相册表
    $sql = "CREATE TABLE IF NOT EXISTS albums (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT NULL,
        cover_image VARCHAR(255) NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    echo "创建albums表成功<br>";
    
    // 创建照片表
    $sql = "CREATE TABLE IF NOT EXISTS photos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        album_id INT NOT NULL,
        filename VARCHAR(255) NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        description TEXT NULL,
        uploaded_by INT NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE,
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    echo "创建photos表成功<br>";
    
    // 创建默认的班级相册
    $stmt = $db->prepare("SELECT id FROM albums WHERE name = ?");
    $stmt->execute(["班级相册"]);
    if (!$stmt->fetch()) {
        // 获取第一个用户的ID作为创建者
        $stmt = $db->query("SELECT id FROM users LIMIT 1");
        $creatorId = $stmt->fetchColumn() ?: 1;
        
        $sql = "INSERT INTO albums (name, description, created_by) VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute(["班级相册", "班级成员共享相册", $creatorId]);
        echo "创建默认'班级相册'成功<br>";
    } else {
        echo "默认'班级相册'已存在<br>";
    }
    
    echo "<br>所有班级相册表结构创建成功！";
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage();
}
?>