<?php
// 管理员注销页面
require_once 'admin_config.php';

// 启动会话
session_start();

// 执行注销
adminLogout();

// 重定向到登录页面
header('Location: login.php');
exit;
?>