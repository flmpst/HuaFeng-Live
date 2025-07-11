<?php

namespace HuaFengLive\Controller\API\v1;

use HuaFengLive\Controller\APIController;
use HuaFengLive\Controller\LiveController;
use HuaFengLive\Controller\ChatController;
use HuaFengLive\Helpers\UserHelpers;
use HuaFengLive\Modules\FileModules;

class Live extends APIController
{
    public function handle(array $requestData)
    {
        $liveController = new LiveController($requestData['live_id'] ?? null);
        $chatController = new ChatController();
        $userHelpers = new UserHelpers();

        $method = $requestData['_method'] ?? '';

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg', 'image/bmp'];
        $fileUpload = new FileModules($allowedTypes, 5 * 1024 * 1024); // 5MB

        switch ($method) {
            case 'list':
                $list = $liveController->getAll();
                $data = [];
                foreach ($list as $id => $item) {
                    $author = $userHelpers->getUserInfo(null, $item['user_id']);
                    $OnlineUsers = $chatController->getOnlineUsers($item['id']);
                    $currentTime = time();
                    $filteredOnlineUsers = array_filter($OnlineUsers, function ($user) use ($currentTime) {
                        return isset($user['last_time']) && ($user['last_time'] >= $currentTime - 60);
                    });
                    $data[] = [
                        'id' => (int)$item['id'],
                        'name' => $this->h($item['name']),
                        'pic' => $this->h($item['pic']),
                        'status' => $this->h($item['status']),
                        'author' => $this->h($author['username']),
                        'authorAvatar' => $this->h($userHelpers->getAvatar($userHelpers->getUserInfo(null, $item['user_id'])['email'])),
                        'peoples' => (int)count($filteredOnlineUsers),
                        'description' => $this->h($item['description']),
                    ];
                }
                return ['status' => 200, 'data' => ['list' => $data]];

            case 'get':
                $data = $liveController->get((int)$requestData['live_id']);
                if (!$data) {
                    return ['status' => 404, 'message' => '直播间不存在'];
                }
                $data['videoSource'] = $this->h($data['video_source']);
                $data['videoSourceType'] = $this->h($data['video_source_type']);
                $data['author'] = $this->h($userHelpers->getUserInfo(null, $data['user_id'])['username']);
                $data['authorAvatar'] = $this->h($userHelpers->getAvatar($userHelpers->getUserInfo(null, $data['user_id'])['email']));
                unset($data['video_source_type'], $data['video_source']);
                return ['status' => 200, 'data' => $data];

            case 'create':
            case 'update':
                $userInfo = $userHelpers->getUserInfoByEnv();
                if ($userInfo['user_id'] === null) {
                    return ['status' => 403, 'message' => '未登录'];
                }

                // 验证必填字段
                $requiredFields = ['name', 'description', 'videoSource', 'videoSourceType'];
                foreach ($requiredFields as $field) {
                    if (empty($requestData[$field])) {
                        return ['status' => 400, 'message' => "字段 {$field} 不能为空"];
                    }
                }

                $name = $this->h($requestData['name']);
                $description = $this->h($requestData['description']);
                $videoSource = $this->h($requestData['videoSource']);
                $videoSourceType = $this->h($requestData['videoSourceType']);

                // 处理图片上传
                $picUrl = null;
                if (!empty($requestData['_files']['pic']) && $requestData['_files']['pic']['error'] === UPLOAD_ERR_OK) {
                    $picUrl = $fileUpload->upload($requestData['_files']['pic'], $userInfo['user_id']);
                } elseif (!empty($requestData['pic'])) {
                    if (!filter_var($requestData['pic'], FILTER_VALIDATE_URL)) {
                        return ['status' => 400, 'message' => '封面URL格式不正确'];
                    }
                    $picUrl = $this->h($requestData['pic']);
                }

                // 验证视频源URL和类型
                if (!filter_var($videoSource, FILTER_VALIDATE_URL)) {
                    return ['status' => 400, 'message' => '直播源URL格式不正确'];
                }
                if (!in_array($videoSourceType, ['flv', 'mp4', 'm3u8'])) {
                    return ['status' => 400, 'message' => '直播源类型不支持'];
                }

                $data = [
                    'user_id' => $userInfo['user_id'],
                    'name' => $name,
                    'pic' => $picUrl,
                    'video_source' => $videoSource,
                    'video_source_type' => $videoSourceType,
                    'description' => $description,
                    'css' => $requestData['css'] ?? ''
                ];

                if ($method === 'create') {
                    $add = $liveController->set($data);
                    if ($add) {
                        return ['status' => 200, 'message' => '创建成功', 'data' => ['id' => $add]];
                    } else {
                        return ['status' => 500, 'message' => '创建直播间失败'];
                    }
                } else {
                    $update = $liveController->set($data, (int)$requestData['liveId']);
                    if ($update) {
                        return ['status' => 200, 'message' => '更新成功'];
                    } else {
                        return ['status' => 500, 'message' => '更新失败'];
                    }
                }

            case 'delete':
                $userInfo = $userHelpers->getUserInfoByEnv();
                if ($userInfo['user_id'] === null) {
                    return ['status' => 403, 'message' => '未登录'];
                }

                $liveData = $liveController->get((int)$requestData['liveId']);
                if (empty($liveData)) {
                    return ['status' => 404, 'message' => '直播间不存在'];
                }

                // 权限检查
                if ($userInfo['group_id'] !== 1 && $userInfo['user_id'] !== $liveData['user_id']) {
                    return ['status' => 403, 'message' => '无权删除此直播间'];
                }

                $delete = $liveController->delete((int)$requestData['liveId']);
                if ($delete) {
                    return ['status' => 200, 'message' => '删除成功'];
                } else {
                    return ['status' => 500, 'message' => '删除失败'];
                }

            default:
                return ['status' => 406, 'message' => '无效的请求方法'];
        }
    }
}
