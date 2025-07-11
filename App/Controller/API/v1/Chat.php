<?php

namespace HuaFengLive\Controller\API\v1;

use HuaFengLive\Controller\ChatController;
use HuaFengLive\Controller\LiveController;
use HuaFengLive\Helpers\UserHelpers;

class Chat
{
    public function handle(array $requestData)
    {
        $userHelpers = new UserHelpers();
        $chat = new ChatController;
        $live = new LiveController();
        $roomId = isset($requestData['room_id']) ? (int)$requestData['room_id'] : 0;

        if (!$live->get($roomId)) {
            return ['status' => 404, 'message' => '直播间不存在'];
        }

        $method = $requestData['_method'] ?? '';
        $userInfo = $userHelpers->getUserInfoByEnv();

        switch ($method) {
            case 'send':
                if (empty($userInfo)) {
                    return ['status' => 401, 'message' => '未登录'];
                }

                $message = htmlspecialchars(trim($requestData['message'] ?? ''), ENT_QUOTES, 'UTF-8');
                if (empty($message)) {
                    return ['status' => 406, 'message' => '消息内容不能为空'];
                }

                $danmakuParams = [
                    'color' => $requestData['color'] ?? '#FFFFFF',
                    'type' => $requestData['type'] ?? 'right',
                    'size' => $requestData['size'] ?? 25,
                ];

                if ($chat->sendMessage($userInfo, $message, $roomId, $danmakuParams)) {
                    return ['status' => 200, 'message' => '消息发送成功'];
                } else {
                    return ['status' => 406, 'message' => '消息发送失败'];
                }

            case 'get':
                $offset = isset($requestData['offset']) ? (int)$requestData['offset'] : 0;
                $limit = isset($requestData['limit']) ? (int)$requestData['limit'] : 10;

                $result = $chat->getMessages($roomId, $offset, $limit);
                if (!$result) {
                    return ['status' => 406, 'message' => '获取消息失败'];
                }
                return ['status' => 200, 'data' => $result];

            case 'count':
                $result = $chat->getMessageCount($roomId);
                if (!$result) {
                    return ['status' => 406, 'message' => '获取消息总数失败'];
                }
                return ['status' => 200, 'data' => ['count' => $result]];

            default:
                return ['status' => 406, 'message' => '无效的请求方法'];
        }
    }
}
