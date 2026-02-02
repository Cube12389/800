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
        $result = $stmt->execute([$id]);
        
        // 记录操作日志
        if ($result) {
            logAdminAction('delete_sensitive_word', "删除敏感词 ID: {$id}");
        }
        
        return $result;
    } catch (PDOException $e) {
        return false;
    }
}

// 记录管理员操作日志
function logAdminAction($action, $description) {
    try {
        $db = getDB();
        $adminId = getCurrentAdminId();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '未知';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '未知';
        
        $stmt = $db->prepare("INSERT INTO admin_logs (admin_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$adminId, $action, $description, $ipAddress, $userAgent]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// 获取操作日志
function getAdminLogs($limit = 100, $offset = 0) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT al.*, a.username FROM admin_logs al LEFT JOIN admins a ON al.admin_id = a.id ORDER BY al.created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// 获取操作日志总数
function getAdminLogsCount() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT COUNT(*) FROM admin_logs");
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}
?>  