<?php

use ChatRoom\Core\Config\App;
use ChatRoom\Core\Helpers\User;
use FluxSoft\Turnstile\Turnstile;
use ChatRoom\Core\Database\SqlLite;
use ChatRoom\Core\Modules\TokenManager;
use ChatRoom\Core\Controller\UserController;

$appConfig = new App;
$isLogin = new User;
$turnstile  = new Turnstile($appConfig->cloudflare['turnstile']['secretKey']);
$tokenManager = new TokenManager;
$userController = new UserController;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = isset(explode('/', trim($uri, '/'))[3]) ? explode('/', trim($uri, '/'))[3] : null;

// 验证 API 名称是否符合字母和数字的格式，且长度不超过 30
if (preg_match('/^[a-zA-Z0-9]{1,30}$/', $method)) {
    switch ($method) {
        case 'get':
            $helpers->jsonResponse(200, true, $userHelpers->getUserInfoByEnv());
        case 'auth':
            $verifyResponse = $turnstile->verify($_POST['cf-turnstile-response'], $_SERVER['REMOTE_ADDR']);
            if ($verifyResponse->success) {
                $userController->auth($_POST['email'], $_POST['password']);
            } else {
                $helpers->jsonResponse(403, UserController::CAPTCHA_ERROR);
            }
            break;
        case 'update':
            $update = $userHelpers->updateUser($userHelpers->getUserInfoByEnv()['user_id'], ['username' => htmlspecialchars($_POST['username'])]);
            if ($update) {
                $helpers->jsonResponse(200, true);
            } else {
                $helpers->jsonResponse(406, $update);
            }
            break;
        case 'verifyEmail':
            if ($tokenManager->validateToken($_GET['token'])) {
                try {
                    $userInfoByToken = $tokenManager->getInfo($_GET['token']);
                    $db = SqlLite::getInstance()->getConnection();
                    $sqlUpdate = "UPDATE users SET status = 1 WHERE user_id = :user_id";
                    $stmtUpdate = $db->prepare($sqlUpdate);
                    $stmtUpdate->bindParam(':user_id', $userInfoByToken['user_id'], PDO::PARAM_INT);
                    if ($stmtUpdate->execute()) {
                        $tokenManager->delet($userInfoByToken['user_id']);
                        $_SESSION['user_login_info'] = json_encode($userInfoByToken);
                        echo "
                        <script>
                            if (confirm('邮箱验证成功！点击确定返回首页')) {
                                window.location.href = '/';
                            }
                        </script>";
                    } else {
                        echo '未知错误';
                    }
                } catch (PDOException $e) {
                    throw new PDOException('状态更新失败: ' . $e);
                }
            } else {
                $helpers->jsonResponse(403, 'token验证失败');
            }
            break;
        case 'logout':
            unset($_SESSION['user_login_info']);
            $helpers->jsonResponse(200, true);
            break;
        default:
            $helpers->jsonResponse(400, UserController::INVALID_METHOD_MESSAGE);
    }
} else {
    // 如果 method 不符合字母数字格式，返回 400 错误
    $helpers->jsonResponse(400, "Invalid API method");
}
