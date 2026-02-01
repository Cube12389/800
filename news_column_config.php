<?php
// 专栏功能配置文件
require_once 'config.php';

// Markdown简单解析函数（复用自dynamic_config.php）
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

// 敏感词过滤函数（复用自dynamic_config.php）
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

// 创建报社
function createNewspaper($userId, $name, $description = '') {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // 过滤敏感词
        $filteredName = filterSensitiveWords($name);
        $filteredDescription = filterSensitiveWords($description);
        
        // 插入报社
        $stmt = $db->prepare("INSERT INTO newspapers (name, description, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$filteredName, $filteredDescription, $userId]);
        
        $newspaperId = $db->lastInsertId();
        
        // 添加创建者为报社成员（所有者）
        $stmt = $db->prepare("INSERT INTO newspaper_members (newspaper_id, user_id, role) VALUES (?, ?, 'owner')");
        $stmt->execute([$newspaperId, $userId]);
        
        $db->commit();
        return $newspaperId;
    } catch (PDOException $e) {
        $db->rollBack();
        return false;
    }
}

// 获取报社信息
function getNewspaper($newspaperId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM newspapers WHERE id = ?");
        $stmt->execute([$newspaperId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

// 更新报社信息
function updateNewspaper($newspaperId, $name, $description = '', $avatar = null) {
    try {
        $db = getDB();
        
        // 过滤敏感词
        $filteredName = filterSensitiveWords($name);
        $filteredDescription = filterSensitiveWords($description);
        
        if ($avatar) {
            $stmt = $db->prepare("UPDATE newspapers SET name = ?, description = ?, avatar = ? WHERE id = ?");
            $stmt->execute([$filteredName, $filteredDescription, $avatar, $newspaperId]);
        } else {
            $stmt = $db->prepare("UPDATE newspapers SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$filteredName, $filteredDescription, $newspaperId]);
        }
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// 获取用户加入的报社列表
function getUserNewspapers($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT n.*, nm.role FROM newspapers n JOIN newspaper_members nm ON n.id = nm.newspaper_id WHERE nm.user_id = ? ORDER BY n.created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// 检查用户是否是报社成员
function isNewspaperMember($newspaperId, $userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT role FROM newspaper_members WHERE newspaper_id = ? AND user_id = ?");
        $stmt->execute([$newspaperId, $userId]);
        $result = $stmt->fetch();
        return $result ? $result['role'] : false;
    } catch (PDOException $e) {
        return false;
    }
}

// 检查用户是否是报社所有者
function isNewspaperOwner($newspaperId, $userId) {
    $role = isNewspaperMember($newspaperId, $userId);
    return $role === 'owner';
}

// 检查用户是否是报社编辑或所有者
function canManageNewspaper($newspaperId, $userId) {
    $role = isNewspaperMember($newspaperId, $userId);
    return $role === 'owner' || $role === 'editor';
}

// 创建新闻
function createNews($newspaperId, $title, $content, $visibilityType = 'public', $visibilityUsers = []) {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // 过滤敏感词
        $filteredContent = filterSensitiveWords($content);
        $filteredTitle = filterSensitiveWords($title);
        
        // 转换markdown为HTML
        $contentHtml = markdownToHtml($filteredContent);
        
        // 插入新闻
        $stmt = $db->prepare("INSERT INTO news (newspaper_id, title, content, content_html, visibility_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$newspaperId, $filteredTitle, $filteredContent, $contentHtml, $visibilityType]);
        
        $newsId = $db->lastInsertId();
        
        // 处理可见范围
        if ($visibilityType === 'specific_users' || $visibilityType === 'exclude_users') {
            foreach ($visibilityUsers as $visibilityUserId) {
                $stmt = $db->prepare("INSERT INTO news_visibility (news_id, user_id) VALUES (?, ?)");
                $stmt->execute([$newsId, $visibilityUserId]);
            }
        }
        
        $db->commit();
        return $newsId;
    } catch (PDOException $e) {
        $db->rollBack();
        return false;
    }
}

// 获取新闻信息
function getNews($newsId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT n.*, np.name as newspaper_name, np.avatar as newspaper_avatar FROM news n JOIN newspapers np ON n.newspaper_id = np.id WHERE n.id = ?");
        $stmt->execute([$newsId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

// 获取用户可见的新闻
function getVisibleNews($userId = null) {
    try {
        $db = getDB();
        
        // 构建SQL查询
        $sql = "SELECT n.*, np.name as newspaper_name, np.avatar as newspaper_avatar FROM news n JOIN newspapers np ON n.newspaper_id = np.id WHERE ";
        
        if ($userId) {
            // 登录用户可以看到：公开新闻、所有用户可见的新闻、指定用户可见的新闻（包含自己）、排除用户可见的新闻（不包含自己）
            $sql .= "(n.visibility_type = 'public' OR n.visibility_type = 'all_users' OR 
                   (n.visibility_type = 'specific_users' AND EXISTS (SELECT 1 FROM news_visibility nv WHERE nv.news_id = n.id AND nv.user_id = ?)) OR 
                   (n.visibility_type = 'exclude_users' AND NOT EXISTS (SELECT 1 FROM news_visibility nv WHERE nv.news_id = n.id AND nv.user_id = ?))) ";
        } else {
            // 游客只能看到公开新闻
            $sql .= "n.visibility_type = 'public' ";
        }
        
        $sql .= "ORDER BY n.created_at DESC";
        
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

// 获取报社的新闻
function getNewspaperNews($newspaperId, $userId = null) {
    try {
        $db = getDB();
        
        // 构建SQL查询
        $sql = "SELECT n.*, np.name as newspaper_name, np.avatar as newspaper_avatar FROM news n JOIN newspapers np ON n.newspaper_id = np.id WHERE n.newspaper_id = ? AND ";
        
        if ($userId) {
            // 登录用户可以看到：公开新闻、所有用户可见的新闻、指定用户可见的新闻（包含自己）、排除用户可见的新闻（不包含自己）
            $sql .= "(n.visibility_type = 'public' OR n.visibility_type = 'all_users' OR 
                   (n.visibility_type = 'specific_users' AND EXISTS (SELECT 1 FROM news_visibility nv WHERE nv.news_id = n.id AND nv.user_id = ?)) OR 
                   (n.visibility_type = 'exclude_users' AND NOT EXISTS (SELECT 1 FROM news_visibility nv WHERE nv.news_id = n.id AND nv.user_id = ?))) ";
        } else {
            // 游客只能看到公开新闻
            $sql .= "n.visibility_type = 'public' ";
        }
        
        $sql .= "ORDER BY n.created_at DESC";
        
        $stmt = $db->prepare($sql);
        
        if ($userId) {
            $stmt->execute([$newspaperId, $userId, $userId]);
        } else {
            $stmt->execute([$newspaperId]);
        }
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// 检查用户是否可以查看新闻
function canUserViewNews($news, $currentUserId) {
    try {
        $visibilityType = $news['visibility_type'];
        
        // 公开新闻，所有人都可以查看
        if ($visibilityType === 'public') {
            return true;
        }
        
        // 所有用户可见的新闻，登录用户可以查看
        if ($visibilityType === 'all_users' && $currentUserId) {
            return true;
        }
        
        // 指定用户可见的新闻
        if ($visibilityType === 'specific_users' && $currentUserId) {
            $db = getDB();
            $stmt = $db->prepare("SELECT COUNT(*) FROM news_visibility WHERE news_id = ? AND user_id = ?");
            $stmt->execute([$news['id'], $currentUserId]);
            $count = $stmt->fetchColumn();
            return $count > 0;
        }
        
        // 排除用户可见的新闻
        if ($visibilityType === 'exclude_users') {
            if (!$currentUserId) {
                // 游客可以查看
                return true;
            }
            $db = getDB();
            $stmt = $db->prepare("SELECT COUNT(*) FROM news_visibility WHERE news_id = ? AND user_id = ?");
            $stmt->execute([$news['id'], $currentUserId]);
            $count = $stmt->fetchColumn();
            return $count === 0;
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

// 点赞新闻
function likeNews($newsId, $userId) {
    try {
        $db = getDB();
        
        // 检查是否已经点赞
        $stmt = $db->prepare("SELECT id FROM news_likes WHERE news_id = ? AND user_id = ?");
        $stmt->execute([$newsId, $userId]);
        if ($stmt->fetch()) {
            return false; // 已经点赞过
        }
        
        // 添加点赞
        $stmt = $db->prepare("INSERT INTO news_likes (news_id, user_id) VALUES (?, ?)");
        $stmt->execute([$newsId, $userId]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// 取消点赞
function unlikeNews($newsId, $userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM news_likes WHERE news_id = ? AND user_id = ?");
        $stmt->execute([$newsId, $userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// 检查用户是否已点赞
function hasUserLikedNews($newsId, $userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM news_likes WHERE news_id = ? AND user_id = ?");
        $stmt->execute([$newsId, $userId]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

// 获取新闻的点赞数
function getNewsLikeCount($newsId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM news_likes WHERE news_id = ?");
        $stmt->execute([$newsId]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// 添加评论
function addNewsComment($newsId, $userId, $content) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO news_comments (news_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$newsId, $userId, $content]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// 获取新闻的评论
function getNewsComments($newsId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT c.*, u.username FROM news_comments c JOIN users u ON c.user_id = u.id WHERE c.news_id = ? ORDER BY c.created_at ASC");
        $stmt->execute([$newsId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// 删除评论
function deleteNewsComment($commentId, $userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM news_comments WHERE id = ? AND user_id = ?");
        $stmt->execute([$commentId, $userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// 获取所有报社
function getAllNewspapers() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM newspapers ORDER BY created_at DESC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// 检查用户是否存在
function userExists($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}
?>