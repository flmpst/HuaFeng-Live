<?php

namespace HuaFengLive\Controller\API\v1;

use HuaFengLive\Controller\APIController;
use HuaFengLive\Controller\UserSettingsController;

class UserSettings extends APIController
{
    public function handle(array $requestData)
    {
        $userSettings = new UserSettingsController();
        $method = $requestData['_method'] ?? '';

        switch ($method) {
            case 'get':
                $user_id = (int)($requestData['user_id'] ?? '');
                $client_id = $this->$this->h($requestData['client_id'] ?? '');
                $setting_name = $this->h($requestData['setting_name'] ?? null);
                if (empty($user_id) || empty($client_id)) {
                    return ['status' => 400, 'message' => 'user_id和client_id不能为空'];
                }
                $result = $userSettings->getSettings($user_id, $client_id, $setting_name);
                return ['status' => 200, 'data' => $result];

            case 'set':
                $user_id = (int)$this->h($requestData['user_id'] ?? '');
                $client_id = $this->h($requestData['client_id'] ?? '');
                $setting_name = $this->h($requestData['setting_name'] ?? '');
                $setting_value = $this->h($requestData['setting_value'] ?? null);
                $update_ip = $this->h($_SERVER['REMOTE_ADDR'] ?? '');
                if (empty($user_id) || empty($client_id) || empty($setting_name)) {
                    return ['status' => 400, 'message' => 'user_id、client_id和setting_name不能为空'];
                }
                $success = $userSettings->setSetting($user_id, $client_id, $setting_name, $setting_value, $update_ip);
                return [
                    'status' => $success ? 200 : 500,
                    'message' => $success ? '设置成功' : '设置失败'
                ];

            case 'delete':
                $user_id = (int)$this->h($requestData['user_id'] ?? '');
                $client_id = $this->h($requestData['client_id'] ?? '');
                $setting_name = $this->h($requestData['setting_name'] ?? '');
                if (empty($user_id) || empty($client_id) || empty($setting_name)) {
                    return ['status' => 400, 'message' => 'user_id、client_id和setting_name不能为空'];
                }
                $success = $userSettings->deleteSetting($user_id, $client_id, $setting_name);
                return [
                    'status' => $success ? 200 : 500,
                    'message' => $success ? '删除成功' : '删除失败'
                ];

            case 'sync':
                $user_id = (int)($requestData['user_id'] ?? '');
                $source_client_id = $this->h($requestData['source_client_id'] ?? '');
                $target_client_id = $this->h($requestData['target_client_id'] ?? '');
                if (empty($user_id) || empty($source_client_id) || empty($target_client_id)) {
                    return ['status' => 400, 'message' => 'user_id、source_client_id和target_client_id不能为空'];
                }
                $success = $userSettings->syncSettings($user_id, $source_client_id, $target_client_id);
                return [
                    'status' => $success ? 200 : 500,
                    'message' => $success ? '同步成功' : '同步失败'
                ];

            default:
                return ['status' => 406, 'message' => '方法不存在'];
        }
    }
}