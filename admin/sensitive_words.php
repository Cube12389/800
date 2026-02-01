<?php
// 敏感词管理页面
require_once 'admin_config.php';

// 检查登录状态
requireAdminLogin();

// 获取当前管理员信息
$currentAdmin = getCurrentAdmin();

$error = '';
$success = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理添加敏感词
    if (isset($_POST['action']) && $_POST['action'] === 'add_word') {
        $word = $_POST['word'] ?? '';
        
        if (empty($word)) {
            $error = '请输入敏感词';
        } else {
            if (addSensitiveWord($word)) {
                $success = '敏感词添加成功';
            } else {
                $error = '敏感词添加失败，可能已存在';
            }
        }
    }
    
    // 处理批量导入敏感词
    if (isset($_POST['action']) && $_POST['action'] === 'import_words') {
        $wordsText = $_POST['words_text'] ?? '';
        
        if (empty($wordsText)) {
            $error = '请输入敏感词列表';
        } else {
            $words = explode("\n", $wordsText);
            $importCount = 0;
            
            foreach ($words as $word) {
                $word = trim($word);
                if (!empty($word)) {
                    if (addSensitiveWord($word)) {
                        $importCount++;
                    }
                }
            }
            
            $success = "成功导入 {$importCount} 个敏感词";
        }
    }
    
    // 处理删除敏感词
    if (isset($_POST['action']) && $_POST['action'] === 'delete_word') {
        $wordId = $_POST['word_id'] ?? '';
        
        if (empty($wordId)) {
            $error = '请选择要删除的敏感词';
        } else {
            if (deleteSensitiveWord($wordId)) {
                $success = '敏感词删除成功';
            } else {
                $error = '敏感词删除失败';
            }
        }
    }
}

// 获取所有敏感词
$sensitiveWords = getSensitiveWords();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <link rel="icon" href="../asstes/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>敏感词管理 - 班级网站</title>
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
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        textarea {
            resize: vertical;
            min-height: 200px;
        }
        
        input[type="text"]:focus,
        textarea:focus {
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
                <li><a href="admin_manage.php">管理员管理</a></li>
                <li><a href="user_manage.php">用户管理</a></li>
                <li><a href="dynamic_manage.php">动态管理</a></li>
                <li><a href="sensitive_words.php" class="active">敏感词管理</a></li>
                <li><a href="logout.php">退出登录</a></li>
            </ul>
        </div>
        
        <!-- 主内容区 -->
        <div class="main-content">
            <div class="header">
                <h1>敏感词管理</h1>
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
            
            <!-- 添加敏感词表单 -->
            <div class="card">
                <h2>添加敏感词</h2>
                <form method="POST" action="sensitive_words.php">
                    <input type="hidden" name="action" value="add_word">
                    
                    <div class="form-group">
                        <label for="word">敏感词</label>
                        <input type="text" id="word" name="word" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">添加敏感词</button>
                </form>
            </div>
            
            <!-- 批量导入敏感词表单 -->
            <div class="card">
                <h2>批量导入敏感词</h2>
                <form method="POST" action="sensitive_words.php">
                    <input type="hidden" name="action" value="import_words">
                    
                    <div class="form-group">
                        <label for="words_text">敏感词列表（每行一个）</label>
                        <textarea id="words_text" name="words_text" placeholder="请输入敏感词列表，每行一个"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">批量导入</button>
                </form>
            </div>
            
            <!-- 敏感词列表 -->
            <div class="card">
                <h2>敏感词列表</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>敏感词</th>
                            <th>添加时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sensitiveWords as $word): ?>
                            <tr>
                                <td><?php echo $word['id']; ?></td>
                                <td><?php echo htmlspecialchars($word['word']); ?></td>
                                <td><?php echo $word['created_at']; ?></td>
                                <td>
                                    <form method="POST" action="sensitive_words.php" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_word">
                                        <input type="hidden" name="word_id" value="<?php echo $word['id']; ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('确定要删除此敏感词吗？');">删除</button>
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