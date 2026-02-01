<?php
// 申请加入报社
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

// 检查用户是否已经是成员
$currentUserId = getCurrentUserId();
if (isNewspaperMember($newspaperId, $currentUserId)) {
    redirect('newspaper_home.php?id=' . $newspaperId);
}

$error = '';
$success = '';

// 处理申请
if (isset($_POST['action']) && $_POST['action'] === 'apply') {
    $message = $_POST['message'];
    
    // 发送申请
    $result = applyToNewspaper($newspaperId, $currentUserId, $message);
    if ($result) {
        $success = '申请发送成功，等待报社审核';
    } else {
        $error = '申请发送失败，可能已经存在待处理的申请';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申请加入报社 - <?php echo htmlspecialchars($newspaper['name']); ?></title>
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
        
        .newspaper-info {
            background-color: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #2196f3;
        }
        
        .newspaper-info h3 {
            margin-bottom: 10px;
            color: #1976d2;
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
        <h1>申请加入报社</h1>
        
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
        
        <div class="newspaper-info">
            <h3><?php echo htmlspecialchars($newspaper['name']); ?></h3>
            <?php if ($newspaper['description']): ?>
                <p><?php echo htmlspecialchars($newspaper['description']); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="form-section">
            <h2 class="section-title">申请加入</h2>
            <form method="POST" action="apply_join.php?newspaper_id=<?php echo $newspaperId; ?>">
                <input type="hidden" name="action" value="apply">
                
                <div class="form-group">
                    <label for="message">申请理由</label>
                    <textarea id="message" name="message" placeholder="请输入申请理由..." required></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn">提交申请</button>
                    <a href="newspaper_home.php?id=<?php echo $newspaperId; ?>" class="btn btn-secondary">返回报社主页</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>