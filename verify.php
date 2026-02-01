<?php
// 包含配置文件
require_once 'config.php';

// 设置响应头为JSON
header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => ''
];

// 处理发送验证码请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_code') {
    $type = $_POST['type'] ?? ''; // 'email' 或 'phone'
    $identifier = $_POST['identifier'] ?? ''; // 邮箱或手机号
    
    if (empty($type) || empty($identifier)) {
        $response['message'] = '请提供验证类型和邮箱/手机号';
    } elseif (!in_array($type, ['email', 'phone'])) {
        $response['message'] = '验证类型无效';
    } else {
        try {
            // 生成6位随机验证码
            $verificationCode = rand(100000, 999999);
            $expireTime = time() + 300; // 5分钟过期
            
            // 存储验证码到会话或数据库
            // 这里简化处理，实际项目中应该存储到数据库并与用户关联
            session_start();
            $_SESSION['verification_code'] = $verificationCode;
            $_SESSION['verification_expire'] = $expireTime;
            $_SESSION['verification_identifier'] = $identifier;
            
            // 模拟发送验证码
            // 实际项目中应该使用真实的邮件发送或短信发送服务
            if ($type === 'email') {
                // 发送邮件验证码
                // 这里只是模拟，实际项目中应该使用SMTP发送邮件
                $response['success'] = true;
                $response['message'] = '验证码已发送到您的邮箱';
                $response['code'] = $verificationCode; // 仅用于测试，实际项目中不应该返回验证码
            } else {
                // 发送短信验证码
                // 这里只是模拟，实际项目中应该使用短信API发送短信
                $response['success'] = true;
                $response['message'] = '验证码已发送到您的手机号';
                $response['code'] = $verificationCode; // 仅用于测试，实际项目中不应该返回验证码
            }
        } catch (Exception $e) {
            $response['message'] = '发送验证码失败，请稍后重试';
        }
    }
}

// 处理验证验证码请求
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_code') {
    $code = $_POST['code'] ?? '';
    
    if (empty($code)) {
        $response['message'] = '请输入验证码';
    } else {
        try {
            session_start();
            
            if (!isset($_SESSION['verification_code']) || !isset($_SESSION['verification_expire'])) {
                $response['message'] = '验证码不存在或已过期';
            } elseif (time() > $_SESSION['verification_expire']) {
                $response['message'] = '验证码已过期';
                // 清除过期的验证码
                unset($_SESSION['verification_code']);
                unset($_SESSION['verification_expire']);
                unset($_SESSION['verification_identifier']);
            } elseif ($code !== strval($_SESSION['verification_code'])) {
                $response['message'] = '验证码错误';
            } else {
                // 验证码正确
                $response['success'] = true;
                $response['message'] = '验证成功';
                
                // 清除验证码
                unset($_SESSION['verification_code']);
                unset($_SESSION['verification_expire']);
                unset($_SESSION['verification_identifier']);
            }
        } catch (Exception $e) {
            $response['message'] = '验证失败，请稍后重试';
        }
    }
}

// 输出JSON响应
echo json_encode($response);
?>