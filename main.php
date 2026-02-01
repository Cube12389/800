<?php
// 引入新闻专栏配置文件
require_once 'news_column_config.php';

// 获取最新的3个新闻
$latestNews = array_slice(getVisibleNews(), 0, 3);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <link rel="icon" href="asstes/favicon.ico" type="image/x-icon">
    <title>班级网站</title>
    <style>
        /* 内容区域样式 */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .section {
            margin-bottom: 60px;
        }
        
        .section h2 {
            font-size: 2rem;
            margin-bottom: 30px;
            color: #333;
            text-align: center;
        }
        
        .section h3 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #007bff;
        }
        
        .intro-content {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 40px;
        }
        
        .intro-text {
            flex: 1;
            min-width: 300px;
        }
        
        .intro-image {
            flex: 1;
            min-width: 300px;
        }
        
        .intro-image img {
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .news-item {
            background-color: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .news-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        .news-image {
            height: 200px;
            overflow: hidden;
        }
        
        .news-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .news-item:hover .news-image img {
            transform: scale(1.1);
        }
        
        .news-content {
            padding: 20px;
        }
        
        .news-content h3 {
            margin-bottom: 10px;
        }
        
        .news-date {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .column-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .column-item {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .column-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        .column-icon {
            width: 80px;
            height: 80px;
            background-color: #007bff;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
        }
        
        @media screen and (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .section h2 {
                font-size: 1.5rem;
            }
            
            .section h3 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <!-- 欢迎横幅 -->
    <section class="hero">
        <div class="container">
            <h1>欢迎访问我们的班级网站</h1>
            <p>记录班级生活的点点滴滴，分享成长的快乐与收获</p>
        </div>
    </section>
    
    <!-- 班级简介 -->
    <section class="section">
        <div class="container">
            <h2>班级简介</h2>
            <div class="intro-content">
                <div class="intro-text">
                    <h3>我们的班级</h3>
                    <p>我们是一个充满活力和凝聚力的班级，由来自不同背景的同学组成。在老师的带领下，我们共同学习、共同成长，建立了深厚的友谊。</p>
                    <p>班级成立以来，我们积极参加各种活动，在学习、文体等方面都取得了优异的成绩。我们相信，通过团结协作，我们的班级会变得更加优秀。</p>
                </div>
                <div class="intro-image">
                    <img src="https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=classroom%20students%20learning%20together%20happy%20young%20people&image_size=landscape_16_9" alt="班级活动">
                </div>
            </div>
        </div>
    </section>
    
    <!-- 最新动态 -->
    <section class="section">
        <div class="container">
            <h2>最新动态</h2>
            <div class="news-grid">
                <?php if (count($latestNews) > 0): ?>
                    <?php foreach ($latestNews as $news): ?>
                        <div class="news-item">
                            <div class="news-image">
                                <img src="https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=news%20article%20headline%20relevant%20image&image_size=square" alt="新闻图片">
                            </div>
                            <div class="news-content">
                                <h3><?php echo htmlspecialchars($news['title']); ?></h3>
                                <div class="news-date">
                                    <?php echo $news['created_at']; ?>
                                    <span style="margin-left: 10px; font-size: 0.8rem; color: #007bff;">
                                        <?php echo htmlspecialchars($news['newspaper_name']); ?>
                                    </span>
                                </div>
                                <p><?php echo mb_substr(strip_tags($news['content_html']), 0, 100) . '...'; ?></p>
                                <a href="news_detail.php?id=<?php echo $news['id']; ?>" style="color: #007bff; text-decoration: none; font-weight: bold;">查看详情 →</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                        <h3>暂无新闻</h3>
                        <p>还没有发布任何新闻</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- 特色专栏 -->
    <section class="section">
        <div class="container">
            <h2>特色专栏</h2>
            <div class="column-grid">
                <div class="column-item">
                    <div class="column-icon">📚</div>
                    <h3>学习天地</h3>
                    <p>分享学习资料、学习方法和考试技巧，帮助同学们提高学习成绩。</p>
                </div>
                <div class="column-item">
                    <div class="column-icon">🎨</div>
                    <h3>文艺风采</h3>
                    <p>展示同学们的文学、美术、音乐等文艺作品，丰富班级文化生活。</p>
                </div>
                <div class="column-item">
                    <div class="column-icon">🏃</div>
                    <h3>体育健康</h3>
                    <p>记录班级体育活动、健身心得和健康知识，倡导健康的生活方式。</p>
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'footer.php'; ?>
</body>
</html>