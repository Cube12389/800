<?php require_once 'config.php'; ?>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

header {
    background-color: #f8f9fa;
    color: #333;
    padding: 10px 0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.container {
    width: 90%;
    margin: 0 auto;
    max-width: 1200px;
}

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 1.5rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    color: #333;
}

.menu-toggle {
    display: none;
    cursor: pointer;
}

.bar {
    display: block;
    width: 25px;
    height: 3px;
    margin: 5px auto;
    transition: all 0.3s ease-in-out;
    background-color: #333;
}

.nav-links {
    list-style: none;
    display: flex;
}

.nav-links li {
    margin-left: 20px;
}

.nav-links a {
    color: #333;
    text-decoration: none;
    font-size: 1rem;
    padding: 8px 16px;
    border-radius: 4px;
    transition: all 0.3s ease;
    display: inline-block;
}

.nav-links a:hover {
    color: #fff;
    background-color: #007bff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* 用户中心按钮样式 */
.nav-links .user-button {
    background-color: #28a745;
    color: #fff;
    font-weight: bold;
}

.nav-links .user-button:hover {
    background-color: #218838;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

@media screen and (max-width: 768px) {
    .menu-toggle {
        display: block;
    }
    
    .nav-links {
        position: fixed;
        left: -100%;
        top: 70px;
        flex-direction: column;
        background-color: #f8f9fa;
        width: 100%;
        text-align: center;
        transition: 0.3s;
        box-shadow: 0 10px 27px rgba(0, 0, 0, 0.1);
        padding: 20px 0;
    }
    
    .nav-links.active {
        left: 0;
    }
    
    .nav-links li {
        margin: 15px 0;
    }
    
    .nav-links a {
        padding: 10px 20px;
        color: #333;
    }
    
    .nav-links a:hover {
        color: #fff;
        background-color: #007bff;
    }
    
    /* 移动端用户中心按钮样式 */
    .nav-links .user-button {
        background-color: #28a745;
        color: #fff;
        font-weight: bold;
    }
    
    .nav-links .user-button:hover {
        background-color: #218838;
    }
}

/* 移动端菜单动画 */
.menu-toggle.active .bar:nth-child(2) {
    opacity: 0;
}

.menu-toggle.active .bar:nth-child(1) {
    transform: translateY(8px) rotate(45deg);
}

.menu-toggle.active .bar:nth-child(3) {
    transform: translateY(-8px) rotate(-45deg);
}
</style>
<header>
    <div class="container">
        <nav class="navbar">
            <div class="logo">
                <img src="asstes/favicon.ico" alt="网站图标" style="width: 30px; height: 30px; vertical-align: middle; margin-right: 10px;">
                班级网站
            </div>
            <div class="menu-toggle" id="mobile-menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">首页</a></li>
                <li><a href="dynamic.php">动态</a></li>
                <li><a href="news_column.php">专栏</a></li>
                <?php if (isLoggedIn()): ?>
                    <?php
                        // 获取当前用户信息
                        function getCurrentUserInfo() {
                            $userId = getCurrentUserId();
                            if (!$userId) {
                                return false;
                            }
                            
                            try {
                                $db = getDB();
                                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                                $stmt->execute([$userId]);
                                $user = $stmt->fetch();
                                return $user;
                            } catch (PDOException $e) {
                                return false;
                            }
                        }
                        
                        $userInfo = getCurrentUserInfo();
                        $username = $userInfo['username'] ?? '用户';
                        $avatar = $userInfo['avatar'] ?? 'BeginUrse.jfif';
                        $avatarPath = "asstes/UrseIcon/{$avatar}";
                    ?>
                    <li>
                        <a href="user_home.php" class="user-button" style="display: flex; align-items: center; gap: 8px;">
                            <img src="<?php echo $avatarPath; ?>" alt="用户头像" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                            <?php echo htmlspecialchars($username); ?>
                        </a>
                    </li>
                <?php else: ?>
                    <li><a href="login.php">登录</a></li>
                    <li><a href="register.php">注册</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<script>
    const menuToggle = document.getElementById('mobile-menu');
    const navLinks = document.querySelector('.nav-links');
    
    menuToggle.addEventListener('click', function() {
        menuToggle.classList.toggle('active');
        navLinks.classList.toggle('active');
    });
    
    // 点击导航链接后关闭菜单
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', function() {
            menuToggle.classList.remove('active');
            navLinks.classList.remove('active');
        });
    });
</script>