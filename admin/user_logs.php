<?php
// 用户操作日志管理页面
require_once 'admin_config.php';

// 检查登录状态
requireAdminLogin();

// 获取当前管理员信息
$currentAdmin = getCurrentAdmin();

// 获取分页参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$limit = 20;
$offset = ($page - 1) * $limit;

// 获取用户操作日志总数
$totalLogs = getAllUserLogsCount();
$totalPages = ceil($totalLogs / $limit);

// 获取用户操作日志列表
$logs = getAllUserLogs($limit, $offset);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <link rel="icon" href="../asstes/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户操作日志 - 班级网站</title>
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
        
        /* 分页样式 */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 30px;
            gap: 10px;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 16px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-decoration: none;
            color: #007bff;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            background-color: #e9ecef;
        }
        
        .pagination .active {
            background-color: #007bff;
            color: #fff;
            border-color: #007bff;
        }
        
        .pagination .disabled {
            color: #6c757d;
            pointer-events: none;
            border-color: #dee2e6;
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
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 8px;
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
                <li><a href="dynamic_manage.php">动态管理</a></li>
                <li><a href="sensitive_words.php">敏感词管理</a></li>
                <li><a href="admin_logs.php">操作日志</a></li>
                <li><a href="user_logs.php" class="active">用户日志</a></li>
                <li><a href="logout.php">退出登录</a></li>
            </ul>
        </div>
        
        <!-- 主内容区 -->
        <div class="main-content">
            <div class="header">
                <h1>用户操作日志</h1>
                <div class="user-info">
                    <span>欢迎，<?php echo $currentAdmin['username']; ?> <?php if ($currentAdmin['is_super']) echo '(超级管理员)'; ?></span>
                    <a href="logout.php" class="btn btn-danger">退出登录</a>
                </div>
            </div>
            
            <!-- 用户操作日志列表 -->
            <div class="card">
                <h2>用户操作日志列表</h2>
                <p>共 <?php echo $totalLogs; ?> 条用户操作记录</p>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户</th>
                            <th>操作类型</th>
                            <th>操作描述</th>
                            <th>IP地址</th>
                            <th>操作时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) > 0): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['id']; ?></td>
                                    <td>
                                        <?php if ($log['operation_type'] === 'guest'): ?>
                                            <span style="color: #6c757d;">游客</span>
                                        <?php else: ?>
                                            <?php echo $log['username'] ?? '未知用户'; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $log['action']; ?></td>
                                    <td><?php echo $log['description']; ?></td>
                                    <td><?php echo $log['ip_address']; ?></td>
                                    <td><?php echo $log['created_at']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px;">
                                    <h3>暂无用户操作日志</h3>
                                    <p>还没有任何用户操作记录</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- 分页 -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="user_logs.php?page=<?php echo $page - 1; ?>">上一页</a>
                        <?php else: ?>
                            <span class="disabled">上一页</span>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="user_logs.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="user_logs.php?page=<?php echo $page + 1; ?>">下一页</a>
                        <?php else: ?>
                            <span class="disabled">下一页</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>