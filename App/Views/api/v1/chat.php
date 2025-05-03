<?php

use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Modules\TokenManager;
use ChatRoom\Core\Controller\Chat;
use ChatRoom\Core\Controller\Live;

$chatConfig = new Chat;
$userHelpers = new User;
$Chat = new Chat;

$tokenManager = new TokenManager;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = isset(explode('/', trim($uri, '/'))[3]) ? explode('/', trim($uri, '/'))[3] : null;

// 验证 API 名称是否符合字母和数字的格式，且长度不超过 30
if (preg_match('/^[a-zA-Z0-9]{1,30}$/', $method)) {
    $message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');
    $userInfo = $userHelpers->getUserInfoByEnv();
    $rooId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
    $live = new Live;
    if ($live->get($rooId)) {
        switch ($method) {
            case 'send':
                if (empty($userInfo)) {
                    $helpers->jsonResponse(401, '未登录');
                }
                // 检查是否有消息或发送
                if (empty($message)) {
                    $helpers->jsonResponse(406, '消息内容不能为空');
                    return;
                }

                // 调用Chat处理消息发送
                if ($Chat->sendMessage($userInfo, $message, $rooId)) {
                    $helpers->jsonResponse(200, Chat::MESSAGE_SUCCESS);
                } else {
                    $helpers->jsonResponse(406, Chat::MESSAGE_SEND_FAILED);
                }
                break;
            case 'get':
                $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

                $result = $Chat->getMessages($rooId, $offset, $limit);
                if (!$result) {
                    $helpers->jsonResponse(406, Chat::MESSAGE_FETCH_FAILED);
                } else {
                    $helpers->jsonResponse(200, true, $result);
                }
                break;
            case 'count':
                $result = $Chat->getMessageCount($rooId);
                if (!$result) {
                    $helpers->jsonResponse(406, '获取消息总数失败');
                } else {
                    $helpers->jsonResponse(200, true, ['count' => $result]);
                }
                break;
            default:
                // 无效的请求方法
                $helpers->jsonResponse(406, Chat::MESSAGE_INVALID_REQUEST);
        }
    } else {
        $helpers->jsonResponse(404, "直播间不存在");
    }
} else {
    // 如果 method 不符合字母数字格式，返回 400 错误
    $helpers->jsonResponse(400, "Invalid API method");
}
