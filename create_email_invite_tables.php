<?php
// 创建站内邮箱功能和报社邀请/申请功能的数据库表结构
require_once 'config.php';

try {
    $db = getDB();
    
    // 创建邮件表
    $sql = "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_type ENUM('user', 'newspaper') DEFAULT 'user',
        sender_id INT NOT NULL,
        receiver_type ENUM('user', 'newspaper') DEFAULT 'user',
        receiver_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        message_type ENUM('normal', 'invitation', 'application', 'notification') DEFAULT 'normal',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "创建邮件表成功<br>";
    
    // 创建邮件状态表
    $sql = "CREATE TABLE IF NOT EXISTS message_status (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        user_id INT NOT NULL,
        status ENUM('unread', 'read', 'deleted') DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    echo "创建邮件状态表成功<br>";
    
    // 创建邮件回复表
    $sql = "CREATE TABLE IF NOT EXISTS message_replies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    echo "创建邮件回复表成功<br>";
    
    // 创建报社邀请表
    $sql = "CREATE TABLE IF NOT EXISTS newspaper_invitations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        newspaper_id INT NOT NULL,
        inviter_id INT NOT NULL,
        invitee_id INT NOT NULL,
        role ENUM('editor', 'reporter') DEFAULT 'reporter',
        status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (newspaper_id) REFERENCES newspapers(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    echo "创建报社邀请表成功<br>";
    
    // 创建报社申请表
    $sql = "CREATE TABLE IF NOT EXISTS newspaper_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        newspaper_id INT NOT NULL,
        applicant_id INT NOT NULL,
        message TEXT,
        status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (newspaper_id) REFERENCES newspapers(id) ON DELETE CASCADE
    )";
    $db->exec($sql);
    echo "创建报社申请表成功<br>";
    
    echo "<br>所有表创建成功！";
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage();
}
?>