<?php
// 动态功能配置文件
require_once 'config.php';

// Markdown简单解析函数
function markdownToHtml($markdown) {
    // 标题
    $markdown = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $markdown);
    $markdown = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $markdown);
    $markdown = preg_replace('/^# (.*)$/m', '<h1>$1</h1>', $markdown);
    
    // 粗体和斜体
    $markdown = preg_replace('/\*\*(.*)\*\*/', '<strong>$1</strong>', $markdown);
    $markdown = preg_replace('/\*(.*)\*/', '<em>$1</em>', $markdown);
    
    // 链接
    $markdown = preg_replace('/\[(.*)\]\((.*)\)/', '<a href="$2">$1</a>', $markdown);
    
    // 列表
    $markdown = preg_replace('/^\- (.*)$/m', '<li>$1</li>', $markdown);
    $markdown = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $markdown);
    
    // 代码块
    $markdown = preg_replace('/```(.*)```/s', '<pre><code>$1</code></pre>', $markdown);
    
    // 换行
    $markdown = nl2br($markdown);
    
    return $markdown;
}

// 敏感词过滤函数
function filterSensitiveWords($content) {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT word FROM sensitive_words");
        $sensitiveWords = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($sensitiveWords as $word) {
            $replacement = str_repeat('*', strlen($word));
            $content = str_replace($word, $replacement, $content);
        }
        
        return $content;
    } catch (PDOException $e) {
        return $content;
    }
}

// 获取用户可见的动态
function getVisibleDynamics($userId = null) {
    try {
        $db = getDB();
        
        // 构建SQL查询
        $sql = "SELECT d.*, u.username FROM dynamics d JOIN users u ON d.user_id = u.id WHERE ";
        
        if ($userId) {
            // 登录用户可以看到：公开动态、所有用户可见的动态、指定用户可见的动态（包含自己）、排除用户可见的动态（不包含自己）
            $sql .= "(d.visibility_type = 'public' OR d.visibility_type = 'all_users' OR 
                   (d.visibility_type = 'specific_users' AND EXISTS (SELECT 1 FROM dynamic_visibility dv WHERE dv.dynamic_id = d.id AND dv.user_id = ?)) OR 
                   (d.visibility_type = 'exclude_users' AND NOT EXISTS (SELECT 1 FROM dynamic_visibility dv WHERE dv.dynamic_id = d.id AND dv.user_id = ?))) ";
        } else {
            // 游客只能看到公开动态
            $sql .= "d.visibility_type = 'public' ";
        }
        
        $sql .= "ORDER BY d.created_at DESC";
        
        $stmt = $db->prepare($sql);
        
        if ($userId) {
            $stmt->execute([$userId, $userId]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// 创建动态
function createDynamic($userId, $title, $content, $visibilityType, $visibilityUsers = []) {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // 过滤敏感词
        $filteredContent = filterSensitiveWords($content);
        $filteredTitle = filterSensitiveWords($title);
        
        // 转换markdown为HTML
        $contentHtml = markdownToHtml($filteredContent);
        
        // 插入动态
        $stmt = $db->prepare("INSERT INTO dynamics (user_id, title, content, content_html, visibility_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $filteredTitle, $filteredContent, $contentHtml, $visibilityType]);
        
        $dynamicId = $db->lastInsertId();
        
        // 处理可见范围
        if ($visibilityType === 'specific_users' || $visibilityType === 'exclude_users') {
            foreach ($visibilityUsers as $visibilityUserId) {
                $stmt = $db->prepare("INSERT INTO dynamic_visibility (dynamic_id, user_id) VALUES (?, ?)");
                $stmt->execute([$dynamicId, $visibilityUserId]);
            }
        }
        
        $db->commit();
        return true;
    } catch (PDOException $e) {
        $db->rollBack();
        return false;
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

// 获取敏感词列表
function getSensitiveWords() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM sensitive_words ORDER BY created_at DESC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// 添加敏感词
function addSensitiveWord($word) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT IGNORE INTO sensitive_words (word) VALUES (?)");
        return $stmt->execute([$word]);
    } catch (PDOException $e) {
        return false;
    }
}

// 删除敏感词
function deleteSensitiveWord($id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM sensitive_words WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        return false;
    }
}

// 检查用户是否有权限查看动态
function canUserViewDynamic($dynamic, $currentUserId) {
    try {
        $visibilityType = $dynamic['visibility_type'];
        
        // 公开动态，所有人都可以查看
        if ($visibilityType === 'public') {
            return true;
        }
        
        // 所有用户可见的动态，登录用户可以查看
        if ($visibilityType === 'all_users' && $currentUserId) {
            return true;
        }
        
        // 指定用户可见的动态
        if ($visibilityType === 'specific_users' && $currentUserId) {
            $db = getDB();
            $stmt = $db->prepare("SELECT COUNT(*) FROM dynamic_visibility WHERE dynamic_id = ? AND user_id = ?");
            $stmt->execute([$dynamic['id'], $currentUserId]);
            $count = $stmt->fetchColumn();
            return $count > 0;
        }
        
        // 排除用户可见的动态
        if ($visibilityType === 'exclude_users') {
            if (!$currentUserId) {
                // 游客可以查看
                return true;
            }
            $db = getDB();
            $stmt = $db->prepare("SELECT COUNT(*) FROM dynamic_visibility WHERE dynamic_id = ? AND user_id = ?");
            $stmt->execute([$dynamic['id'], $currentUserId]);
            $count = $stmt->fetchColumn();
            return $count === 0;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

// 检查用户是否是动态的作者
function isDynamicAuthor($dynamic, $userId) {
    return $dynamic['user_id'] == $userId;
}

// 点赞动态
function likeDynamic($dynamicId, $userId) {
    try {
        $db = getDB();
        
        // 检查是否已经点赞
        $stmt = $db->prepare("SELECT id FROM dynamic_likes WHERE dynamic_id = ? AND user_id = ?");
        $stmt->execute([$dynamicId, $userId]);
        if ($stmt->fetch()) {
            return false; // 已经点赞过
        }
        
        // 添加点赞
        $stmt = $db->prepare("INSERT INTO dynamic_likes (dynamic_id, user_id) VALUES (?, ?)");
        $stmt->execute([$dynamicId, $userId]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// 取消点赞
function unlikeDynamic($dynamicId, $userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM dynamic_likes WHERE dynamic_id = ? AND user_id = ?");
        $stmt->execute([$dynamicId, $userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// 检查用户是否已点赞
function hasUserLiked($dynamicId, $userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM dynamic_likes WHERE dynamic_id = ? AND user_id = ?");
        $stmt->execute([$dynamicId, $userId]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

// 获取动态的点赞数
function getDynamicLikeCount($dynamicId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM dynamic_likes WHERE dynamic_id = ?");
        $stmt->execute([$dynamicId]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// 添加评论
function addComment($dynamicId, $userId, $content) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO dynamic_comments (dynamic_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$dynamicId, $userId, $content]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// 获取动态的评论
function getDynamicComments($dynamicId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT c.*, u.username FROM dynamic_comments c JOIN users u ON c.user_id = u.id WHERE c.dynamic_id = ? ORDER BY c.created_at ASC");
        $stmt->execute([$dynamicId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// 删除评论
function deleteComment($commentId, $userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM dynamic_comments WHERE id = ? AND user_id = ?");
        $stmt->execute([$commentId, $userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}
?>