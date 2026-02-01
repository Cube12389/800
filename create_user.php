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
    
    // 创建用户
    $sql = "CREATE USER IF NOT EXISTS 'urse'@'localhost' IDENTIFIED BY 'WncKxEIkqtvCCkeH'";
    $conn->exec($sql);
    echo "用户创建成功<br>";
    
    // 授予权限
    $sql = "GRANT ALL PRIVILEGES ON urse.* TO 'urse'@'localhost'";
    $conn->exec($sql);
    echo "权限授予成功<br>";
    
    // 刷新权限
    $sql = "FLUSH PRIVILEGES";
    $conn->exec($sql);
    echo "权限刷新成功<br>";
    
    echo "用户设置完成！";
    
} catch(PDOException $e) {
    echo "错误: " . $e->getMessage();
} finally {
    // 关闭连接
    if (isset($conn)) {
        $conn = null;
    }
}
?>