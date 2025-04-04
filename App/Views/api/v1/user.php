<?php

use ChatRoom\Core\Config\App;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Modules\TokenManager;
use ChatRoom\Core\Controller\UserController;

$appConfig = new App;
$userHelpers = new User;
$tokenManager = new TokenManager;
$userController = new UserController;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = isset(explode('/', trim($uri, '/'))[3]) ? explode('/', trim($uri, '/'))[3] : null;

// 验证 API 名称是否符合字母和数字的格式，且长度不超过 30
if (preg_match('/^[a-zA-Z0-9]{1,30}$/', $method)) {
    switch ($method) {
        case 'get':
            $helpers->jsonResponse(200, true, $userHelpers->getUserInfoByEnv());
            break;
        case 'captcha':
            $captcha = new \ChatRoom\Core\Modules\Captcha();
            $verify = $captcha->validate($_POST['lot_number'], $_POST['captcha_output'], $_POST['pass_token'], $_POST['gen_time']);
            if ($verify['status'] !== 'success' || $verify['result'] === 'fail') {
                $helpers->jsonResponse(406, $verify['reason'] ?? $verify['msg']);
            } else {
                $token = bin2hex(hash('sha256', random_bytes(32) . $_POST['lot_number'], true));
                $_SESSION['captcha_token'] = $token;
                $helpers->jsonResponse(200, true, ['token' => $token]);
            }
            break;
        case 'auth':
            if ($_SESSION['captcha_token'] === $_POST['captcha_token']) {
                unset($_SESSION['captcha_token']);
                $userController->auth($_POST['email'], $_POST['password']);
            } else {
                $helpers->jsonResponse(401, '入机验证未通过');
            }
            break;
        case 'clientAuth':
            // 验证clientid
            $clientid = $_GET['clientid'];
            $method = $_GET['method'];
            if ($method === 'webAuth') {
                $tokenManager->generateToken($userHelpers->getUserInfoByEnv()['user_id'], '+ 1year', $clientid, 'clientAuth');
            }
            if ($tokenManager->validateToken($clientid, 'clientAuth')) {
                // 如果验证通过，生成新的token
                $token = $tokenManager->generateToken($userHelpers->getUserInfoByEnv()['user_id'], '+ 1year', null, 'clientAuth');
                $helpers->jsonResponse(200, true, [$token]);
            } else {
                $helpers->jsonResponse(401, 'ID不正确');
            }
            break;
        case 'update':
            $userData = $userHelpers->getUserInfoByEnv();
            if (empty($userData)) {
                $helpers->jsonResponse(401, '未登录');
            }
            $update = $userController->updateUser($userData['user_id'], ['username' => htmlspecialchars($_POST['username'])]);
            if ($update) {
                $helpers->jsonResponse(200, true);
            } else {
                $helpers->jsonResponse(406, $update);
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
