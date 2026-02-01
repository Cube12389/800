<?php
// 用户主页
require_once 'config.php';
require_once 'dynamic_config.php';

requireLogin();

$userId = getCurrentUserId();
$error = '';
$success = '';

// 检查用户是否有班级成员标签
function isClassMember($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT t.name FROM user_tag_relations utr JOIN user_tags t ON utr.tag_id = t.id WHERE utr.user_id = ? AND t.name = '班级成员'");
        $stmt->execute([$userId]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

// 获取用户信息
function getUserInfo($userId) {
    try {
        $db = getDB();
        
        // 获取用户基本信息
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // 获取用户详细信息
        $stmt = $db->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
        
        return [
            'user' => $user,
            'profile' => $profile
        ];
    } catch (PDOException $e) {
        return false;
    }
}

// 获取用户发布的动态
function getUserDynamics($userId) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("SELECT * FROM dynamics WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $dynamics = $stmt->fetchAll();
        
        // 转换markdown为HTML
        foreach ($dynamics as &$dynamic) {
            $dynamic['content_html'] = markdownToHtml($dynamic['content']);
        }
        
        return $dynamics;
    } catch (PDOException $e) {
        return [];
    }
}

// 删除动态
if (isset($_POST['action']) && $_POST['action'] === 'delete_dynamic' && isset($_POST['dynamic_id'])) {
    $dynamicId = $_POST['dynamic_id'];
    
    try {
        $db = getDB();
        
        // 检查动态是否存在且属于当前用户
        $stmt = $db->prepare("SELECT * FROM dynamics WHERE id = ? AND user_id = ?");
        $stmt->execute([$dynamicId, $userId]);
        $dynamic = $stmt->fetch();
        
        if (!$dynamic) {
            $error = '动态不存在或无权限操作';
        } else {
            // 删除动态
            $stmt = $db->prepare("DELETE FROM dynamics WHERE id = ?");
            $stmt->execute([$dynamicId]);
            
            // 删除相关的可见性设置
            $stmt = $db->prepare("DELETE FROM dynamic_visibility WHERE dynamic_id = ?");
            $stmt->execute([$dynamicId]);
            
            $success = '动态删除成功';
        }
    } catch (PDOException $e) {
        $error = '删除动态失败，请稍后重试';
    }
}

// 获取用户信息和动态
$userInfo = getUserInfo($userId);
$userDynamics = getUserDynamics($userId);

if (!$userInfo) {
    $error = '获取用户信息失败';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <link rel="icon" href="asstes/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户主页 - 班级网站</title>
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
        
        .user-profile {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: flex-start;
            gap: 30px;
        }
        
        .avatar-section {
            flex-shrink: 0;
        }
        
        .avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #007bff;
        }
        
        .user-info {
            flex-grow: 1;
        }
        
        .user-name {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #343a40;
        }
        
        .user-meta {
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .user-bio {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .profile-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
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
        
        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #343a40;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        
        .dynamics-section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .dynamic-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        .dynamic-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .dynamic-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .dynamic-content {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .dynamic-actions {
            display: flex;
            gap: 10px;
            border-top: 1px solid #e9ecef;
            padding-top: 15px;
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
        
        .no-dynamics {
            text-align: center;
            padding: 40px 0;
            color: #6c757d;
        }
        
        @media screen and (max-width: 768px) {
            .container {
                margin: 30px auto;
            }
            
            .user-profile {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .profile-actions {
                justify-content: center;
            }
            
            .dynamic-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .dynamic-actions {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>用户主页</h1>
        
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
        
        <?php if ($userInfo): ?>
            <!-- 用户信息部分 -->
            <div class="user-profile">
                <div class="avatar-section">
                    <img src="asstes/UrseIcon/<?php echo htmlspecialchars($userInfo['user']['avatar'] ?? 'BeginUrse.jfif'); ?>" alt="用户头像" class="avatar">
                </div>
                <div class="user-info">
                    <h2 class="user-name">
                        <?php echo htmlspecialchars($userInfo['user']['username']); ?>
                        <?php 
                        // 检查当前用户是否是班级成员，如果是且用户有班内名称，则显示班内名称
                        if (isClassMember($userId) && !empty($userInfo['user']['class_name'])): ?>
                            <span style="font-size: 1rem; color: #007bff; margin-left: 10px;">(<?php echo htmlspecialchars($userInfo['user']['class_name']); ?>)</span>
                        <?php endif; ?>
                    </h2>
                    <div class="user-meta">
                        <p>注册时间：<?php echo $userInfo['user']['created_at']; ?></p>
                        <p>邮箱：<?php echo $userInfo['user']['email'] ?? '未设置'; ?></p>
                        <p>手机号：<?php echo $userInfo['user']['phone'] ?? '未设置'; ?></p>
                        <?php if (isClassMember($userId)): ?>
                            <p>班内名称：<?php echo $userInfo['user']['class_name'] ?? '未设置'; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($userInfo['profile']['bio']): ?>
                        <div class="user-bio">
                            <h3>个人简介</h3>
                            <p><?php echo htmlspecialchars($userInfo['profile']['bio']); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="profile-actions">
                        <a href="avatar_upload.php" class="btn btn-secondary">上传头像</a>
                        <a href="user_profile.php" class="btn btn-success">账户设置</a>
                        <a href="dynamic_post.php" class="btn">发布动态</a>
                        <a href="user_messages.php" class="btn btn-info">站内邮箱</a>
                    </div>
                </div>
            </div>
            
            <!-- 用户动态部分 -->
            <div class="dynamics-section">
                <h2 class="section-title">我的动态</h2>
                
                <?php if (count($userDynamics) > 0): ?>
                    <?php foreach ($userDynamics as $dynamic): ?>
                        <div class="dynamic-card">
                            <div class="dynamic-header">
                                <div class="dynamic-meta">
                                    发布时间：<?php echo $dynamic['created_at']; ?>
                                </div>
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
                            
                            <div class="dynamic-actions">
                                <a href="dynamic_detail.php?id=<?php echo $dynamic['id']; ?>" class="btn">查看详情</a>
                                <a href="dynamic_edit.php?id=<?php echo $dynamic['id']; ?>" class="btn btn-secondary">编辑</a>
                                <form method="POST" action="user_home.php" onsubmit="return confirm('确定要删除这条动态吗？');">
                                    <input type="hidden" name="action" value="delete_dynamic">
                                    <input type="hidden" name="dynamic_id" value="<?php echo $dynamic['id']; ?>">
                                    <button type="submit" class="btn btn-danger">删除</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-dynamics">
                        <h3>暂无动态</h3>
                        <p>快来发布第一条动态吧！</p>
                        <a href="dynamic_post.php" class="btn">发布动态</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="error">
                获取用户信息失败
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>