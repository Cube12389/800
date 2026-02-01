<?php
// 站内邮箱功能配置文件
require_once 'config.php';
require_once 'news_column_config.php';

// 发送邮件
function sendMessage($senderType, $senderId, $receiverType, $receiverId, $subject, $content, $messageType = 'normal') {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // 过滤敏感词
        $filteredContent = filterSensitiveWords($content);
        $filteredSubject = filterSensitiveWords($subject);
        
        // 插入邮件
        $stmt = $db->prepare("INSERT INTO messages (sender_type, sender_id, receiver_type, receiver_id, subject, content, message_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$senderType, $senderId, $receiverType, $receiverId, $filteredSubject, $filteredContent, $messageType]);
        
        $messageId = $db->lastInsertId();
        
        // 添加邮件状态
        if ($receiverType === 'user') {
            $stmt = $db->prepare("INSERT INTO message_status (message_id, user_id, status) VALUES (?, ?, 'unread')");
            $stmt->execute([$messageId, $receiverId]);
        } else if ($receiverType === 'newspaper') {
            // 对于报社，需要为所有成员添加状态
            $stmt = $db->prepare("SELECT user_id FROM newspaper_members WHERE newspaper_id = ?");
            $stmt->execute([$receiverId]);
            $members = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($members as $memberId) {
                $stmt = $db->prepare("INSERT INTO message_status (message_id, user_id, status) VALUES (?, ?, 'unread')");
                $stmt->execute([$messageId, $memberId]);
            }
        }
        
        $db->commit();
        return $messageId;
    } catch (PDOException $e) {
        $db->rollBack();
        return false;
    }
}

