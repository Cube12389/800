<?php
// 新闻详情页
require_once 'news_column_config.php';
require_once 'config.php';

// 获取新闻ID
if (!isset($_GET['id'])) {
    redirect('news_column.php');
}

$newsId = intval($_GET['id']);

// 获取新闻信息
$news = getNews($newsId);
if (!$news) {
    redirect('news_column.php');
}

// 获取当前用户ID
$currentUserId = getCurrentUserId();

// 检查用户是否可以查看新闻
if (!canUserViewNews($news, $currentUserId)) {
    redirect('news_column.php');
}

// 处理点赞
if (isset($_POST['like']) && $currentUserId) {
    likeNews($newsId, $currentUserId);
    redirect('news_detail.php?id=' . $newsId);
}

// 处理取消点赞
if (isset($_POST['unlike']) && $currentUserId) {
    unlikeNews($newsId, $currentUserId);
    redirect('news_detail.php?id=' . $newsId);
}

// 处理评论
if (isset($_POST['comment']) && $currentUserId) {
    $commentContent = $_POST['comment_content'] ?? '';
    if (!empty($commentContent)) {
        addNewsComment($newsId, $currentUserId, $commentContent);
        redirect('news_detail.php?id=' . $newsId);
    }
}

// 获取新闻的评论
$comments = getNewsComments($newsId);

// 检查用户是否已点赞
$userLiked = $currentUserId ? hasUserLikedNews($newsId, $currentUserId) : false;

// 获取点赞数
$likeCount = getNewsLikeCount($newsId);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($news['title']); ?> - 新闻详情</title>
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
        
        .news-detail {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin-bottom: 30px;
        }
        
        .news-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .news-title {
            font-size: 2rem;
            font-weight: bold;
            color: #343a40;
            margin-bottom: 20px;
        }
        
        .news-meta {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .newspaper-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .newspaper-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
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
        
        .news-content {
            margin-bottom: 40px;
            line-height: 1.8;
        }
        
        .news-content h1, .news-content h2, .news-content h3 {
            margin: 20px 0 10px 0;
            color: #343a40;
        }
        
        .news-content p {
            margin-bottom: 15px;
        }
        
        .news-content ul {
            margin-left: 20px;
            margin-bottom: 15px;
        }
        
        .news-content li {
            margin-bottom: 5px;
        }
        
        .news-content pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            margin-bottom: 15px;
        }
        
        .news-content code {
            font-family: 'Courier New', Courier, monospace;
        }
        
        .news-actions {
            margin-bottom: 40px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .like-section {
            display: flex;
            align-items: center;
        }
        
        .like-form {
            display: inline;
        }
        
        .like-button {
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 1rem;
            padding: 8px 16px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .like-button:hover {
            background-color: #f8f9fa;
            color: #007bff;
        }
        
        .like-button.liked {
            color: #dc3545;
        }
        
        .like-count {
            margin-left: 10px;
            font-weight: bold;
        }
        
        .comments-section {
            margin-top: 50px;
        }
        
        .comments-section h2 {
            color: #007bff;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        
        .comment-form {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .comment-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            resize: vertical;
            min-height: 120px;
            margin-bottom: 10px;
        }
        
        .comment-form button {
            padding: 8px 16px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .comment-form button:hover {
            background-color: #0069d9;
        }
        
        .comment {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .comment-author {
            font-weight: bold;
            color: #343a40;
        }
        
        .comment-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .comment-content {
            line-height: 1.6;
        }
        
        .no-comments {
            text-align: center;
            padding: 30px 0;
            color: #6c757d;
            background-color: #f8f9fa;
            border-radius: 4px;
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
        
        @media screen and (max-width: 768px) {
            .container {
                margin: 30px auto;
            }
            
            .news-detail {
                padding: 20px;
            }
            
            .news-title {
                font-size: 1.5rem;
            }
            
            .news-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
        <div class="news-detail">
            <div class="news-header">
                <h1 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h1>
                <div class="news-meta">
                    <div class="newspaper-info">
                        <img src="asstes/UrseIcon/<?php echo htmlspecialchars($news['newspaper_avatar']); ?>" alt="报社头像" class="newspaper-avatar">
                        <span><?php echo htmlspecialchars($news['newspaper_name']); ?></span>
                    </div>
                    <span class="news-time">发布时间：<?php echo $news['created_at']; ?></span>
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
            </div>
            
            <div class="news-content">
                <?php echo $news['content_html']; ?>
            </div>
            
            <div class="news-actions">
                <a href="news_column.php" class="btn btn-secondary">返回专栏</a>
                <div class="like-section">
                    <?php if ($currentUserId): ?>
                        <?php if ($userLiked): ?>
                            <form method="POST" class="like-form">
                                <button type="submit" name="unlike" class="like-button liked">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-heart-fill" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M8 1.314C12.438-3.248 23.534 4.735 8 15-7.534 4.736 3.562-3.248 8 1.314z"/>
                                    </svg>
                                    <span>取消点赞</span>
                                    <span class="like-count">(<?php echo $likeCount; ?>)</span>
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" class="like-form">
                                <button type="submit" name="like" class="like-button">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-heart" viewBox="0 0 16 16">
                                        <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.882.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01L8 2.748zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143c.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15z"/>
                                    </svg>
                                    <span>点赞</span>
                                    <span class="like-count">(<?php echo $likeCount; ?>)</span>
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="like-button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-heart" viewBox="0 0 16 16">
                                <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.882.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01L8 2.748zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143c.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15z"/>
                            </svg>
                            <span>点赞</span>
                            <span class="like-count">(<?php echo $likeCount; ?>)</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 评论区 -->
            <div class="comments-section">
                <h2>评论</h2>
                
                <!-- 评论表单 -->
                <?php if ($currentUserId): ?>
                    <div class="comment-form">
                        <form method="POST">
                            <textarea name="comment_content" placeholder="写下你的评论..." required></textarea>
                            <button type="submit" name="comment">发表评论</button>
                        </form>
                    </div>
                <?php endif; ?>
                
                <!-- 评论列表 -->
                <?php if (count($comments) > 0): ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment">
                            <div class="comment-header">
                                <span class="comment-author"><?php echo htmlspecialchars($comment['username']); ?></span>
                                <span class="comment-time"><?php echo $comment['created_at']; ?></span>
                            </div>
                            <div class="comment-content">
                                <?php echo htmlspecialchars($comment['content']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-comments">
                        <h3>暂无评论</h3>
                        <?php if ($currentUserId): ?>
                            <p>快来发表第一条评论吧！</p>
                        <?php else: ?>
                            <p>登录后查看和发表评论</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>