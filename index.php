<?php
// 包含配置文件
require_once 'config.php';

// 处理注销请求
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
}

// 引入main.php的内容
include 'main.php';
?>