<?php
// 用户邮件页面
require_once 'config.php';
require_once 'email_config.php';

requireLogin();

$userId = getCurrentUserId();
$error = '';
$success = '';

// 处理邀请
if (isset($_POST['action']) && $_POST['action'] === 'handle_invitation') {
    $invitationId = intval($_POST['invitation_id']);
    $actionType = $_POST['action_type'];
    
    // 处理邀请
    $result = handleInvitation($invitationId, $userId, $actionType);
    if ($result) {
        $success = $actionType === 'accept' ? '邀请接受成功' : '邀请拒绝成功';
    } else {
        $error = '处理邀请失败';
    }
}

// 处理删除邮件
if (isset($_POST['action']) && $_POST['action'] === 'delete_message') {
    $messageId = intval($_POST['message_id']);
    
    // 删除邮件
    $result = deleteMessage($messageId, $userId);
    if ($result) {
        $success = '邮件删除成功';
    } else {
        $error = '邮件删除失败';
    }
}

// 获取邮件列表
$status = isset($_GET['status']) ? $_GET['status'] : null;
$messages = getUserMessages($userId, $status);

// 获取未读邮件数量
$unreadCount = getUnreadMessageCount($userId);

// 获取用户的邀请
$invitations = getUserInvitations($userId);
$pendingInvitations = array_filter($invitations, function($inv) {
    return $inv['status'] === 'pending';
});

