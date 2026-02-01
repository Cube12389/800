<?php
// 动态列表页面
require_once 'dynamic_config.php';

// 获取当前用户ID
$currentUserId = getCurrentUserId();

// 获取用户可见的动态
$dynamics = getVisibleDynamics($currentUserId);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <link rel="icon" href="asstes/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>动态 - 班级网站</title>
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
        
        .post-button {
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
        }
        
        .btn:hover {
            background-color: #0069d9;
        }
        
        .dynamic-actions {
            margin-top: 20px;
            border-top: 1px solid #e9ecef;
            padding-top: 20px;
        }
        
        .no-dynamics {
            text-align: center;
            padding: 50px 0;
            color: #6c757d;
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
        <h1>动态</h1>
        
        <!-- 发布动态按钮 -->
        <?php if (isLoggedIn()): ?>
            <div class="post-button">
                <a href="dynamic_post.php" class="btn">发布动态</a>
            </div>
        <?php endif; ?>
        
        <!-- 动态列表 -->
        <?php if (count($dynamics) > 0): ?>
            <?php foreach ($dynamics as $dynamic): ?>
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
                    
                    <div class="dynamic-actions">
                        <a href="dynamic_detail.php?id=<?php echo $dynamic['id']; ?>" class="btn">查看详情</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-dynamics">
                <h3>暂无动态</h3>
                <?php if (isLoggedIn()): ?>
                    <p>快来发布第一条动态吧！</p>
                <?php else: ?>
                    <p>登录后查看更多动态</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>