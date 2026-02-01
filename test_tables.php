<?php
// 测试数据库表是否成功创建
require_once 'config.php';

try {
    $db = getDB();
    
    // 检查报社表
    $stmt = $db->query("SHOW TABLES LIKE 'newspapers'");
    $newspapersTable = $stmt->fetch();
    echo "报社表: " . ($newspapersTable ? "存在" : "不存在") . "<br>";
    
    // 检查新闻表
    $stmt = $db->query("SHOW TABLES LIKE 'news'");
    $newsTable = $stmt->fetch();
    echo "新闻表: " . ($newsTable ? "存在" : "不存在") . "<br>";
    
    // 检查报社成员表
    $stmt = $db->query("SHOW TABLES LIKE 'newspaper_members'");
    $membersTable = $stmt->fetch();
    echo "报社成员表: " . ($membersTable ? "存在" : "不存在") . "<br>";
    
    // 检查新闻可见性表
    $stmt = $db->query("SHOW TABLES LIKE 'news_visibility'");
    $visibilityTable = $stmt->fetch();
    echo "新闻可见性表: " . ($visibilityTable ? "存在" : "不存在") . "<br>";
    
    // 检查新闻评论表
    $stmt = $db->query("SHOW TABLES LIKE 'news_comments'");
    $commentsTable = $stmt->fetch();
    echo "新闻评论表: " . ($commentsTable ? "存在" : "不存在") . "<br>";
    
    // 检查新闻点赞表
    $stmt = $db->query("SHOW TABLES LIKE 'news_likes'");
    $likesTable = $stmt->fetch();
    echo "新闻点赞表: " . ($likesTable ? "存在" : "不存在") . "<br>";
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage();
}
?>