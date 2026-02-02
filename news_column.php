<?php
// 专栏首页
require_once 'news_column_config.php';

// 获取当前用户ID
$currentUserId = getCurrentUserId();

// 获取用户可见的新闻
$newsList = getVisibleNews($currentUserId);

// 获取当前用户加入的报社
$userNewspapers = [];
if ($currentUserId) {
    $userNewspapers = getUserNewspapers($currentUserId);
}

// 记录游客访问日志
if (!isLoggedIn()) {
    logUserAction('visit_news_column', "游客访问专栏页面");
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <link rel="icon" href="asstes/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>专栏 - 班级网站</title>
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
        
        .news-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .news-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .news-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #343a40;
            flex: 1;
        }
        
        .news-meta {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .newspaper-info {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        
        .newspaper-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 8px;
        }
        
        .news-time {
            font-size: 0.8rem;
        }
        
        .news-content {
            margin-bottom: 20px;
            line-height: 1.8;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .news-visibility {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .visibility-public {
            background-color: #d4edda;
            color: #155724;
        }
        
        .visibility-all_users {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .visibility-specific_users {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .visibility-exclude_users {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            text-align: center;
            margin-bottom: 30px;
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
            margin: 0 10px;
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
        
        .news-actions {
            margin-top: 20px;
            border-top: 1px solid #e9ecef;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .like-section {
            display: flex;
            align-items: center;
        }
        
        .like-button {
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }
        
        .like-button:hover {
            color: #007bff;
        }
        
        .like-count {
            margin-left: 5px;
        }
        
        .no-news {
            text-align: center;
            padding: 50px 0;
            color: #6c757d;
        }
        
        .newspaper-section {
            margin-bottom: 40px;
        }
        
        .newspaper-section h2 {
            color: #007bff;
            margin-bottom: 20px;
            font-size: 1.2rem;
        }
        
        .newspaper-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .newspaper-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: calc(33.333% - 14px);
            min-width: 200px;
            text-align: center;
        }
        
        .newspaper-card img {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }
        
        .newspaper-card h3 {
            font-size: 1rem;
            margin-bottom: 10px;
        }
        
        @media screen and (max-width: 768px) {
            .container {
                margin: 30px auto;
            }
            
            .news-card {
                padding: 20px;
            }
            
            .news-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .news-meta {
                margin-top: 10px;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .newspaper-info {
                margin-bottom: 5px;
            }
            
            .news-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .newspaper-card {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>专栏</h1>
        
        <!-- 操作按钮 -->
        <div class="action-buttons">
            <!-- 班级相册入口 - 所有用户可见 -->
            <a href="class_album.php" class="btn btn-success">班级相册</a>
            
            <?php if (isLoggedIn()): ?>
                <?php if (empty($userNewspapers)): ?>
                    <a href="create_newspaper.php" class="btn">创建报社</a>
                <?php else: ?>
                    <a href="create_news.php" class="btn">发布新闻</a>
                    <a href="my_newspapers.php" class="btn btn-secondary">我的报社</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- 我的报社 -->
        <?php if (isLoggedIn() && !empty($userNewspapers)): ?>
            <div class="newspaper-section">
                <h2>我的报社</h2>
                <div class="newspaper-list">
                    <?php foreach ($userNewspapers as $newspaper): ?>
                        <div class="newspaper-card">
                            <img src="asstes/UrseIcon/<?php echo htmlspecialchars($newspaper['avatar']); ?>" alt="报社头像">
                            <h3><?php echo htmlspecialchars($newspaper['name']); ?></h3>
                            <a href="newspaper_home.php?id=<?php echo $newspaper['id']; ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">查看主页</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- 新闻列表 -->
        <?php if (count($newsList) > 0): ?>
            <?php foreach ($newsList as $news): ?>
                <div class="news-card">
                    <div class="news-header">
                        <h2 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h2>
                        <span class="news-visibility visibility-<?php echo $news['visibility_type']; ?>">
                            <?php
                                switch ($news['visibility_type']) {
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
                        </span>
                    </div>
                    
                    <div class="news-meta">
                        <div class="newspaper-info">
                            <img src="asstes/UrseIcon/<?php echo htmlspecialchars($news['newspaper_avatar']); ?>" alt="报社头像" class="newspaper-avatar">
                            <span><?php echo htmlspecialchars($news['newspaper_name']); ?></span>
                        </div>
                        <span class="news-time">发布时间：<?php echo $news['created_at']; ?></span>
                    </div>
                    
                    <div class="news-content">
                        <?php echo strip_tags($news['content_html']); ?>
                    </div>
                    
                    <div class="news-actions">
                        <a href="news_detail.php?id=<?php echo $news['id']; ?>" class="btn">查看详情</a>
                        <div class="like-section">
                            <button class="like-button" onclick="likeNews(<?php echo $news['id']; ?>)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-heart" viewBox="0 0 16 16">
                                    <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.882.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01L8 2.748zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143c.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15z"/>
                                </svg>
                                <span class="like-count"><?php echo getNewsLikeCount($news['id']); ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-news">
                <h3>暂无新闻</h3>
                <?php if (isLoggedIn()): ?>
                    <p>快来创建报社并发布第一条新闻吧！</p>
                <?php else: ?>
                    <p>登录后查看更多新闻</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script>
        function likeNews(newsId) {
            // 这里可以添加AJAX点赞功能
            alert('点赞功能开发中');
        }
    </script>
</body>
</html>