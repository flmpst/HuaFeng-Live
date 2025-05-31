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
            if (isset($_SESSION['captcha_token'], $_POST['captcha_token']) && $_SESSION['captcha_token'] === $_POST['captcha_token']) {
                unset($_SESSION['captcha_token']);
                $userController->auth($_POST['email'], $_POST['password']);
            } else {
                $helpers->jsonResponse(401, '入机验证未通过');
            }
            break;
        case 'clientAuth':
            // 验证clientid
            $clientid = $_GET['clientid'] ?? '';
            $method = $_GET['method'] ?? '';

            // 前端确认第三方客户端
            // 生成一个临时的token，过期时间为1小时
            if ($method === 'webAuth') {
                $tokenManager->generateToken($userHelpers->getUserInfoByEnv()['user_id'], 'clientAuth', '+ 1hour', $clientid, ['说明' => '前端临时验证']);
                $helpers->jsonResponse(
                    200,
                    true
                );
            }

            // 客户端轮询请求
            if ($tokenManager->validateToken($clientid, 'clientAuth')) {
                // 如果验证通过，生成新的token
                $token = $tokenManager->generateToken($userHelpers->getUserInfoByEnv()['user_id'], 'clientAuth', '+ 1year', null, ['clientid' => $clientid]);
                $helpers->jsonResponse(200, true, [$token]);
            } else {
                $helpers->jsonResponse(401, 'ID不正确');
            }
            break;
        case 'update':
            $userData = $userHelpers->getUserInfoByEnv();
            if (empty($userData) || !isset($userData['user_id'])) {
                $helpers->jsonResponse(401, '未登录');
            }

            $updateData = ['username' => htmlspecialchars($_POST['username'])];

            // 处理头像更新
            if (!empty($_POST['avatar_path'])) {
                $updateData['avatar'] = htmlspecialchars($_POST['avatar_path']);
            }

            // 处理密码更新
            if (!empty($_POST['password']) && !empty($_POST['newPassword'])) {
                // 验证当前密码是否正确
                $isValid = $userController->verifyPassword($userData['user_id'], $_POST['password']);
                if (!$isValid) {
                    $helpers->jsonResponse(401, '当前密码不正确');
                }

                // 更新密码
                $passwordHash = password_hash($_POST['newPassword'], PASSWORD_DEFAULT);
                $updateData['password'] = $passwordHash;
            }

            $update = $userController->updateUser($userData['user_id'], $updateData);
            if ($update) {
                $helpers->jsonResponse(200, '更新成功');
            } else {
                $helpers->jsonResponse(406, '更新失败');
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
