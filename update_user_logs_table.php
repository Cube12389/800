<?php
// 更新用户操作日志表，支持游客操作记录
require_once 'config.php';

try {
    $db = getDB();
    
    // 添加操作类型字段，区分用户和游客
    $sql = "ALTER TABLE user_logs ADD COLUMN operation_type ENUM('user', 'guest') DEFAULT 'user' AFTER user_id";
    $db->exec($sql);
    
    echo "用户操作日志表更新成功！";
} catch (PDOException $e) {
    echo "更新用户操作日志表失败：" . $e->getMessage();
}
?>