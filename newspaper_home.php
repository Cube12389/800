<?php
// 报社主页
require_once 'news_column_config.php';

// 获取报社ID
if (!isset($_GET['id'])) {
    redirect('news_column.php');
}

$newspaperId = intval($_GET['id']);

// 获取报社信息
$newspaper = getNewspaper($newspaperId);
if (!$newspaper) {
    redirect('news_column.php');
}

// 获取当前用户ID
$currentUserId = getCurrentUserId();

// 检查用户是否是报社成员
$userRole = isNewspaperMember($newspaperId, $currentUserId);

// 获取报社的新闻
$newsList = getNewspaperNews($newspaperId, $currentUserId);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($newspaper['name']); ?> - 报社主页</title>
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
        
        .newspaper-header {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin-bottom: 30px;
            text-align: center;
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
            font-size: 2rem;
            font-weight: bold;
            color: #343a40;
            margin-bottom: 10px;
        }
        
        .newspaper-description {
            color: #6c757d;
            margin-bottom: 20px;
            line-height: 1.8;
        }
        
        .newspaper-actions {
            margin-top: 20px;
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
        
        h2 {
            margin-bottom: 30px;
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
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
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        @media screen and (max-width: 768px) {
            .container {
                margin: 30px auto;
            }
            
            .newspaper-header {
                padding: 20px;
            }
            
            .newspaper-avatar {
                width: 96px;
                height: 96px;
            }
            
            .newspaper-name {
                font-size: 1.5rem;
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
            
            .news-actions {
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
        <!-- 报社信息 -->
        <div class="newspaper-header">
            <img src="asstes/UrseIcon/<?php echo htmlspecialchars($newspaper['avatar']); ?>" alt="报社头像" class="newspaper-avatar">
            <h1 class="newspaper-name"><?php echo htmlspecialchars($newspaper['name']); ?></h1>
            <?php if ($newspaper['description']): ?>
                <p class="newspaper-description"><?php echo htmlspecialchars($newspaper['description']); ?></p>
            <?php endif; ?>
            <div class="newspaper-actions">
                <?php if ($userRole): ?>
                    <a href="create_news.php?newspaper_id=<?php echo $newspaperId; ?>" class="btn">发布新闻</a>
                    <?php if (canManageNewspaper($newspaperId, $currentUserId)): ?>
                        <a href="newspaper_settings.php?id=<?php echo $newspaperId; ?>" class="btn btn-secondary">报社设置</a>
                        <a href="invite_members.php?newspaper_id=<?php echo $newspaperId; ?>" class="btn btn-success">邀请成员</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="apply_join.php?newspaper_id=<?php echo $newspaperId; ?>" class="btn btn-success">申请加入</a>
                <?php endif; ?>
                <a href="news_column.php" class="btn btn-secondary">返回专栏</a>
            </div>
        </div>
        
        <!-- 报社新闻 -->
        <h2>发布的新闻</h2>
        <?php if (count($newsList) > 0): ?>
            <?php foreach ($newsList as $news): ?>
                <div class="news-card">
                    <div class="news-header">
                        <h3 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h3>
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
                <?php if ($userRole): ?>
                    <p>快来发布第一条新闻吧！</p>
                <?php else: ?>
                    <p>该报社还没有发布任何新闻</p>
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