<?php
// 邀请成员加入报社
require_once 'email_config.php';

// 获取报社ID
if (!isset($_GET['newspaper_id'])) {
    redirect('news_column.php');
}

$newspaperId = intval($_GET['newspaper_id']);

// 获取报社信息
$newspaper = getNewspaper($newspaperId);
if (!$newspaper) {
    redirect('news_column.php');
}

// 检查用户权限
$currentUserId = getCurrentUserId();
if (!canManageNewspaper($newspaperId, $currentUserId)) {
    redirect('newspaper_home.php?id=' . $newspaperId);
}

$error = '';
$success = '';

// 处理邀请
if (isset($_POST['action']) && $_POST['action'] === 'invite') {
    $inviteeId = intval($_POST['invitee_id']);
    $role = $_POST['role'];
    
    // 检查被邀请人是否存在
    if (!userExists($inviteeId)) {
        $error = '被邀请用户不存在';
    } else {
        // 发送邀请
        $result = sendNewspaperInvitation($newspaperId, $currentUserId, $inviteeId, $role);
        if ($result) {
            $success = '邀请发送成功';
        } else {
            $error = '邀请发送失败，可能已经存在待处理的邀请';
        }
    }
}

// 获取所有用户列表
function getAllUsers() {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username FROM users ORDER BY username ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

$users = getAllUsers();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邀请成员 - <?php echo htmlspecialchars($newspaper['name']); ?></title>
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
        
        .form-section {
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #495057;
        }
        
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
            background-color: #fff;
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
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }
        
        @media screen and (max-width: 768px) {
            .container {
                margin: 30px auto;
            }
            
            .form-section {
                padding: 20px;
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
        <h1>邀请成员加入报社</h1>
        
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
        
        <div class="form-section">
            <h2 class="section-title">邀请成员</h2>
            <form method="POST" action="invite_members.php?newspaper_id=<?php echo $newspaperId; ?>">
                <input type="hidden" name="action" value="invite">
                
                <div class="form-group">
                    <label for="invitee_id">选择用户</label>
                    <select id="invitee_id" name="invitee_id" required>
                        <option value="">请选择用户</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="role">职位</label>
                    <select id="role" name="role" required>
                        <option value="editor">编辑</option>
                        <option value="reporter">记者</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn">发送邀请</button>
                    <a href="newspaper_home.php?id=<?php echo $newspaperId; ?>" class="btn btn-secondary">返回报社主页</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>