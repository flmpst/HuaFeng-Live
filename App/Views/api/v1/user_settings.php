<?php

use ChatRoom\Core\Controller\UserSettings;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = isset(explode('/', trim($uri, '/'))[3]) ? explode('/', trim($uri, '/'))[3] : null;

// 创建 UserSettings 实例
$userSettings = new UserSettings();

// 验证 API 名称是否符合字母和数字的格式，且长度不超过 30
if (preg_match('/^[a-zA-Z0-9_]{1,30}$/', $method)) {
    switch ($method) {
        case 'get':
            $user_id = (int)($_GET['user_id'] ?? '');
            $client_id = h($_GET['client_id'] ?? '');
            $setting_name = h($_GET['setting_name'] ?? null);
            if (empty($user_id) || empty($client_id)) {
                $helpers->jsonResponse(400, 'user_id和client_id不能为空');
                break;
            }
            $result = $userSettings->getSettings($user_id, $client_id, $setting_name);
            $helpers->jsonResponse(200, true, $result);
            break;
        case 'set':
            $user_id = (int)h($_POST['user_id'] ?? '');
            $client_id = h($_POST['client_id'] ?? '');
            $setting_name = h($_POST['setting_name'] ?? '');
            $setting_value = h($_POST['setting_value'] ?? null);
            $update_ip = h($_SERVER['REMOTE_ADDR'] ?? '');
            if (empty($user_id) || empty($client_id) || empty($setting_name)) {
                $helpers->jsonResponse(400, 'user_id、client_id和setting_name不能为空');
                break;
            }
            $success = $userSettings->setSetting($user_id, $client_id, $setting_name, $setting_value, $update_ip);
            $helpers->jsonResponse($success ? 200 : 500, $success, ['message' => $success ? '设置成功' : '设置失败']);
            break;
        case 'delete':
            $user_id = (int)h($_POST['user_id'] ?? '');
            $client_id = h($_POST['client_id'] ?? '');
            $setting_name = h($_POST['setting_name'] ?? '');
            if (empty($user_id) || empty($client_id) || empty($setting_name)) {
                $helpers->jsonResponse(400, 'user_id、client_id和setting_name不能为空');
                break;
            }
            $success = $userSettings->deleteSetting($user_id, $client_id, $setting_name);
            $helpers->jsonResponse($success ? 200 : 500, $success, ['message' => $success ? '删除成功' : '删除失败']);
            break;
        case 'sync':
            $user_id = (int)($_POST['user_id'] ?? '');
            $source_client_id = h($_POST['source_client_id'] ?? '');
            $target_client_id = h($_POST['target_client_id'] ?? '');
            if (empty($user_id) || empty($source_client_id) || empty($target_client_id)) {
                $helpers->jsonResponse(400, 'user_id、source_client_id和target_client_id不能为空');
                break;
            }
            $success = $userSettings->syncSettings($user_id, $source_client_id, $target_client_id);
            $helpers->jsonResponse($success ? 200 : 500, $success, ['message' => $success ? '同步成功' : '同步失败']);
            break;
        default:
            $helpers->jsonResponse(406, '方法不存在');
    }
} else {
    $helpers->jsonResponse(400, "Invalid API method");
}
