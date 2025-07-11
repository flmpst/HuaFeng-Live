<?php

use HuaFengLive\Modules\TokenModules;
use XQPF\Core\Modules\Database\Base as DatabaseBase;

try {
    // 验证token是否存在且有效
    if (empty($_GET['token'])) {
        throw new Exception('验证令牌缺失');
    }

    $tokenManager = new TokenModules();
    $token = $_GET['token'];

    if ($tokenManager->validateToken($token, 'verifyEmail')) {
        $userInfoByToken = $tokenManager->getInfo($token, 'verifyEmail');

        // 验证用户信息
        if (empty($userInfoByToken['user_id'])) {
            throw new Exception('无效的用户信息');
        }

        $db = DatabaseBase::getInstance()->getConnection();
        // 开启事务确保数据一致性
        $db->beginTransaction();

        try {
            // 更新用户状态
            $sqlUpdate = "UPDATE users SET status = 1 WHERE user_id = :user_id AND status = 0";
            $stmtUpdate = $db->prepare($sqlUpdate);
            $stmtUpdate->bindParam(':user_id', $userInfoByToken['user_id'], PDO::PARAM_INT);

            if (!$stmtUpdate->execute() || $stmtUpdate->rowCount() === 0) {
                throw new Exception('邮箱已验证或用户不存在');
            }

            // 删除token
            $tokenManager->delete($userInfoByToken['user_id'], 'verifyEmail');

            // 更新会话信息
            if (isset($_SESSION['user_login_info'])) {
                $userInfo = json_decode($_SESSION['user_login_info'], true);
                $userInfo['status'] = 1;
                $userInfo['email_verified'] = true;
                $_SESSION['user_login_info'] = json_encode($userInfo);
            }

            $db->commit();

            $state = [
                'success' => true,
                'message' => '邮箱验证成功！',
                'redirect' => '/',
                'show_retry' => false
            ];
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    } else {
        // 令牌无效时允许重新发送
        $state['show_retry'] = true;
        $state['message'] = '验证令牌无效或已过期，请重新发送验证邮件。';
    }
} catch (PDOException $e) {
    $state = [
        'success' => false,
        'message' => '数据库错误: ' . $e->getMessage(),
        'redirect' => '/',
        'show_retry' => true
    ];
} catch (Exception $e) {
    $state = [
        'success' => false,
        'message' => $e->getMessage(),
        'redirect' => '/',
        'show_retry' => strpos($e->getMessage(), '过期') !== false
    ];
}
?>
<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邮箱验证状态 - 花枫 Live</title>
    <meta name="description" content="花枫 Live 邮箱验证页面">
    <link rel="stylesheet" href="/StaticResources/css/common.css?<?= FRAMEWORK_VERSION ?>">
    <link href="https://cdn.bootcdn.net/ajax/libs/mdui/1.0.2/css/mdui.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/normalize/8.0.1/normalize.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', 'Microsoft YaHei', Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            background-color: #f5f5f5;
        }

        .mdui-container {
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-top: 100px;
        }

        .verification-card {
            padding: 30px;
            text-align: center;
        }

        .verification-icon {
            font-size: 72px;
            margin-bottom: 20px;
        }

        .success {
            color: #4CAF50;
        }

        .error {
            color: #F44336;
        }

        .warning {
            color: #FF9800;
        }

        .verification-message {
            font-size: 18px;
            margin: 20px 0;
            line-height: 1.6;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .mdui-btn {
            border-radius: 4px;
            min-width: 160px;
        }

        @media (max-width: 600px) {
            .mdui-container {
                margin-top: 60px;
                padding: 15px;
            }

            .verification-card {
                padding: 20px;
            }
        }
    </style>
</head>

<body class="mdui-theme-primary-indigo mdui-theme-accent-pink">
    <div class="mdui-appbar mdui-appbar-fixed mdui-color-grey-800">
        <div class="mdui-toolbar">
            <a href="/" class="mdui-typo-headline">邮箱验证</a>
            <div class="mdui-toolbar-spacer"></div>
            <a href="https://dfggmc.top" target="_blank" class="mdui-btn mdui-btn-dense">
                <i class="mdui-icon material-icons">link</i> 花枫官网
            </a>
        </div>
    </div>

    <div class="mdui-container">
        <div class="verification-card">
            <?php if ($state['success']): ?>
                <div class="verification-icon success">
                    <i class="mdui-icon material-icons">check_circle</i>
                </div>
                <h2 class="mdui-typo-title">验证成功</h2>
                <div class="verification-message">
                    <?= htmlspecialchars($state['message'], ENT_QUOTES, 'UTF-8') ?>
                    <div class="mdui-typo-caption-opacity mdui-m-t-2">
                        <i class="mdui-icon material-icons">info</i>
                        您将在 <span id="countdown">5</span> 秒后自动跳转
                    </div>
                </div>
                <div class="action-buttons">
                    <a href="<?= htmlspecialchars($state['redirect'], ENT_QUOTES, 'UTF-8') ?>"
                        class="mdui-btn mdui-btn-raised mdui-ripple mdui-color-theme">
                        <i class="mdui-icon material-icons">account_circle</i> 立即前往主页
                    </a>
                </div>
                <script>
                    // 5秒倒计时自动跳转
                    let seconds = 5;
                    const countdown = setInterval(() => {
                        seconds--;
                        document.getElementById('countdown').textContent = seconds;
                        if (seconds <= 0) {
                            clearInterval(countdown);
                            window.location.href = "<?= $state['redirect'] ?>";
                        }
                    }, 1000);
                </script>
            <?php else : ?>
                <div class="verification-icon error">
                    <i class="mdui-icon material-icons">error</i>
                </div>
                <h2 class="mdui-typo-title">验证失败</h2>
                <div class="verification-message">
                    <?= htmlspecialchars($state['message'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="action-buttons">
                    <a href="/" class="mdui-btn mdui-btn-raised mdui-ripple mdui-color-theme">
                        <i class="mdui-icon material-icons">home</i> 返回首页
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>