// 获取用户收到的邮件
function getUserMessages($userId, $status = null) {
    try {
        $db = getDB();
        
        $sql = "SELECT m.*, 
               CASE 
                   WHEN m.sender_type = 'user' THEN u.username 
                   WHEN m.sender_type = 'newspaper' THEN np.name 
               END as sender_name,
               CASE 
                   WHEN m.sender_type = 'newspaper' THEN np.avatar 
               END as sender_avatar,
               ms.status as message_status
               FROM messages m
               JOIN message_status ms ON m.id = ms.message_id
               LEFT JOIN users u ON m.sender_type = 'user' AND m.sender_id = u.id
               LEFT JOIN newspapers np ON m.sender_type = 'newspaper' AND m.sender_id = np.id
               WHERE ms.user_id = ?
               AND (m.receiver_type = 'user' AND m.receiver_id = ? OR m.receiver_type = 'newspaper' AND EXISTS (
                   SELECT 1 FROM newspaper_members nm WHERE nm.newspaper_id = m.receiver_id AND nm.user_id = ?
               ))
               ";
        
        if ($status) {
            $sql .= " AND ms.status = ?";
        }
        
        $sql .= " ORDER BY m.created_at DESC";
        
        $stmt = $db->prepare($sql);
        
        if ($status) {
            $stmt->execute([$userId, $userId, $userId, $status]);
        } else {
            $stmt->execute([$userId, $userId, $userId]);
        }
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// 获取邮件详情
function getMessageDetail($messageId, $userId) {
    try {
        $db = getDB();
        
        // 检查用户是否有权限查看该邮件
        $stmt = $db->prepare("SELECT * FROM message_status WHERE message_id = ? AND user_id = ?");
        $stmt->execute([$messageId, $userId]);
        $status = $stmt->fetch();
        
        if (!$status) {
            return false;
        }
        
        // 更新邮件状态为已读
        $stmt = $db->prepare("UPDATE message_status SET status = 'read' WHERE message_id = ? AND user_id = ?");
        $stmt->execute([$messageId, $userId]);
        
        // 获取邮件详情
        $stmt = $db->prepare("SELECT m.*, 
               CASE 
                   WHEN m.sender_type = 'user' THEN u.username 
                   WHEN m.sender_type = 'newspaper' THEN np.name 
               END as sender_name,
               CASE 
                   WHEN m.sender_type = 'user' THEN u.avatar 
                   WHEN m.sender_type = 'newspaper' THEN np.avatar 
               END as sender_avatar,
               ms.status as message_status
               FROM messages m
               JOIN message_status ms ON m.id = ms.message_id
               LEFT JOIN users u ON m.sender_type = 'user' AND m.sender_id = u.id
               LEFT JOIN newspapers np ON m.sender_type = 'newspaper' AND m.sender_id = np.id
               WHERE m.id = ? AND ms.user_id = ?");
        $stmt->execute([$messageId, $userId]);
        $message = $stmt->fetch();
        
        // 获取邮件回复
        $stmt = $db->prepare("SELECT r.*, u.username FROM message_replies r JOIN users u ON r.user_id = u.id WHERE r.message_id = ? ORDER BY r.created_at ASC");
        $stmt->execute([$messageId]);
        $replies = $stmt->fetchAll();
        
        return [
            'message' => $message,
            'replies' => $replies
        ];
    } catch (PDOException $e) {
        return false;
    }
}

// 回复邮件
function replyMessage($messageId, $userId, $content) {
    try {
        $db = getDB();
        
        // 检查用户是否有权限回复该邮件
        $stmt = $db->prepare("SELECT * FROM message_status WHERE message_id = ? AND user_id = ?");
        $stmt->execute([$messageId, $userId]);
        if (!$stmt->fetch()) {
            return false;
        }
        
        // 过滤敏感词
        $filteredContent = filterSensitiveWords($content);
        
        // 插入回复
        $stmt = $db->prepare("INSERT INTO message_replies (message_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$messageId, $userId, $filteredContent]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// 删除邮件
function deleteMessage($messageId, $userId) {
    try {
        $db = getDB();
        
        // 更新邮件状态为已删除
        $stmt = $db->prepare("UPDATE message_status SET status = 'deleted' WHERE message_id = ? AND user_id = ?");
        $stmt->execute([$messageId, $userId]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// 获取未读邮件数量
function getUnreadMessageCount($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM message_status WHERE user_id = ? AND status = 'unread'");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// 获取用户发送的邮件
function getUserSentMessages($userId) {
    try {
        $db = getDB();
        
        $sql = "SELECT m.*, 
               CASE 
                   WHEN m.receiver_type = 'user' THEN u.username 
                   WHEN m.receiver_type = 'newspaper' THEN np.name 
               END as receiver_name
               FROM messages m
               LEFT JOIN users u ON m.receiver_type = 'user' AND m.receiver_id = u.id
               LEFT JOIN newspapers np ON m.receiver_type = 'newspaper' AND m.receiver_id = np.id
               WHERE m.sender_type = 'user' AND m.sender_id = ?
               ORDER BY m.created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// 发送报社邀请邮件
function sendNewspaperInvitation($newspaperId, $inviterId, $inviteeId, $role) {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // 检查是否已经存在邀请
        $stmt = $db->prepare("SELECT id FROM newspaper_invitations WHERE newspaper_id = ? AND invitee_id = ? AND status = 'pending'");
        $stmt->execute([$newspaperId, $inviteeId]);
        if ($stmt->fetch()) {
            $db->rollBack();
            return false;
        }
        
        // 获取报社信息
        $newspaper = getNewspaper($newspaperId);
        if (!$newspaper) {
            $db->rollBack();
            return false;
        }
        
        // 插入邀请
        $stmt = $db->prepare("INSERT INTO newspaper_invitations (newspaper_id, inviter_id, invitee_id, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$newspaperId, $inviterId, $inviteeId, $role]);
        
        $invitationId = $db->lastInsertId();
        
        // 发送邀请邮件
        $subject = "邀请你加入报社 {$newspaper['name']}";
        $content = "你好！\n\n我邀请你加入报社 {$newspaper['name']}，担任 {$role} 角色。\n\n请登录系统查看并处理此邀请。";
        
        sendMessage('user', $inviterId, 'user', $inviteeId, $subject, $content, 'invitation');
        
        $db->commit();
        return $invitationId;
    } catch (PDOException $e) {
        $db->rollBack();
        return false;
    }
}

// 申请加入报社
function applyToNewspaper($newspaperId, $applicantId, $message) {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // 检查是否已经存在申请
        $stmt = $db->prepare("SELECT id FROM newspaper_applications WHERE newspaper_id = ? AND applicant_id = ? AND status = 'pending'");
        $stmt->execute([$newspaperId, $applicantId]);
        if ($stmt->fetch()) {
            $db->rollBack();
            return false;
        }
        
        // 检查是否已经是报社成员
        if (isNewspaperMember($newspaperId, $applicantId)) {
            $db->rollBack();
            return false;
        }
        
        // 获取报社信息
        $newspaper = getNewspaper($newspaperId);
        if (!$newspaper) {
            $db->rollBack();
            return false;
        }
        
        // 插入申请
        $stmt = $db->prepare("INSERT INTO newspaper_applications (newspaper_id, applicant_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$newspaperId, $applicantId, $message]);
        
        $applicationId = $db->lastInsertId();
        
        // 发送申请通知邮件给报社所有者
        $subject = "有人申请加入报社 {$newspaper['name']}";
        $content = "你好！\n\n有用户申请加入报社 {$newspaper['name']}。\n\n申请理由：{$message}\n\n请登录系统查看并处理此申请。";
        
        sendMessage('user', $applicantId, 'newspaper', $newspaperId, $subject, $content, 'application');
        
        $db->commit();
        return $applicationId;
    } catch (PDOException $e) {
        $db->rollBack();
        return false;
    }
}

// 获取用户的邀请
function getUserInvitations($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT ni.*, n.name as newspaper_name, n.avatar as newspaper_avatar, u.username as inviter_name FROM newspaper_invitations ni JOIN newspapers n ON ni.newspaper_id = n.id JOIN users u ON ni.inviter_id = u.id WHERE ni.invitee_id = ? ORDER BY ni.created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// 获取用户的申请
function getUserApplications($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT na.*, n.name as newspaper_name, n.avatar as newspaper_avatar FROM newspaper_applications na JOIN newspapers n ON na.newspaper_id = n.id WHERE na.applicant_id = ? ORDER BY na.created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// 获取报社的申请
function getNewspaperApplications($newspaperId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT na.*, u.username as applicant_name, u.avatar as applicant_avatar FROM newspaper_applications na JOIN users u ON na.applicant_id = u.id WHERE na.newspaper_id = ? AND na.status = 'pending' ORDER BY na.created_at DESC");
        $stmt->execute([$newspaperId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// 处理邀请
function handleInvitation($invitationId, $userId, $action) {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // 获取邀请信息
        $stmt = $db->prepare("SELECT * FROM newspaper_invitations WHERE id = ? AND invitee_id = ? AND status = 'pending'");
        $stmt->execute([$invitationId, $userId]);
        $invitation = $stmt->fetch();
        
        if (!$invitation) {
            $db->rollBack();
            return false;
        }
        
        // 更新邀请状态
        $stmt = $db->prepare("UPDATE newspaper_invitations SET status = ? WHERE id = ?");
        $stmt->execute([$action === 'accept' ? 'accepted' : 'declined', $invitationId]);
        
        // 如果接受邀请，添加到报社成员
        if ($action === 'accept') {
            $stmt = $db->prepare("INSERT INTO newspaper_members (newspaper_id, user_id, role) VALUES (?, ?, ?)");
            $stmt->execute([$invitation['newspaper_id'], $userId, $invitation['role']]);
            
            // 发送通知邮件
            $newspaper = getNewspaper($invitation['newspaper_id']);
            $subject = "邀请已被接受";
            $content = "你好！\n\n用户已接受你发出的加入报社 {$newspaper['name']} 的邀请。";
            
            sendMessage('user', $userId, 'user', $invitation['inviter_id'], $subject, $content, 'notification');
        }
        
        $db->commit();
        return true;
    } catch (PDOException $e) {
        $db->rollBack();
        return false;
    }
}

// 处理申请
function handleApplication($applicationId, $newspaperId, $userId, $action) {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // 检查用户是否有权限处理申请
        if (!canManageNewspaper($newspaperId, $userId)) {
            $db->rollBack();
            return false;
        }
        
        // 获取申请信息
        $stmt = $db->prepare("SELECT * FROM newspaper_applications WHERE id = ? AND newspaper_id = ? AND status = 'pending'");
        $stmt->execute([$applicationId, $newspaperId]);
        $application = $stmt->fetch();
        
        if (!$application) {
            $db->rollBack();
            return false;
        }
        
        // 更新申请状态
        $stmt = $db->prepare("UPDATE newspaper_applications SET status = ? WHERE id = ?");
        $stmt->execute([$action === 'accept' ? 'accepted' : 'declined', $applicationId]);
        
        // 如果接受申请，添加到报社成员
        if ($action === 'accept') {
            $stmt = $db->prepare("INSERT INTO newspaper_members (newspaper_id, user_id, role) VALUES (?, ?, 'reporter')");
            $stmt->execute([$newspaperId, $application['applicant_id']]);
        }
        
        // 发送通知邮件
        $newspaper = getNewspaper($newspaperId);
        $subject = $action === 'accept' ? "申请已被接受" : "申请已被拒绝";
        $content = $action === 'accept' ? 
            "你好！\n\n你的申请已被报社 {$newspaper['name']} 接受，你现在是该报社的 reporter。" : 
            "你好！\n\n很遗憾，你的申请未被报社 {$newspaper['name']} 接受。";
        
        sendMessage('user', $userId, 'user', $application['applicant_id'], $subject, $content, 'notification');
        
        $db->commit();
        return true;
    } catch (PDOException $e) {
        $db->rollBack();
        return false;
    }
}
?>