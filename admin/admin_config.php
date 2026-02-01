<?php
// 管理员系统配置文件
require_once '../config.php';

// 检查管理员是否登录
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// 获取当前登录的管理员ID
function getCurrentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

// 获取当前登录的管理员信息
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([getCurrentAdminId()]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

// 管理员登录函数
function adminLogin($username, $password) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && verifyPassword($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

// 管理员注销函数
function adminLogout() {
    unset($_SESSION['admin_id']);
    session_destroy();
}

// 检查是否为超级管理员
function isSuperAdmin() {
    $admin = getCurrentAdmin();
    return $admin && $admin['is_super'] == 1;
}

// 需要管理员登录的中间件
function requireAdminLogin() {
    session_start();
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// 需要超级管理员权限的中间件
function requireSuperAdmin() {
    requireAdminLogin();
    if (!isSuperAdmin()) {
        header('Location: index.php');
        exit;
    }
}

// 敏感词管理函数
function getSensitiveWords() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM sensitive_words ORDER BY created_at DESC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function addSensitiveWord($word) {
    try {
        $db = getDB();
        
        // 检查敏感词是否已存在
        $stmt = $db->prepare("SELECT id FROM sensitive_words WHERE word = ?");
        $stmt->execute([$word]);
        if ($stmt->fetch()) {
            return false;
        }
        
        // 添加敏感词
        $stmt = $db->prepare("INSERT INTO sensitive_words (word) VALUES (?)");
        return $stmt->execute([$word]);
    } catch (PDOException $e) {
        return false;
    }
}

function deleteSensitiveWord($id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM sensitive_words WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}
?>