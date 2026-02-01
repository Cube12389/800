<?php
// 管理员账号管理页面
require_once 'admin_config.php';

// 检查登录状态
requireAdminLogin();

// 获取当前管理员信息
$currentAdmin = getCurrentAdmin();

// 检查是否为超级管理员
if (!$currentAdmin['is_super']) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理创建管理员
    if (isset($_POST['action']) && $_POST['action'] === 'create_admin') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($username) || empty($password) || empty($confirmPassword)) {
            $error = '请填写所有字段';
        } elseif ($password !== $confirmPassword) {
            $error = '两次输入的密码不一致';
        } else {
            try {
                $db = getDB();
                
                // 检查用户名是否已存在
                $stmt = $db->prepare("SELECT id FROM admins WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->rowCount() > 0) {
                    $error = '用户名已存在';
                } else {
                    // 创建管理员
                    $passwordHash = hashPassword($password);
                    $stmt = $db->prepare("INSERT INTO admins (username, password, is_super) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $passwordHash, 0]); // 默认为普通管理员
                    $success = '管理员创建成功';
                }
            } catch (PDOException $e) {
                $error = '创建管理员失败: ' . $e->getMessage();
            }
        }
    }
    
    // 处理删除管理员
    if (isset($_POST['action']) && $_POST['action'] === 'delete_admin') {
        $adminId = $_POST['admin_id'] ?? '';
        
        if (empty($adminId)) {
            $error = '请选择要删除的管理员';
        } else {
            try {
                $db = getDB();
                
                // 检查是否为超级管理员
                $stmt = $db->prepare("SELECT is_super FROM admins WHERE id = ?");
                $stmt->execute([$adminId]);
                $admin = $stmt->fetch();
                
                if ($admin && $admin['is_super']) {
                    $error = '超级管理员不可删除';
                } else {
                    // 删除管理员
                    $stmt = $db->prepare("DELETE FROM admins WHERE id = ? AND is_super = 0");
                    $result = $stmt->execute([$adminId]);
                    if ($result) {
                        $success = '管理员删除成功';
                    } else {
                        $error = '删除管理员失败';
                    }
                }
            } catch (PDOException $e) {
                $error = '删除管理员失败: ' . $e->getMessage();
            }
        }
    }
}

// 获取所有管理员
function getAllAdmins() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM admins ORDER BY is_super DESC, created_at DESC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

$admins = getAllAdmins();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <link rel="icon" href="../asstes/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员管理 - 班级网站</title>
    <style>
        /* 复用index.php的样式 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        /* 侧边栏 */
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: #fff;
            padding: 20px;
        }
        
        .sidebar h2 {
            margin-bottom: 30px;
            text-align: center;
            color: #007bff;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 10px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 15px;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        
        .sidebar-menu a:hover {
            background-color: #495057;
        }
        
        .sidebar-menu a.active {
            background-color: #007bff;
        }
        
        /* 主内容区 */
        .main-content {
            flex: 1;
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .header h1 {
            color: #343a40;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info span {
            margin-right: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #6c757d;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn:hover {
            background-color: #5a6268;
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-primary {
            background-color: #007bff;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
        }
        
        /* 卡片样式 */
        .card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .card h2 {
            margin-bottom: 20px;
            color: #007bff;
        }
        
        /* 表单样式 */
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        
        /* 表格样式 */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        /* 消息样式 */
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        /* 响应式设计 */
        @media screen and (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 10px;
            }
            
            .sidebar-menu {
                display: flex;
                overflow-x: auto;
            }
            
            .sidebar-menu li {
                margin-bottom: 0;
                margin-right: 10px;
            }
            
            .main-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 侧边栏 -->
        <div class="sidebar">
            <h2>管理员中心</h2>
            <ul class="sidebar-menu">
                <li><a href="index.php">首页</a></li>
                <li><a href="admin_manage.php" class="active">管理员管理</a></li>
                <li><a href="user_manage.php">用户管理</a></li>
                <li><a href="dynamic_manage.php">动态管理</a></li>
                <li><a href="sensitive_words.php">敏感词管理</a></li>
                <li><a href="logout.php">退出登录</a></li>
            </ul>
        </div>
        
        <!-- 主内容区 -->
        <div class="main-content">
            <div class="header">
                <h1>管理员管理</h1>
                <div class="user-info">
                    <span>欢迎，<?php echo $currentAdmin['username']; ?> (超级管理员)</span>
                    <a href="logout.php" class="btn btn-danger">退出登录</a>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <!-- 创建管理员表单 -->
            <div class="card">
                <h2>创建管理员</h2>
                <form method="POST" action="admin_manage.php">
                    <input type="hidden" name="action" value="create_admin">
                    
                    <div class="form-group">
                        <label for="username">用户名</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">密码</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">确认密码</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">创建管理员</button>
                </form>
            </div>
            
            <!-- 管理员列表 -->
            <div class="card">
                <h2>管理员列表</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户名</th>
                            <th>类型</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo $admin['id']; ?></td>
                                <td><?php echo $admin['username']; ?></td>
                                <td><?php echo $admin['is_super'] ? '超级管理员' : '普通管理员'; ?></td>
                                <td><?php echo $admin['created_at']; ?></td>
                                <td>
                                    <?php if (!$admin['is_super']): ?>
                                        <form method="POST" action="admin_manage.php" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_admin">
                                            <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('确定要删除此管理员吗？');">删除</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #6c757d;">不可操作</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>