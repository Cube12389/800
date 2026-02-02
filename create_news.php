<?php
// 发布新闻页面
require_once 'news_column_config.php';
require_once 'config.php';

// 检查用户是否登录
requireLogin();

// 获取当前用户ID
$currentUserId = getCurrentUserId();

// 获取用户加入的报社
$userNewspapers = getUserNewspapers($currentUserId);
if (empty($userNewspapers)) {
    redirect('create_newspaper.php');
}

// 获取默认报社ID
$defaultNewspaperId = isset($_GET['newspaper_id']) ? intval($_GET['newspaper_id']) : $userNewspapers[0]['id'];

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newspaperId = intval($_POST['newspaper_id']);
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $visibilityType = $_POST['visibility_type'] ?? 'public';
    $visibilityUsers = $_POST['visibility_users'] ?? [];
    
    // 检查用户是否是报社成员
    if (!isNewspaperMember($newspaperId, $currentUserId)) {
        redirect('create_news.php');
    }
    
    // 创建新闻
    $newsId = createNews($newspaperId, $title, $content, $visibilityType, $visibilityUsers);
    if ($newsId) {
        // 记录操作日志
        logUserAction('create_news', "发布新闻成功，标题: {$title}");
        
        // 重定向到新闻详情页
        redirect('news_detail.php?id=' . $newsId);
    } else {
        $error = '发布失败，请重试';
    }
}

// 获取所有用户（用于可见范围选择）
function getAllUsers() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT id, username FROM users ORDER BY username");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

$allUsers = getAllUsers();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>发布新闻 - 班级网站</title>
    <link rel="stylesheet" href="css/navbar.css">
    <style>
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
            max-width: 800px;
            margin: 50px auto;
            padding: 0 20px;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #007bff;
        }
        
        .news-form {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #495057;
        }
        
        .form-group input[type="text"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 300px;
            font-family: 'Courier New', Courier, monospace;
        }
        
        .visibility-options {
            margin-top: 10px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .user-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .form-actions {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn:hover {
            background-color: #0069d9;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .error-message {
            color: #dc3545;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
        }
        
        .markdown-help {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        .markdown-help h3 {
            font-size: 1rem;
            margin-bottom: 10px;
        }
        
        .markdown-help ul {
            margin-left: 20px;
        }
        
        .markdown-help li {
            margin-bottom: 5px;
        }
        
        @media screen and (max-width: 768px) {
            .container {
                margin: 30px auto;
            }
            
            .news-form {
                padding: 20px;
            }
            
            .form-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>发布新闻</h1>
        
        <div class="news-form">
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="newspaper_id">选择报社</label>
                    <select id="newspaper_id" name="newspaper_id" required>
                        <?php foreach ($userNewspapers as $newspaper): ?>
                            <option value="<?php echo $newspaper['id']; ?>" <?php if ($newspaper['id'] == $defaultNewspaperId) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($newspaper['name']); ?> (<?php echo htmlspecialchars($newspaper['role']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="title">新闻标题</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="content">新闻内容（支持 Markdown 格式）</label>
                    <textarea id="content" name="content" required></textarea>
                    <div class="markdown-help">
                        <h3>Markdown 语法提示：</h3>
                        <ul>
                            <li># 一级标题</li>
                            <li>## 二级标题</li>
                            <li>### 三级标题</li>
                            <li>**粗体**</li>
                            <li>*斜体*</li>
                            <li>![图片描述](图片链接)</li>
                            <li>[链接文字](链接地址)</li>
                            <li>- 无序列表项</li>
                            <li>```代码块```</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="visibility_type">可见范围</label>
                    <select id="visibility_type" name="visibility_type" onchange="toggleVisibilityUsers()">
                        <option value="public">所有人可见</option>
                        <option value="all_users">所有用户可见</option>
                        <option value="specific_users">指定用户可见</option>
                        <option value="exclude_users">部分用户不可见</option>
                    </select>
                    
                    <div class="visibility-options" id="visibilityUsersContainer" style="display: none;">
                        <label>选择用户：</label>
                        <div class="user-list">
                            <?php foreach (getAllUsers() as $user): ?>
                                <div class="user-item">
                                    <input type="checkbox" name="visibility_users[]" value="<?php echo $user['id']; ?>">
                                    <label><?php echo htmlspecialchars($user['username']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="news_column.php" class="btn btn-secondary">取消</a>
                    <button type="submit" class="btn">发布新闻</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script>
        function toggleVisibilityUsers() {
            const visibilityType = document.getElementById('visibility_type').value;
            const container = document.getElementById('visibilityUsersContainer');
            
            if (visibilityType === 'specific_users' || visibilityType === 'exclude_users') {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }
        
        // 初始化
        toggleVisibilityUsers();
    </script>
</body>
</html>