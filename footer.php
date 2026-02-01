<style>
footer {
    background-color: #f8f9fa;
    color: #333;
    padding: 40px 0 20px;
    margin-top: 40px;
    border-top: 1px solid #e9ecef;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    margin-bottom: 30px;
}

.footer-column {
    flex: 1;
    min-width: 250px;
    margin-bottom: 20px;
}

.footer-column h3 {
    font-size: 1.2rem;
    margin-bottom: 15px;
    color: #007bff;
}

.footer-column p {
    margin-bottom: 10px;
    line-height: 1.5;
}

.footer-column ul {
    list-style: none;
}

.footer-column ul li {
    margin-bottom: 10px;
}

.footer-column ul li a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-column ul li a:hover {
    color: #007bff;
}

.footer-bottom {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
    font-size: 0.9rem;
    color: #6c757d;
}

@media screen and (max-width: 768px) {
    .footer-content {
        flex-direction: column;
    }
    
    .footer-column {
        margin-bottom: 30px;
    }
}
</style>
<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-column">
                <h3>班级网站</h3>
                <p>我们的班级网站，记录班级生活的点点滴滴</p>
            </div>
            <div class="footer-column">
                <h3>快速链接</h3>
                <ul>
                    <li><a href="index.php">首页</a></li>
                    <li><a href="dynamic.php">动态</a></li>
                    <li><a href="news_column.php">专栏</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>联系方式</h3>
                <p>邮箱：class@example.com</p>
                <p>电话：123-4567-8910</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 班级网站 版权所有</p>
        </div>
    </div>
</footer>