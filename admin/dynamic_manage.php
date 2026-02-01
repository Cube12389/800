<?php
// 动态管理页面
require_once 'admin_config.php';

// 检查登录状态
requireAdminLogin();

// 获取当前管理员信息
$currentAdmin = getCurrentAdmin();

$error = '';
$success = '';

// 获取所有动态
function getAllDynamics() {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT d.*, u.username FROM dynamics d JOIN users u ON d.user_id = u.id ORDER BY d.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// 删除动态
function deleteDynamic($id) {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // 删除相关的可见性设置
        $stmt = $db->prepare("DELETE FROM dynamic_visibility WHERE dynamic_id = ?");
        $stmt->execute([$id]);
        
        // 删除动态
        $stmt = $db->prepare("DELETE FROM dynamics WHERE id = ?");
        $stmt->execute([$id]);
        
        $db->commit();
        return true;
    } catch (PDOException $e) {
        if ($db) {
            $db->rollBack();
        }
        return false;
    }
}

// 处理删除动态
if (isset($_POST['action']) && $_POST['action'] === 'delete_dynamic') {
    $dynamicId = $_POST['dynamic_id'] ?? '';
    
    if (empty($dynamicId)) {
        $error = '请选择要删除的动态';
    } else {
        if (deleteDynamic($dynamicId)) {
            $success = '动态删除成功';
        } else {
            $error = '动态删除失败';
        }
    }
}

// 获取所有动态
$dynamics = getAllDynamics();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <link rel="icon" href="../asstes/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>动态管理 - 班级网站</title>
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
                <li><a href="admin_manage.php">管理员管理</a></li>
                <li><a href="user_manage.php">用户管理</a></li>
                <li><a href="dynamic_manage.php" class="active">动态管理</a></li>
                <li><a href="sensitive_words.php">敏感词管理</a></li>
                <li><a href="logout.php">退出登录</a></li>
            </ul>
        </div>
        
        <!-- 主内容区 -->
        <div class="main-content">
            <div class="header">
                <h1>动态管理</h1>
                <div class="user-info">
                    <span>欢迎，<?php echo $currentAdmin['username']; ?> <?php if ($currentAdmin['is_super']) echo '(超级管理员)'; ?></span>
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
            
            <!-- 动态列表 -->
            <div class="card">
                <h2>动态列表</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>标题</th>
                            <th>作者</th>
                            <th>可见范围</th>
                            <th>发布时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dynamics as $dynamic): ?>
                            <tr>
                                <td><?php echo $dynamic['id']; ?></td>
                                <td><?php echo htmlspecialchars($dynamic['title']); ?></td>
                                <td><?php echo $dynamic['username']; ?></td>
                                <td>
                                    <?php
                                        switch ($dynamic['visibility_type']) {
                                            case 'public':
                                                echo '所有人可见';
                                                break;
                                            case 'all_users':
                                                echo '所有用户可见';
                                                break;
                                            case 'specific_users':
                                                echo '指定用户可见';
                                                break;
                                            case 'exclude_users':
                                                echo '部分用户不可见';
                                                break;
                                        }
                                    ?>
                                </td>
                                <td><?php echo $dynamic['created_at']; ?></td>
                                <td>
                                    <form method="POST" action="dynamic_manage.php" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_dynamic">
                                        <input type="hidden" name="dynamic_id" value="<?php echo $dynamic['id']; ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('确定要删除此动态吗？');">删除</button>
                                    </form>
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