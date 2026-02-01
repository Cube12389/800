<?php
// 动态详情页面
require_once 'dynamic_config.php';

$error = '';
$success = '';
$dynamic = null;
$likeCount = 0;
$hasLiked = false;
$comments = [];

// 检查是否提供了动态ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $error = '动态ID不能为空';
} else {
    $dynamicId = $_GET['id'];
    $currentUserId = getCurrentUserId();
    
    // 处理点赞/取消点赞
    if (isset($_POST['action']) && $_POST['action'] === 'like' && $currentUserId) {
        if (likeDynamic($dynamicId, $currentUserId)) {
            $success = '点赞成功';
        } else {
            $error = '已经点赞过了';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'unlike' && $currentUserId) {
        if (unlikeDynamic($dynamicId, $currentUserId)) {
            $success = '取消点赞成功';
        } else {
            $error = '取消点赞失败';
        }
    }
    
    // 处理评论提交
    if (isset($_POST['action']) && $_POST['action'] === 'comment' && $currentUserId) {
        $content = $_POST['content'] ?? '';
        if (empty($content)) {
            $error = '评论内容不能为空';
        } else {
            if (addComment($dynamicId, $currentUserId, $content)) {
                $success = '评论成功';
            } else {
                $error = '评论失败，请稍后重试';
            }
        }
    }
    
    // 处理删除评论
    if (isset($_POST['action']) && $_POST['action'] === 'delete_comment' && $currentUserId && isset($_POST['comment_id'])) {
        $commentId = $_POST['comment_id'];
        if (deleteComment($commentId, $currentUserId)) {
            $success = '评论删除成功';
        } else {
            $error = '删除评论失败，无权限操作';
        }
    }
    
    // 获取动态详情
    function getDynamicById($id, $currentUserId) {
        try {
            $db = getDB();
            
            // 获取动态基本信息
            $stmt = $db->prepare("SELECT d.*, u.username FROM dynamics d JOIN users u ON d.user_id = u.id WHERE d.id = ?");
            $stmt->execute([$id]);
            $dynamic = $stmt->fetch();
            
            if (!$dynamic) {
                return false;
            }
            
            // 检查是否有权限查看
            if (!canUserViewDynamic($dynamic, $currentUserId)) {
                return false;
            }
            
            return $dynamic;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    $dynamic = getDynamicById($dynamicId, $currentUserId);
    
    if (!$dynamic) {
        $error = '动态不存在或无权限查看';
    } else {
        // 获取点赞数和点赞状态
        $likeCount = getDynamicLikeCount($dynamicId);
        if ($currentUserId) {
            $hasLiked = hasUserLiked($dynamicId, $currentUserId);
        }
        
        // 获取评论列表
        $comments = getDynamicComments($dynamicId);
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>动态详情 - 班级网站</title>
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
        
        .dynamic-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .dynamic-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .dynamic-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #343a40;
        }
        
        .dynamic-meta {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .dynamic-author {
            margin-right: 15px;
        }
        
        .dynamic-time {
            font-size: 0.8rem;
        }
        
        .dynamic-content {
            margin-bottom: 20px;
            line-height: 1.8;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .dynamic-content pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .dynamic-content h1, .dynamic-content h2, .dynamic-content h3 {
            margin: 20px 0 10px 0;
            color: #343a40;
        }
        
        .dynamic-content p {
            margin-bottom: 15px;
        }
        
        .dynamic-content ul {
            margin-left: 20px;
            margin-bottom: 15px;
        }
        
        .dynamic-content li {
            margin-bottom: 5px;
        }
        
        .dynamic-content pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            margin-bottom: 15px;
        }
        
        .dynamic-content code {
            font-family: 'Courier New', Courier, monospace;
        }
        
        .dynamic-visibility {
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
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-sm {
            padding: 4px 12px;
            font-size: 12px;
        }
        
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
        
        .dynamic-actions {
            margin-top: 30px;
            border-top: 1px solid #e9ecef;
            padding-top: 20px;
        }
        
        .interaction-section {
            margin-top: 30px;
            border-top: 1px solid #e9ecef;
            padding-top: 20px;
        }
        
        .like-section {
            margin-bottom: 30px;
        }
        
        .like-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .like-count {
            margin-left: 10px;
            font-weight: bold;
            color: #6c757d;
        }
        
        .comments-section {
            margin-top: 40px;
        }
        
        .comments-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #343a40;
        }
        
        .comment-form {
            margin-bottom: 30px;
        }
        
        .comment-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 10px;
        }
        
        .comment-form textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        
        .comment-list {
            margin-top: 30px;
        }
        
        .comment-item {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #007bff;
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
            margin-bottom: 10px;
            line-height: 1.5;
        }
        
        .comment-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .no-comments {
            text-align: center;
            padding: 40px 0;
            color: #6c757d;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        @media screen and (max-width: 768px) {
            .container {
                margin: 30px auto;
            }
            
            .dynamic-card {
                padding: 20px;
            }
            
            .dynamic-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .dynamic-meta {
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>动态详情</h1>
        
        <?php if ($error): ?>
            <div class="error">
                <?php echo $error; ?>
                <div style="margin-top: 20px;">
                    <a href="dynamic.php" class="btn btn-secondary">返回动态列表</a>
                </div>
            </div>
        <?php elseif ($dynamic): ?>
            <div class="dynamic-card">
                <div class="dynamic-header">
                    <h2 class="dynamic-title"><?php echo htmlspecialchars($dynamic['username']); ?>发表了动态</h2>
                    <span class="dynamic-visibility visibility-<?php echo $dynamic['visibility_type']; ?>">
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
                    </span>
                </div>
                
                <div class="dynamic-meta">
                    <span class="dynamic-author">作者：<?php echo htmlspecialchars($dynamic['username']); ?></span>
                    <span class="dynamic-time">发布时间：<?php echo $dynamic['created_at']; ?></span>
                </div>
                
                <div class="dynamic-content">
                    <?php echo $dynamic['content_html']; ?>
                </div>
                
                <!-- 交互部分 -->
                <div class="interaction-section">
                    <!-- 点赞部分 -->
                    <div class="like-section">
                        <?php if (isLoggedIn()): ?>
                            <?php if ($hasLiked): ?>
                                <form method="POST" action="dynamic_detail.php?id=<?php echo $dynamicId; ?>" style="display: inline;">
                                    <input type="hidden" name="action" value="unlike">
                                    <button type="submit" class="btn btn-secondary like-button">
                                        <span>取消点赞</span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="dynamic_detail.php?id=<?php echo $dynamicId; ?>" style="display: inline;">
                                    <input type="hidden" name="action" value="like">
                                    <button type="submit" class="btn btn-success like-button">
                                        <span>点赞</span>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <span class="like-count"><?php echo $likeCount; ?> 人点赞</span>
                        <?php else: ?>
                            <span class="like-count"><?php echo $likeCount; ?> 人点赞</span>
                            <span style="margin-left: 10px; color: #6c757d;">登录后可点赞</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 评论部分 -->
                    <div class="comments-section">
                        <h3 class="comments-title">评论 (<?php echo count($comments); ?>)</h3>
                        
                        <!-- 评论表单 -->
                        <?php if (isLoggedIn()): ?>
                            <div class="comment-form">
                                <form method="POST" action="dynamic_detail.php?id=<?php echo $dynamicId; ?>">
                                    <input type="hidden" name="action" value="comment">
                                    <div class="form-group">
                                        <label for="comment-content">发表评论</label>
                                        <textarea id="comment-content" name="content" placeholder="写下你的评论..." required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">发表评论</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <p style="margin-bottom: 20px; color: #6c757d;">登录后可发表评论</p>
                        <?php endif; ?>
                        
                        <!-- 评论列表 -->
                        <div class="comment-list">
                            <?php if (count($comments) > 0): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment-item">
                                        <div class="comment-header">
                                            <span class="comment-author"><?php echo htmlspecialchars($comment['username']); ?></span>
                                            <span class="comment-time"><?php echo $comment['created_at']; ?></span>
                                        </div>
                                        <div class="comment-content">
                                            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                        </div>
                                        <?php if (isLoggedIn() && $comment['user_id'] == $currentUserId): ?>
                                            <div class="comment-actions">
                                                <form method="POST" action="dynamic_detail.php?id=<?php echo $dynamicId; ?>" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_comment">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('确定要删除这条评论吗？');">删除</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-comments">
                                    <p>暂无评论，快来发表第一条评论吧！</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="dynamic-actions">
                    <a href="dynamic.php" class="btn btn-secondary">返回动态列表</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>