<?php

use HuaFengLive\Helpers\UserHelpers;
use HuaFengLive\Modules\TokenModules;
use XQPF\Core\Modules\AppAuth;

// 初始化状态变量


try {
    // 验证必要参数
    if (empty($_GET['callback'])) {
        throw new Exception('缺少必要参数: callback');
    }

    if (empty($_GET['clientid'])) {
        throw new Exception('缺少必要参数: clientid');
    }

    // 过滤和验证回调URL
    $callback = filter_var($_GET['callback'], FILTER_SANITIZE_URL);
    if (!filter_var($callback, FILTER_VALIDATE_URL)) {
        throw new Exception('无效的回调地址');
    }

    // 验证客户端应用是否存在
    $appInfo = AppAuth::getAppById($_GET['clientid']);
    $state['app_info'] = $appInfo;

    // 验证回调地址
    if ($appInfo["redirect_uri"] !== $callback){
        throw new Exception('无效的回调地址');
    }

    // 处理POST请求
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            throw new Exception('非法请求，CSRF令牌验证失败');
        }

        $auth = $_POST['auth'] ?? null;
        $tokenManager = new TokenModules;
        $userHelpers = new UserHelpers;

        if ($auth === 'true') {
            $userInfo = $userHelpers->getUserInfoByEnv();
            if (empty($userInfo)) {
                throw new Exception('用户未登录');
            }

            $token = $tokenManager->generateToken($userInfo['user_id'], 'clientAuth', '+1 year', null,  ['应用名：' => $appInfo["app_name"]]);
            unset($_SESSION['csrf_token']);
            header("Location: " . $callback . '?succeed=true&msg=' . urlencode('授权成功') . '&token=' . urlencode($token));
            exit;
        } else {
            header("Location: " . $callback . '?succeed=false&msg=' . urlencode('用户拒绝授权'));
            exit;
        }
    }
} catch (Exception $e) {
    $state['message'] = $e->getMessage();
    $state['show_form'] = false; // 参数验证失败时不显示表单
}

// 生成CSRF令牌
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 验证CSRF令牌
function validateCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>第三方客户端授权确认 - <?= $state['clientid'] ?> | 花枫 Live</title>
    <meta name="description" content="花枫 Live 第三方应用授权页面">
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

        .auth-card {
            padding: 30px;
            text-align: center;
        }

        .auth-icon {
            font-size: 72px;
            margin-bottom: 20px;
        }

        .info {
            color: #2196F3;
        }

        .success {
            color: #4CAF50;
        }

        .error {
            color: #F44336;
        }

        .auth-message {
            font-size: 18px;
            margin: 20px 0;
            line-height: 1.6;
        }

        .client-id {
            word-break: break-all;
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            margin: 15px 0;
            font-family: monospace;
        }

        .app-info {
            text-align: left;
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }

        .mdui-btn {
            margin: 10px;
            border-radius: 4px;
            min-width: 120px;
        }

        @media (max-width: 600px) {
            .mdui-container {
                margin-top: 60px;
                padding: 15px;
            }

            .auth-card {
                padding: 20px;
            }
        }
    </style>
</head>

<body class="mdui-theme-primary-indigo mdui-theme-accent-pink">
    <div class="mdui-appbar mdui-appbar-fixed mdui-color-grey-800">
        <div class="mdui-toolbar">
            <a href="/" class="mdui-typo-headline">客户端授权</a>
            <div class="mdui-toolbar-spacer"></div>
            <a href="https://dfggmc.top" target="_blank" class="mdui-btn mdui-btn-dense">
                <i class="mdui-icon material-icons">link</i> 花枫官网
            </a>
        </div>
    </div>

    <div class="mdui-container">
        <div class="auth-card">
            <?php if (!empty($state['message'])): ?>
                <div class="auth-icon error">
                    <i class="mdui-icon material-icons">error</i>
                </div>
                <h2 class="mdui-typo-title">授权请求无效</h2>
                <div class="auth-message">
                    <?= htmlspecialchars($state['message'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <a href="/" class="mdui-btn mdui-btn-raised mdui-ripple mdui-color-theme">
                    <i class="mdui-icon material-icons">home</i> 返回首页
                </a>
            <?php elseif ($state['show_form']): ?>
                <div class="auth-icon info">
                    <i class="mdui-icon material-icons">verified_user</i>
                </div>
                <h2 class="mdui-typo-title">第三方应用授权</h2>

                <?php if ($state['app_info']): ?>
                    <div class="app-info">
                        <div><strong>应用名称:</strong> <?= htmlspecialchars($state['app_info']['app_name']) ?></div>
                        <?php if (!empty($state['app_info']['app_description'])): ?>
                            <div><strong>应用描述:</strong> <?= htmlspecialchars($state['app_info']['app_description']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="auth-message">
                    该应用请求访问您的账户
                </div>

                <p>
                    请确认您信任此应用程序
                </p>

                <form id="authForm" method="post">
                    <input type="hidden" id="authValue" name="auth" value="">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <button type="button" class="mdui-btn mdui-btn-raised mdui-ripple mdui-color-theme" id="allow">
                        <i class="mdui-icon material-icons">check</i> 允许
                    </button>
                    <button type="button" class="mdui-btn mdui-btn-raised mdui-ripple mdui-color-grey" id="deny">
                        <i class="mdui-icon material-icons">close</i> 拒绝
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const allowButton = document.getElementById('allow');
            const denyButton = document.getElementById('deny');
            const authForm = document.getElementById('authForm');

            if (allowButton && denyButton && authForm) {
                allowButton.addEventListener('click', function() {
                    document.getElementById('authValue').value = 'true';
                    authForm.submit();
                });

                denyButton.addEventListener('click', function() {
                    document.getElementById('authValue').value = 'false';
                    authForm.submit();
                });
            }
        });
    </script>
    <?php
    $this->loadView('/module/footer');
    ?>
</html>