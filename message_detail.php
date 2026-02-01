<?php
// 邮件详情页面
require_once 'email_config.php';

// 获取邮件ID
if (!isset($_GET['id'])) {
    redirect('user_messages.php');
}

$messageId = intval($_GET['id']);

// 获取当前用户ID
$currentUserId = getCurrentUserId();

// 获取邮件详情
$messageDetail = getMessageDetail($messageId, $currentUserId);
if (!$messageDetail) {
    redirect('user_messages.php');
}

$message = $messageDetail['message'];
$replies = $messageDetail['replies'];

$error = '';
$success = '';

// 处理回复
if (isset($_POST['action']) && $_POST['action'] === 'reply') {
    $content = $_POST['content'];
    
    if (empty($content)) {
        $error = '回复内容不能为空';
    } else {
        // 回复邮件
        $result = replyMessage($messageId, $currentUserId, $content);
        if ($result) {
            $success = '回复成功';
            // 重新获取邮件详情
            $messageDetail = getMessageDetail($messageId, $currentUserId);
            $replies = $messageDetail['replies'];
        } else {
            $error = '回复失败，请稍后重试';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邮件详情 - 班级网站</title>
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
        
        .message-section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .message-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        .message-subject {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #343a40;
        }
        
        .message-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .message-content {
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        
        .message-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .type-normal {
            background-color: #d4edda;
            color: #155724;
        }
        
        .type-invitation {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .type-application {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .type-notification {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .reply-section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #343a40;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
            font-family: Arial, sans-serif;
            resize: vertical;
            min-height: 150px;
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
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .form-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .replies-section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .reply-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #6c757d;
        }
        
        .reply-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .reply-content {
            line-height: 1.6;
        }
        
        .no-replies {
            text-align: center;
            padding: 40px 0;
            color: #6c757d;
        }
        
        @media screen and (max-width: 768px) {
            .container {
                margin: 30px auto;
            }
            
            .message-section,
            .reply-section,
            .replies-section {
                padding: 20px;
            }
            
            .message-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>邮件详情</h1>
        
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
        
        <div class="message-section">
            <div class="message-header">
                <div class="message-subject"><?php echo htmlspecialchars($message['subject']); ?></div>
                <span class="message-type type-<?php echo $message['message_type']; ?>">
                    <?php
                        switch ($message['message_type']) {
                            case 'normal':
                                echo '普通邮件';
                                break;
                            case 'invitation':
                                echo '邀请';
                                break;
                            case 'application':
                                echo '申请';
                                break;
                            case 'notification':
                                echo '通知';
                                break;
                        }
                    ?>
                </span>
            </div>
            
            <div class="message-meta">
                <span>发件人：<?php echo htmlspecialchars($message['sender_name']); ?></span>
                <span><?php echo $message['created_at']; ?></span>
            </div>
            
            <div class="message-content">
                <?php echo nl2br(htmlspecialchars($message['content'])); ?>
            </div>
        </div>
        
        <div class="replies-section">
            <h2 class="section-title">回复</h2>
            
            <?php if (count($replies) > 0): ?>
                <?php foreach ($replies as $reply): ?>
                    <div class="reply-card">
                        <div class="reply-header">
                            <span>回复人：<?php echo htmlspecialchars($reply['username']); ?></span>
                            <span><?php echo $reply['created_at']; ?></span>
                        </div>
                        <div class="reply-content">
                            <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-replies">
                    <h3>暂无回复</h3>
                    <p>成为第一个回复的人吧！</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="reply-section">
            <h2 class="section-title">回复邮件</h2>
            <form method="POST" action="message_detail.php?id=<?php echo $messageId; ?>">
                <input type="hidden" name="action" value="reply">
                
                <div class="form-group">
                    <textarea name="content" placeholder="请输入回复内容..." required></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn">发送回复</button>
                    <a href="user_messages.php" class="btn btn-secondary">返回邮箱</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>