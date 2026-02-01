<?php
// 我的报社页面
require_once 'news_column_config.php';
require_once 'config.php';

// 检查用户是否登录
requireLogin();

// 获取当前用户ID
$currentUserId = getCurrentUserId();

// 获取用户加入的报社
$userNewspapers = getUserNewspapers($currentUserId);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的报社 - 班级网站</title>
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
        
        .newspaper-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .newspaper-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .newspaper-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        .newspaper-avatar {
            width: 128px;
            height: 128px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 4px solid #007bff;
        }
        
        .newspaper-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: #343a40;
            margin-bottom: 10px;
        }
        
        .newspaper-role {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            background-color: #d1ecf1;
            color: #0c5460;
            margin-bottom: 15px;
        }
        
        .newspaper-description {
            color: #6c757d;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .newspaper-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 14px;
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
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .no-newspapers {
            text-align: center;
            padding: 50px 0;
            color: #6c757d;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .action-buttons {
            margin-bottom: 40px;
            text-align: center;
        }
        
        @media screen and (max-width: 768px) {
            .container {
                margin: 30px auto;
            }
            
            .newspaper-list {
                grid-template-columns: 1fr;
            }
            
            .newspaper-card {
                padding: 20px;
            }
            
            .newspaper-avatar {
                width: 96px;
                height: 96px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>我的报社</h1>
        
        <div class="action-buttons">
            <a href="create_newspaper.php" class="btn btn-success">创建新报社</a>
            <a href="news_column.php" class="btn btn-secondary">返回专栏</a>
        </div>
        
        <?php if (count($userNewspapers) > 0): ?>
            <div class="newspaper-list">
                <?php foreach ($userNewspapers as $newspaper): ?>
                    <div class="newspaper-card">
                        <img src="asstes/UrseIcon/<?php echo htmlspecialchars($newspaper['avatar']); ?>" alt="报社头像" class="newspaper-avatar">
                        <h2 class="newspaper-name"><?php echo htmlspecialchars($newspaper['name']); ?></h2>
                        <span class="newspaper-role"><?php echo htmlspecialchars($newspaper['role']); ?></span>
                        <?php if ($newspaper['description']): ?>
                            <p class="newspaper-description"><?php echo htmlspecialchars($newspaper['description']); ?></p>
                        <?php endif; ?>
                        <div class="newspaper-actions">
                            <a href="newspaper_home.php?id=<?php echo $newspaper['id']; ?>" class="btn">查看主页</a>
                            <?php if (canManageNewspaper($newspaper['id'], $currentUserId)): ?>
                                <a href="newspaper_settings.php?id=<?php echo $newspaper['id']; ?>" class="btn btn-secondary">报社设置</a>
                            <?php endif; ?>
                            <a href="create_news.php?newspaper_id=<?php echo $newspaper['id']; ?>" class="btn">发布新闻</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-newspapers">
                <h3>暂无报社</h3>
                <p>你还没有加入任何报社，快来创建一个吧！</p>
                <a href="create_newspaper.php" class="btn btn-success">创建报社</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>