<?php
// 动态编辑页面
require_once 'config.php';
require_once 'dynamic_config.php';

requireLogin();

$userId = getCurrentUserId();
$error = '';
$success = '';
$dynamic = null;

// 检查是否提供了动态ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $error = '动态ID不能为空';
} else {
    $dynamicId = $_GET['id'];
    
    // 获取动态详情
    function getDynamicById($id, $userId) {
        try {
            $db = getDB();
            
            $stmt = $db->prepare("SELECT * FROM dynamics WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            $dynamic = $stmt->fetch();
            
            return $dynamic;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    $dynamic = getDynamicById($dynamicId, $userId);
    
    if (!$dynamic) {
        $error = '动态不存在或无权限操作';
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dynamic) {
    $content = $_POST['content'] ?? '';
    $visibilityType = $_POST['visibility_type'] ?? 'public';
    $visibilityUsers = $_POST['visibility_users'] ?? [];
    
    if (empty($content)) {
        $error = '内容不能为空';
    } else {
        try {
            $db = getDB();
            
            // 开始事务
            $db->beginTransaction();
            
            // 更新动态内容
            $stmt = $db->prepare("UPDATE dynamics SET content = ?, visibility_type = ? WHERE id = ?");
            $stmt->execute([$content, $visibilityType, $dynamicId]);
            
            // 删除旧的可见性设置
            $stmt = $db->prepare("DELETE FROM dynamic_visibility WHERE dynamic_id = ?");
            $stmt->execute([$dynamicId]);
            
            // 添加新的可见性设置（如果需要）
            if (in_array($visibilityType, ['specific_users', 'exclude_users']) && !empty($visibilityUsers)) {
                foreach ($visibilityUsers as $targetUserId) {
                    $stmt = $db->prepare("INSERT INTO dynamic_visibility (dynamic_id, user_id) VALUES (?, ?)");
                    $stmt->execute([$dynamicId, $targetUserId]);
                }
            }
            
            // 提交事务
            $db->commit();
            
            $success = '动态编辑成功';
            
            // 重新获取动态信息
            $dynamic = getDynamicById($dynamicId, $userId);
        } catch (PDOException $e) {
            // 回滚事务
            $db->rollBack();
            $error = '动态编辑失败，请稍后重试';
        }
    }
}

// 获取所有用户（用于可见范围选择）
function getAllUsers() {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username FROM users");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

$users = getAllUsers();

// 获取当前动态的可见用户
function getDynamicVisibilityUsers($dynamicId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT user_id FROM dynamic_visibility WHERE dynamic_id = ?");
        $stmt->execute([$dynamicId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}

$visibilityUsers = $dynamic ? getDynamicVisibilityUsers($dynamicId) : [];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑动态 - 班级网站</title>
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
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #007bff;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            resize: vertical;
            min-height: 300px;
            font-family: 'Courier New', Courier, monospace;
        }
        
        textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
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
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-right: 10px;
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
        
        .form-actions {
            margin-top: 30px;
            text-align: center;
        }
        
        .visibility-options {
            margin-bottom: 20px;
        }
        
        .visibility-option {
            margin-bottom: 10px;
        }
        
        .user-list {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .user-item {
            margin-bottom: 5px;
        }
        
        .markdown-hint {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        .markdown-hint h3 {
            margin-bottom: 10px;
            color: #007bff;
        }
        
        .markdown-hint ul {
            margin-left: 20px;
        }
        
        @media screen and (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>编辑动态</h1>
        
        <?php if ($error): ?>
            <div class="error">
                <?php echo $error; ?>
                <div style="margin-top: 20px;">
                    <a href="user_home.php" class="btn btn-secondary">返回用户主页</a>
                </div>
            </div>
        <?php elseif ($dynamic): ?>
            <?php if ($success): ?>
                <div class="success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <div class="markdown-hint">
                <h3>Markdown语法提示</h3>
                <ul>
                    <li><strong>标题</strong>: # 一级标题, ## 二级标题, ### 三级标题</li>
                    <li><strong>粗体</strong>: **粗体文本**</li>
                    <li><strong>斜体</strong>: *斜体文本*</li>
                    <li><strong>链接</strong>: [链接文本](链接地址)</li>
                    <li><strong>列表</strong>: - 列表项1</li>
                    <li><strong>代码块</strong>: ```代码内容```</li>
                </ul>
            </div>
            
            <form method="POST" action="dynamic_edit.php?id=<?php echo $dynamicId; ?>">
                <div class="form-group">
                    <label for="content">内容</label>
                    <textarea id="content" name="content" required><?php echo htmlspecialchars($dynamic['content']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>可见范围</label>
                    <div class="visibility-options">
                        <div class="visibility-option">
                            <input type="radio" id="visibility_public" name="visibility_type" value="public" <?php echo $dynamic['visibility_type'] === 'public' ? 'checked' : ''; ?>>
                            <label for="visibility_public">所有人（包括游客）</label>
                        </div>
                        <div class="visibility-option">
                            <input type="radio" id="visibility_all_users" name="visibility_type" value="all_users" <?php echo $dynamic['visibility_type'] === 'all_users' ? 'checked' : ''; ?>>
                            <label for="visibility_all_users">所有用户</label>
                        </div>
                        <div class="visibility-option">
                            <input type="radio" id="visibility_specific_users" name="visibility_type" value="specific_users" <?php echo $dynamic['visibility_type'] === 'specific_users' ? 'checked' : ''; ?>>
                            <label for="visibility_specific_users">指定用户</label>
                            <div class="user-list" id="specific_users_list">
                                <?php foreach ($users as $user): ?>
                                    <div class="user-item">
                                        <input type="checkbox" name="visibility_users[]" value="<?php echo $user['id']; ?>" <?php echo in_array($user['id'], $visibilityUsers) ? 'checked' : ''; ?>>
                                        <label><?php echo $user['username']; ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="visibility-option">
                            <input type="radio" id="visibility_exclude_users" name="visibility_type" value="exclude_users" <?php echo $dynamic['visibility_type'] === 'exclude_users' ? 'checked' : ''; ?>>
                            <label for="visibility_exclude_users">不给指定用户看</label>
                            <div class="user-list" id="exclude_users_list">
                                <?php foreach ($users as $user): ?>
                                    <div class="user-item">
                                        <input type="checkbox" name="visibility_users[]" value="<?php echo $user['id']; ?>" <?php echo in_array($user['id'], $visibilityUsers) ? 'checked' : ''; ?>>
                                        <label><?php echo $user['username']; ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn">保存修改</button>
                    <a href="user_home.php" class="btn btn-secondary">取消</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script>
        // 显示/隐藏用户选择列表
        document.querySelectorAll('input[name="visibility_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('specific_users_list').style.display = this.value === 'specific_users' ? 'block' : 'none';
                document.getElementById('exclude_users_list').style.display = this.value === 'exclude_users' ? 'block' : 'none';
            });
        });
        
        // 初始状态
        document.getElementById('specific_users_list').style.display = document.querySelector('input[name="visibility_type"][value="specific_users"]').checked ? 'block' : 'none';
        document.getElementById('exclude_users_list').style.display = document.querySelector('input[name="visibility_type"][value="exclude_users"]').checked ? 'block' : 'none';
    </script>
</body>
</html>