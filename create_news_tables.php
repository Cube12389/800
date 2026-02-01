<?php
// 创建专栏功能数据库表结构
require_once 'config.php';

try {
    $db = getDB();
    
    // 创建报社表
    $sql = "CREATE TABLE IF NOT EXISTS newspapers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        avatar VARCHAR(255) DEFAULT 'BeginUrse.jfif',
        description TEXT,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    echo "创建报社表成功<br>";
    
    // 创建新闻表
    $sql = "CREATE TABLE IF NOT EXISTS news (
        id INT AUTO_INCREMENT PRIMARY KEY,
        newspaper_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        content_html TEXT,
        visibility_type ENUM('public', 'all_users', 'specific_users', 'exclude_users') DEFAULT 'public',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (newspaper_id) REFERENCES newspapers(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    echo "创建新闻表成功<br>";
    
    // 创建报社成员表
    $sql = "CREATE TABLE IF NOT EXISTS newspaper_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        newspaper_id INT NOT NULL,
        user_id INT NOT NULL,
        role ENUM('owner', 'editor', 'reporter') DEFAULT 'reporter',
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (newspaper_id) REFERENCES newspapers(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_newspaper_user (newspaper_id, user_id)
    )";
    $db->exec($sql);
    echo "创建报社成员表成功<br>";
    
    // 创建新闻可见性表
    $sql = "CREATE TABLE IF NOT EXISTS news_visibility (
        id INT AUTO_INCREMENT PRIMARY KEY,
        news_id INT NOT NULL,
        user_id INT NOT NULL,
        FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    echo "创建新闻可见性表成功<br>";
    
    // 创建新闻评论表
    $sql = "CREATE TABLE IF NOT EXISTS news_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        news_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    echo "创建新闻评论表成功<br>";
    
    // 创建新闻点赞表
    $sql = "CREATE TABLE IF NOT EXISTS news_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        news_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_news_user (news_id, user_id)
    )";
    $db->exec($sql);
    echo "创建新闻点赞表成功<br>";
    
    echo "<br>所有表创建成功！";
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage();
}
?>