<?php

namespace HuaFengLive\Controller\API\v1;

use HuaFengLive\Config\AppConfig;
use HuaFengLive\Controller\UserController;
use HuaFengLive\Helpers\UserHelpers;
use HuaFengLive\Modules\CaptchaModules;
use HuaFengLive\Modules\TokenModules;

class User
{
    public function handle(array $requestData)
    {
        $appConfig = new AppConfig();
        $userHelpers = new UserHelpers();
        $tokenManager = new TokenModules();
        $userController = new UserController();

        $method = $requestData['_method'] ?? '';

        switch ($method) {
            case 'get':
                return ['status' => 200, 'data' => $userHelpers->getUserInfoByEnv()];

            case 'captcha':
                $captcha = new CaptchaModules();
                $verify = $captcha->validate(
                    $requestData['lot_number'],
                    $requestData['captcha_output'],
                    $requestData['pass_token'],
                    $requestData['gen_time']
                );
                if ($verify['status'] !== 'success' || $verify['result'] === 'fail') {
                    return ['status' => 406, 'message' => $verify['reason'] ?? $verify['msg']];
                }
                $token = bin2hex(hash('sha256', random_bytes(32) . $requestData['lot_number'], true));
                $_SESSION['captcha_token'] = $token;
                return ['status' => 200, 'data' => ['token' => $token]];

            case 'auth':
                if (
                    isset($_SESSION['captcha_token'], $requestData['captcha_token']) &&
                    $_SESSION['captcha_token'] === $requestData['captcha_token']
                ) {
                    unset($_SESSION['captcha_token']);
                    $result = $userController->auth($requestData['email'], $requestData['password']);
                    return $result ?
                        ['status' => 200, 'message' => '登录成功'] :
                        ['status' => 401, 'message' => '登录失败'];
                }
                return ['status' => 401, 'message' => '入机验证未通过'];

            case 'update':
                $userData = $userHelpers->getUserInfoByEnv();
                if (empty($userData) || !isset($userData['user_id'])) {
                    return ['status' => 401, 'message' => '未登录'];
                }

                $updateData = [];
                if (!empty($requestData['username'])) {
                    $updateData['username'] = htmlspecialchars($requestData['username']);
                }
                if (!empty($requestData['avatar_path'])) {
                    $updateData['avatar'] = htmlspecialchars($requestData['avatar_path']);
                }
                if (!empty($requestData['password']) && !empty($requestData['new_password'])) {
                    $isValid = $userController->verifyPassword($userData['user_id'], $requestData['password']);
                    if (!$isValid) {
                        return ['status' => 401, 'message' => '当前密码不正确'];
                    }
                    if ($requestData['new_password'] !== $requestData['confirm_password']) {
                        return ['status' => 401, 'message' => '确认密码和新密码不一致'];
                    }
                    $updateData['password'] = password_hash($requestData['new_password'], PASSWORD_DEFAULT);
                }

                $update = $userController->updateUser($userData['user_id'], $updateData);
                return $update ?
                    ['status' => 200, 'message' => '更新成功'] :
                    ['status' => 406, 'message' => '更新失败'];

            case 'logout':
                unset($_SESSION['user_login_info']);
                return ['status' => 200];

            default:
                return ['status' => 400, 'message' => '无效的请求方法'];
        }
    }
}