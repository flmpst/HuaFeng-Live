<?php
// 生成 CSRF 令牌
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 验证 CSRF 令牌
function validateCSRFToken($token)
{
    if (isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token) {
        return true;
    }
    return false;
}

use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Modules\TokenManager;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查 CSRF 令牌
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        die("非法请求，CSRF 令牌验证失败！");
    }

    $auth = isset($_POST['auth']) ? $_POST['auth'] : null;
    $callback = isset($_GET['callback']) ? htmlspecialchars($_GET['callback'], ENT_QUOTES, 'UTF-8') : '';

    if ($auth === 'true' && !empty($callback)) {
        // 生成token
        $tokenManager = new TokenManager();
        $userHelpers = new User();

        try {
            $userInfo = $userHelpers->getUserInfoByEnv();
            $token = $tokenManager->generateToken($userInfo['user_id'], '+1 year', null, 'clientAuth');
            header("Location: $callback?succeed=true&msg=授权成功&token=$token");
            exit;
        } catch (Throwable $e) {
            header("Location: $callback?succeed=false&msg=授权服务端错误");
            exit;
        }
    } else {
        header("Location: $callback?succeed=false&msg=用户拒绝授权");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授权确认</title>
    <link rel="stylesheet" href="/StaticResources/css/common.css?<?= FRAMEWORK_VERSION ?>">
    <link href="https://cdn.bootcdn.net/ajax/libs/mdui/1.0.2/css/mdui.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/normalize/8.0.1/normalize.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 100%;
        }

        .mdui-container {
            padding: 20px;
        }

        .mdui-btn {
            margin-top: 20px;
        }

        .response-message {
            margin-top: 20px;
            font-size: 18px;
        }

        .mdui-container {
            padding-top: 70px;
        }
    </style>
</head>

<body class="mdui-theme-primary-indigo mdui-theme-accent-pink">

    <div class="mdui-appbar mdui-appbar-fixed mdui-color-grey-800">
        <nav class="mdui-toolbar">
            <a>花枫 Live - 第三方应用授权</a>
            <div class="mdui-toolbar-spacer"></div>
            <a href="https://dfggmc.top" target="_blank" class="mdui-btn">
                花枫官网 <i class="mdui-icon material-icons">link</i>
            </a>
        </nav>
    </div>

    <div class="mdui-container mdui-typo">
        <h3>是否授权此客户端？</h3>
        <p>客户端ID <strong><?= $_GET['clientid'] ?></strong> 请求登录。</p>
        <p>注意：请授权信任的客户端</p>

        <form id="authForm" method="post" action="">
            <input type="hidden" id="authValue" name="auth" value="">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

            <button type="button" class="mdui-btn mdui-btn-raised mdui-color-theme" id="allow">允许</button>
            <button type="button" class="mdui-btn mdui-btn-raised mdui-color-grey" id="deny">拒绝</button>
        </form>

        <div class="response-message" id="responseMessage"></div>
    </div>

    <script>
        const allowButton = document.getElementById('allow');
        const denyButton = document.getElementById('deny');
        const authValue = document.getElementById('authValue');
        const authForm = document.getElementById('authForm');

        allowButton.addEventListener('click', function() {
            authValue.value = 'true';
            authForm.submit();
        });

        denyButton.addEventListener('click', function() {
            authValue.value = 'false';
            authForm.submit();
        });
    </script>

</body>

</html>