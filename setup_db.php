<?php
// 设置错误报告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 连接到MySQL服务器（使用root用户）
$servername = "localhost";
$username = "root";
$password = "124536";

try {
    // 创建数据库连接
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "连接成功<br>";
    
    // 创建数据库
    $sql = "CREATE DATABASE IF NOT EXISTS urse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "数据库创建成功<br>";
    
    // 选择数据库
    $conn->exec("USE urse");
    
    // 创建用户信息表
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20) UNIQUE,
        email VARCHAR(100) UNIQUE,
        token VARCHAR(255),
        token_expire DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "用户信息表创建成功<br>";
    
    // 创建基本信息表
    $sql = "CREATE TABLE IF NOT EXISTS user_profiles (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        name VARCHAR(100),
        avatar VARCHAR(255),
        bio TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "基本信息表创建成功<br>";
    
    // 创建权限表
    $sql = "CREATE TABLE IF NOT EXISTS user_permissions (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        permission VARCHAR(50) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY user_permission (user_id, permission)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "权限表创建成功<br>";
    
    echo "数据库设置完成！";
    
} catch(PDOException $e) {
    echo "错误: " . $e->getMessage();
} finally {
    // 关闭连接
    if (isset($conn)) {
        $conn = null;
    }
}
?>