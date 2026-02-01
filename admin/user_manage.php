<?php
// 用户管理页面
require_once 'admin_config.php';

// 检查登录状态
requireAdminLogin();

// 获取当前管理员信息
$currentAdmin = getCurrentAdmin();

$error = '';
$success = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理更新用户信息
    if (isset($_POST['action']) && $_POST['action'] === 'update_user') {
        $userId = $_POST['user_id'];
        $className = $_POST['class_name'];
        $tagId = $_POST['tag_id'];
        
        try {
            $db = getDB();
            $db->beginTransaction();
            
            // 更新班内名称
            $stmt = $db->prepare("UPDATE users SET class_name = ? WHERE id = ?");
            $stmt->execute([$className, $userId]);
            
            // 检查用户是否已有班级成员标签
            $stmt = $db->prepare("SELECT id FROM user_tag_relations WHERE user_id = ? AND tag_id = ?");
            $stmt->execute([$userId, $tagId]);
            
            if (!$stmt->fetch()) {
                // 添加标签关联
                $stmt = $db->prepare("INSERT INTO user_tag_relations (user_id, tag_id) VALUES (?, ?)");
                $stmt->execute([$userId, $tagId]);
            }
            
            $db->commit();
            $success = '用户信息更新成功';
        } catch (PDOException $e) {
            $db->rollBack();
            $error = '更新用户信息失败: ' . $e->getMessage();
        }
    }
}

// 获取所有用户
function getAllUsers() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT u.id, u.username, u.class_name, u.email, u.phone, u.created_at, p.name FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id ORDER BY u.created_at DESC");
        $users = $stmt->fetchAll();
        
        // 获取每个用户的标签
        foreach ($users as &$user) {
            $stmt = $db->prepare("SELECT t.name FROM user_tag_relations utr JOIN user_tags t ON utr.tag_id = t.id WHERE utr.user_id = ?");
            $stmt->execute([$user['id']]);
            $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $user['tags'] = $tags;
        }
        
        return $users;
    } catch (PDOException $e) {
        return [];
    }
}

// 获取班级成员标签ID
function getClassMemberTagId() {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM user_tags WHERE name = ?");
        $stmt->execute(["班级成员"]);
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    } catch (PDOException $e) {
        return null;
    }
}

$users = getAllUsers();
$classMemberTagId = getClassMemberTagId();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <link rel="icon" href="../asstes/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - 班级网站</title>
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
                <li><a href="user_manage.php" class="active">用户管理</a></li>
                <li><a href="dynamic_manage.php">动态管理</a></li>
                <li><a href="sensitive_words.php">敏感词管理</a></li>
                <li><a href="logout.php">退出登录</a></li>
            </ul>
        </div>
        
        <!-- 主内容区 -->
        <div class="main-content">
            <div class="header">
                <h1>用户管理</h1>
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
            
            <!-- 用户列表 -->
            <div class="card">
                <h2>用户列表</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户名</th>
                            <th>姓名</th>
                            <th>班内名称</th>
                            <th>邮箱</th>
                            <th>手机号</th>
                            <th>标签</th>
                            <th>注册时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo $user['username']; ?></td>
                                <td><?php echo $user['name'] ?? '-'; ?></td>
                                <td><?php echo $user['class_name'] ?? '-'; ?></td>
                                <td><?php echo $user['email'] ?? '-'; ?></td>
                                <td><?php echo $user['phone'] ?? '-'; ?></td>
                                <td>
                                    <?php if (!empty($user['tags'])): ?>
                                        <?php foreach ($user['tags'] as $tag): ?>
                                            <span style="display: inline-block; background-color: #007bff; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; margin-right: 5px;"><?php echo $tag; ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $user['created_at']; ?></td>
                                <td>
                                    <!-- 编辑用户信息 -->
                                    <button type="button" class="btn" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['class_name'] ?? ''); ?>')">编辑</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- 编辑用户模态框 -->
    <div id="editUserModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: white; padding: 30px; border-radius: 8px; width: 400px; max-width: 90%;">
            <h3>编辑用户信息</h3>
            <form method="POST" action="user_manage.php">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="modal_user_id">
                <input type="hidden" name="tag_id" value="<?php echo $classMemberTagId; ?>">
                
                <div style="margin-bottom: 20px;">
                    <label for="modal_class_name" style="display: block; margin-bottom: 8px; font-weight: bold;">班内名称</label>
                    <input type="text" id="modal_class_name" name="class_name" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div style="margin-top: 30px; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeModal()" style="padding: 8px 16px; background-color: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">取消</button>
                    <button type="submit" style="padding: 8px 16px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">保存</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editUser(userId, className) {
            document.getElementById('modal_user_id').value = userId;
            document.getElementById('modal_class_name').value = className || '';
            document.getElementById('editUserModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }
        
        // 点击模态框外部关闭
        window.onclick = function(event) {
            var modal = document.getElementById('editUserModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>