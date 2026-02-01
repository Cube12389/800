<?php
// 班级相册页面
require_once 'config.php';
require_once 'news_column_config.php';

// 检查用户是否登录
$isLoggedIn = false;
$currentUserId = null;
if (isset($_COOKIE['token'])) {
    require_once 'config.php';
    if (isLoggedIn()) {
        $isLoggedIn = true;
        $currentUserId = getCurrentUserId();
    }
}

// 检查用户是否有班级成员标签
function isClassMember($userId) {
    if (!$userId) return false;
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT t.name FROM user_tag_relations utr JOIN user_tags t ON utr.tag_id = t.id WHERE utr.user_id = ? AND t.name = '班级成员'");
        $stmt->execute([$userId]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

$isClassMember = $currentUserId ? isClassMember($currentUserId) : false;

$error = '';
$success = '';

// 创建上传目录
$uploadDir = 'uploads/albums/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 处理照片上传
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_photo') {
    // 检查用户是否是班级成员
    if (!$isClassMember) {
        $error = '只有班级成员才能上传照片';
    } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $originalFilename = $_FILES['photo']['name'];
        $filename = uniqid() . '_' . time() . '.' . pathinfo($originalFilename, PATHINFO_EXTENSION);
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            try {
                $db = getDB();
                $albumId = 1; // 默认班级相册
                $description = $_POST['description'] ?? '';
                $visibility = $_POST['visibility'] ?? 'public'; // 默认公开
                
                $stmt = $db->prepare("INSERT INTO photos (album_id, filename, original_filename, description, visibility, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$albumId, $filename, $originalFilename, $description, $visibility, $currentUserId]);
                
                $success = '照片上传成功';
            } catch (PDOException $e) {
                $error = '保存照片信息失败: ' . $e->getMessage();
                unlink($targetPath); // 删除已上传的文件
            }
        } else {
            $error = '上传照片失败';
        }
    } else {
        $error = '请选择要上传的照片';
    }
}

// 处理照片删除
if (isset($_GET['action']) && $_GET['action'] === 'delete_photo' && isset($_GET['photo_id'])) {
    // 检查用户是否是班级成员
    if (!$isClassMember) {
        $error = '只有班级成员才能删除照片';
    } else {
        $photoId = intval($_GET['photo_id']);
        
        try {
            $db = getDB();
            
            // 检查照片是否存在且属于当前用户
            $stmt = $db->prepare("SELECT filename FROM photos WHERE id = ? AND uploaded_by = ?");
            $stmt->execute([$photoId, $currentUserId]);
            $photo = $stmt->fetch();
            
            if ($photo) {
                // 删除文件
                $filePath = $uploadDir . $photo['filename'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                // 删除数据库记录
                $stmt = $db->prepare("DELETE FROM photos WHERE id = ?");
                $stmt->execute([$photoId]);
                
                $success = '照片删除成功';
            } else {
                $error = '照片不存在或无权限删除';
            }
        } catch (PDOException $e) {
            $error = '删除照片失败: ' . $e->getMessage();
        }
    }
}

// 获取所有照片
function getAllPhotos($isClassMember) {
    try {
        $db = getDB();
        
        // 根据用户身份构建查询条件
        if ($isClassMember) {
            // 班级成员可以看到所有照片
            $stmt = $db->prepare("SELECT p.*, u.username, u.class_name FROM photos p JOIN users u ON p.uploaded_by = u.id WHERE p.album_id = 1 ORDER BY p.uploaded_at DESC");
        } else {
            // 非班级成员只能看到公开照片
            $stmt = $db->prepare("SELECT p.*, u.username, u.class_name FROM photos p JOIN users u ON p.uploaded_by = u.id WHERE p.album_id = 1 AND p.visibility = 'public' ORDER BY p.uploaded_at DESC");
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

$photos = getAllPhotos($isClassMember);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <link rel="icon" href="asstes/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>班级相册 - 班级网站</title>
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
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 20px;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #007bff;
        }
        
        .upload-section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        label {
            font-weight: bold;
            color: #495057;
        }
        
        input[type="file"] {
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        
        textarea {
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            resize: vertical;
            min-height: 100px;
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
            align-self: flex-start;
        }
        
        .btn:hover {
            background-color: #0069d9;
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .photo-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .photo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        .photo-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
        }
        
        .photo-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .photo-card:hover .photo-image img {
            transform: scale(1.1);
        }
        
        .photo-content {
            padding: 20px;
        }
        
        .photo-meta {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .photo-uploader {
            font-weight: bold;
        }
        
        .photo-description {
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .photo-actions {
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
        
        .no-photos {
            text-align: center;
            padding: 60px 0;
            color: #6c757d;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        @media screen and (max-width: 768px) {
            .container {
                margin: 30px auto;
            }
            
            .upload-section {
                padding: 20px;
            }
            
            .photo-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }
            
            .btn {
                align-self: stretch;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>班级相册</h1>
        
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
        
        <!-- 上传照片 - 仅班级成员可见 -->
        <?php if ($isClassMember): ?>
        <div class="upload-section">
            <h2>上传照片</h2>
            <form method="POST" action="class_album.php" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="action" value="upload_photo">
                
                <div class="form-group">
                    <label for="photo">选择照片</label>
                    <input type="file" id="photo" name="photo" accept="image/*" required>
                </div>
                
                <div class="form-group">
                    <label for="description">描述</label>
                    <textarea id="description" name="description" placeholder="请输入照片描述..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="visibility">可见性</label>
                    <select id="visibility" name="visibility" class="form-control" style="padding: 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 16px;">
                        <option value="public">公开 (所有人可见)</option>
                        <option value="class_only">仅班级成员可见</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">上传照片</button>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- 照片列表 -->
        <h2>照片列表</h2>
        <?php if (count($photos) > 0): ?>
            <div class="photo-grid">
                <?php foreach ($photos as $photo): ?>
                    <div class="photo-card">
                            <div class="photo-image">
                                <img src="<?php echo $uploadDir . $photo['filename']; ?>" alt="<?php echo htmlspecialchars($photo['original_filename']); ?>">
                            </div>
                            <div class="photo-content">
                                <div class="photo-meta">
                                    <div class="photo-uploader">
                                        <?php echo htmlspecialchars($photo['class_name'] ?? $photo['username']); ?>
                                        <?php if (isset($photo['visibility'])): ?>
                                            <span style="font-size: 0.8rem; color: #6c757d; margin-left: 5px;">
                                                (<?php echo $photo['visibility'] === 'public' ? '公开' : '仅班级可见'; ?>)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="photo-date">
                                        <?php echo $photo['uploaded_at']; ?>
                                    </div>
                                </div>
                                <?php if ($photo['description']): ?>
                                    <div class="photo-description">
                                        <?php echo htmlspecialchars($photo['description']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($isClassMember && $photo['uploaded_by'] === $currentUserId): ?>
                                    <div class="photo-actions">
                                        <a href="class_album.php?action=delete_photo&photo_id=<?php echo $photo['id']; ?>" class="btn btn-danger" onclick="return confirm('确定要删除这张照片吗？');">删除</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-photos">
                <h3>暂无照片</h3>
                <p>快来上传第一张照片吧！</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>