// 获取用户的申请
$applications = getUserApplications($userId);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>站内邮箱 - 班级网站</title>
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
            max-width: 1000px;
            margin: 50px auto;
            padding: 0 20px;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #007bff;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .tab {
            flex: 1;
            padding: 15px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        
        .tab:hover {
            background-color: #f8f9fa;
        }
        
        .tab.active {
            border-bottom-color: #007bff;
            color: #007bff;
            font-weight: bold;
        }
        
        .tab-count {
            background-color: #dc3545;
            color: #fff;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 0.8rem;
            margin-left: 5px;
        }
        
        .message-section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #343a40;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        
        .message-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        
        .message-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .message-card.unread {
            background-color: #e3f2fd;
            border-left-color: #2196f3;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .message-sender {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: bold;
        }
        
        .sender-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .message-subject {
            margin-bottom: 10px;
            font-size: 1.1rem;
            color: #343a40;
        }
        
        .message-content {
            margin-bottom: 15px;
            color: #6c757d;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .message-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .message-type {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
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
        
        .message-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
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
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .no-messages {
            text-align: center;
            padding: 40px 0;
            color: #6c757d;
        }
        
        .invitation-section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .invitation-card {
            background-color: #fff3cd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }
        
        .invitation-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .invitation-info {
            margin-bottom: 15px;
        }
        
        .invitation-actions {
            display: flex;
            gap: 10px;
        }
        
        .application-section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .application-card {
            background-color: #d1ecf1;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #17a2b8;
        }
        
        .application-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-accepted {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-declined {
            background-color: #f8d7da;
            color: #721c24;
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
        
        @media screen and (max-width: 768px) {
            .container {
                margin: 30px auto;
            }
            
            .message-section,
            .invitation-section,
            .application-section {
                padding: 20px;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .tab {
                text-align: left;
            }
            
            .message-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .message-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .message-actions {
                flex-wrap: wrap;
            }
            
            .invitation-header {
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
        <h1>站内邮箱</h1>
        
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
        
        <!-- 导航标签 -->
        <div class="tabs">
            <a href="user_messages.php" class="tab <?php if (!isset($_GET['tab'])) echo 'active'; ?>">
                收件箱 <?php if ($unreadCount > 0) echo '<span class="tab-count">' . $unreadCount . '</span>'; ?>
            </a>
            <a href="user_messages.php?tab=invitations" class="tab <?php if (isset($_GET['tab']) && $_GET['tab'] === 'invitations') echo 'active'; ?>">
                邀请 <?php if (count($pendingInvitations) > 0) echo '<span class="tab-count">' . count($pendingInvitations) . '</span>'; ?>
            </a>
            <a href="user_messages.php?tab=applications" class="tab <?php if (isset($_GET['tab']) && $_GET['tab'] === 'applications') echo 'active'; ?>">
                申请
            </a>
            <a href="user_messages.php?tab=sent" class="tab <?php if (isset($_GET['tab']) && $_GET['tab'] === 'sent') echo 'active'; ?>">
                已发送
            </a>
        </div>
        
        <!-- 邮件列表 -->
        <?php if (!isset($_GET['tab']) || $_GET['tab'] !== 'invitations' && $_GET['tab'] !== 'applications' && $_GET['tab'] !== 'sent'): ?>
            <div class="message-section">
                <h2 class="section-title">收件箱</h2>
                
                <div style="margin-bottom: 20px;">
                    <a href="user_messages.php" class="btn btn-sm <?php if (!isset($_GET['status'])) echo 'active'; ?>">全部</a>
                    <a href="user_messages.php?status=unread" class="btn btn-sm btn-secondary <?php if (isset($_GET['status']) && $_GET['status'] === 'unread') echo 'active'; ?>">未读</a>
                    <a href="user_messages.php?status=read" class="btn btn-sm btn-secondary <?php if (isset($_GET['status']) && $_GET['status'] === 'read') echo 'active'; ?>">已读</a>
                </div>
                
                <?php if (count($messages) > 0): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message-card <?php if ($message['message_status'] === 'unread') echo 'unread'; ?>">
                            <div class="message-header">
                                <div class="message-sender">
                                    <?php if ($message['sender_type'] === 'newspaper' && $message['sender_avatar']): ?>
                                        <img src="asstes/UrseIcon/<?php echo htmlspecialchars($message['sender_avatar']); ?>" alt="发件人头像" class="sender-avatar">
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($message['sender_name']); ?>
                                </div>
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
                            <div class="message-subject"><?php echo htmlspecialchars($message['subject']); ?></div>
                            <div class="message-content"><?php echo strip_tags($message['content']); ?></div>
                            <div class="message-meta">
                                <span><?php echo $message['created_at']; ?></span>
                                <span><?php echo $message['message_status'] === 'unread' ? '未读' : '已读'; ?></span>
                            </div>
                            <div class="message-actions">
                                <a href="message_detail.php?id=<?php echo $message['id']; ?>" class="btn">查看详情</a>
                                <form method="POST" action="user_messages.php" onsubmit="return confirm('确定要删除这封邮件吗？');">
                                    <input type="hidden" name="action" value="delete_message">
                                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                    <button type="submit" class="btn btn-danger">删除</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-messages">
                        <h3>暂无邮件</h3>
                        <p>你还没有收到任何邮件</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- 邀请列表 -->
        <?php if (isset($_GET['tab']) && $_GET['tab'] === 'invitations'): ?>
            <div class="invitation-section">
                <h2 class="section-title">邀请</h2>
                
                <?php if (count($invitations) > 0): ?>
                    <?php foreach ($invitations as $invitation): ?>
                        <div class="invitation-card">
                            <div class="invitation-header">
                                <h3>加入报社邀请</h3>
                                <span class="application-status status-<?php echo $invitation['status']; ?>">
                                    <?php
                                        switch ($invitation['status']) {
                                            case 'pending':
                                                echo '待处理';
                                                break;
                                            case 'accepted':
                                                echo '已接受';
                                                break;
                                            case 'declined':
                                                echo '已拒绝';
                                                break;
                                        }
                                    ?>
                                </span>
                            </div>
                            <div class="invitation-info">
                                <p>报社：<?php echo htmlspecialchars($invitation['newspaper_name']); ?></p>
                                <p>邀请人：<?php echo htmlspecialchars($invitation['inviter_name']); ?></p>
                                <p>邀请职位：<?php echo htmlspecialchars($invitation['role']); ?></p>
                                <p>邀请时间：<?php echo $invitation['created_at']; ?></p>
                            </div>
                            <?php if ($invitation['status'] === 'pending'): ?>
                                <div class="invitation-actions">
                                    <form method="POST" action="user_messages.php?tab=invitations">
                                        <input type="hidden" name="action" value="handle_invitation">
                                        <input type="hidden" name="invitation_id" value="<?php echo $invitation['id']; ?>">
                                        <input type="hidden" name="action_type" value="accept">
                                        <button type="submit" class="btn btn-success">接受</button>
                                    </form>
                                    <form method="POST" action="user_messages.php?tab=invitations">
                                        <input type="hidden" name="action" value="handle_invitation">
                                        <input type="hidden" name="invitation_id" value="<?php echo $invitation['id']; ?>">
                                        <input type="hidden" name="action_type" value="decline">
                                        <button type="submit" class="btn btn-danger">拒绝</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-messages">
                        <h3>暂无邀请</h3>
                        <p>你还没有收到任何邀请</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- 申请列表 -->
        <?php if (isset($_GET['tab']) && $_GET['tab'] === 'applications'): ?>
            <div class="application-section">
                <h2 class="section-title">申请</h2>
                
                <?php if (count($applications) > 0): ?>
                    <?php foreach ($applications as $application): ?>
                        <div class="application-card">
                            <div class="invitation-header">
                                <h3>加入报社申请</h3>
                                <span class="application-status status-<?php echo $application['status']; ?>">
                                    <?php
                                        switch ($application['status']) {
                                            case 'pending':
                                                echo '待处理';
                                                break;
                                            case 'accepted':
                                                echo '已接受';
                                                break;
                                            case 'declined':
                                                echo '已拒绝';
                                                break;
                                        }
                                    ?>
                                </span>
                            </div>
                            <div class="invitation-info">
                                <p>报社：<?php echo htmlspecialchars($application['newspaper_name']); ?></p>
                                <p>申请理由：<?php echo htmlspecialchars($application['message']); ?></p>
                                <p>申请时间：<?php echo $application['created_at']; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-messages">
                        <h3>暂无申请</h3>
                        <p>你还没有提交任何申请</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- 已发送邮件 -->
        <?php if (isset($_GET['tab']) && $_GET['tab'] === 'sent'): ?>
            <div class="message-section">
                <h2 class="section-title">已发送邮件</h2>
                
                <?php
                    $sentMessages = getUserSentMessages($userId);
                    if (count($sentMessages) > 0):
                ?>
                    <?php foreach ($sentMessages as $message): ?>
                        <div class="message-card">
                            <div class="message-header">
                                <div class="message-sender">
                                    收件人：<?php echo htmlspecialchars($message['receiver_name']); ?>
                                </div>
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
                            <div class="message-subject"><?php echo htmlspecialchars($message['subject']); ?></div>
                            <div class="message-content"><?php echo strip_tags($message['content']); ?></div>
                            <div class="message-meta">
                                <span><?php echo $message['created_at']; ?></span>
                            </div>
                            <div class="message-actions">
                                <a href="message_detail.php?id=<?php echo $message['id']; ?>" class="btn">查看详情</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-messages">
                        <h3>暂无已发送邮件</h3>
                        <p>你还没有发送任何邮件</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>