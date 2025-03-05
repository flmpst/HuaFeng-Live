<?php

use ChatRoom\Core\Config\App;
use ChatRoom\Core\Controller\Live;
use ChatRoom\Core\Modules\TokenManager;
use ChatRoom\Core\Controller\ChatController;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = isset(explode('/', trim($uri, '/'))[3]) ? explode('/', trim($uri, '/'))[3] : null;
$liveId = isset(explode('/', trim($uri, '/'))[4]) ? explode('/', trim($uri, '/'))[4] : null;

// 验证 API 名称是否符合字母和数字的格式，且长度不超过 30
if (preg_match('/^[a-zA-Z0-9]{1,30}$/', $method)) {
    $appConfig = new App;
    $live = new Live($liveId);
    $tokenManager = new TokenManager;
    $chatController = new ChatController;
    unset($_POST['token']);
    switch ($method) {
        case 'list':
            $list = $live->getAll();
            $data = []; // 用来存放格式化后的数据
            foreach ($list as $id => $item) {
                // 获取作者用户名
                $author = $userHelpers->getUserInfo(null, $item['user_id']);
                // 获取当前聊天室的在线用户
                $OnlineUsers = $chatController->getOnlineUsers($id);
                // 获取当前时间戳
                $currentTime = time();
                // 过滤在线用户，仅保留 last_time 在三分钟内的用户
                $filteredOnlineUsers = array_filter($OnlineUsers, function ($user) use ($currentTime) {
                    // 判断 last_time 是否大于等于三分钟前的时间戳
                    return isset($user['last_time']) && ($user['last_time'] >= $currentTime - 180);
                });
                // 将过滤后的在线用户数量存入 'peoples'
                $data[] = [
                    'id' => $id,
                    'name' => $item['name'],
                    'pic' => $item['pic'],
                    'status' => $item['status'],
                    'author' => $author['username'],
                    'authorAvatar' => $userHelpers->getAvatar($userHelpers->getUserInfo(null, $item['user_id'])['email']),
                    'peoples' => count($filteredOnlineUsers), // 显示符合条件的用户数量
                    'description' => $item['description'],
                ];
            }
            $helpers->jsonResponse(200, true, ['list' => $data]);
            break;
        case 'get':
            $data = $live->get((int)$_GET['live_id']);
            if (!$data) {
                $helpers->jsonResponse(404, '直播间不存在');
            }
            $data['author'] = $userHelpers->getUserInfo(null, $data['user_id'])['username'];
            $data['authorAvatar'] = $userHelpers->getAvatar($userHelpers->getUserInfo(null, $data['user_id'])['email']);
            $helpers->jsonResponse(200, true, $data);
        case 'create':
            $userInfo = $userHelpers->getUserInfoByEnv();
            $return = [];
            $code = 200;
            $msg = true;
            if ($userInfo['user_id'] !== null) {
                // 字段验证
                if (empty($_POST['name']) || empty($_POST['description']) || empty($_POST['videoSource']) || empty($_POST['videoSourceType'])) {
                    $msg = '所有字段都是必填的，星号为可选项';
                    $code = 400;
                } elseif ($_POST['pic'] && !filter_var($_POST['pic'], FILTER_VALIDATE_URL)) {
                    $msg = '封面URL格式不正确';
                    $code = 400;
                } elseif (!filter_var($_POST['videoSource'], FILTER_VALIDATE_URL)) {
                    $msg = '直播源URL格式不正确';
                    $code = 400;
                } elseif (!in_array($_POST['videoSourceType'], ['flv', 'mp4', 'm3u8'])) {
                    $msg = '直播源类型不支持';
                    $code = 400;
                } else {
                    $_POST['status'] = 'on';

                    // 保存直播信息
                    $add = $live->set($_POST);
                    if ($add) {
                        $return = ['id' => $add];
                    }
                }
            } else {
                $msg = '未登录';
                $code = 403;
            }
            // 返回响应
            $helpers->jsonResponse($code, $msg, $return);
            break;
        case 'update':
            $userInfo = $userHelpers->getUserInfoByEnv();
            $code = 200;
            $msg = true;
            if ($live->get((int)$_GET['liveId']) === null) {
                $msg = '直播间不存在';
                $code = 404;
                $helpers->jsonResponse($code, $msg);
            }
            if ($userInfo['user_id'] !== null) {
                // 必填字段验证
                if (empty($_POST['name']) || empty($_POST['description']) || empty($_POST['videoSource']) || empty($_POST['videoSourceType'])) {
                    $msg = '所有字段都是必填的，星号为可选项';
                    $code = 400;
                } elseif ($_POST['pic'] && !filter_var($_POST['pic'], FILTER_VALIDATE_URL)) {
                    $msg = '封面URL格式不正确';
                    $code = 400;
                } elseif (!filter_var($_POST['videoSource'], FILTER_VALIDATE_URL)) {
                    $msg = '直播源URL格式不正确';
                    $code = 400;
                } elseif (!in_array($_POST['videoSourceType'], ['flv', 'mp4', 'm3u8'])) {
                    $msg = '直播源类型不支持';
                    $code = 400;
                } elseif ($userInfo['group_id'] === 1) {
                    // 如果是管理员，可以修改任何直播间
                    // 保存直播信息
                    $_POST['user_id'] = $live->get((int)$_GET['liveId'])['user_id'];
                    $add = $live->set($_POST, (int)$_GET['liveId']);
                    if ($add) {
                        $msg = true;
                    } else {
                        $msg = $add;
                    }
                    $helpers->jsonResponse($code, $msg);
                } elseif ($userInfo['user_id'] !== $live->get((int)$_GET['liveId'])['user_id']) {
                    $msg = '搞错了！这不是你的直播间😅';
                    $code = 403;
                } else {
                    // 保存直播信息
                    $add = $live->set($_POST, (int)$_GET['liveId']);
                    if ($add) {
                        $msg = true;
                    } else {
                        $msg = $add;
                    }
                }
            } else {
                $msg = '未登录';
                $code = 403;
            }
            $helpers->jsonResponse($code, $msg);
            break;
        case 'delet':
            $userInfo = $userHelpers->getUserInfoByEnv();
            $code = 200;
            $msg = true;
            if ($userInfo['group_id'] === 1) {
                // 如果是管理员，可以删除任何直播间
                $delet = $live->delete((int)$_GET['liveId']);
                if ($delet) {
                    $msg = true;
                } else {
                    $msg = $delet;
                }
                $helpers->jsonResponse($code, $msg);
            } elseif ($userInfo['user_id'] !== $live->get((int)$_GET['liveId'])['user_id']) {
                $msg = '搞错了！这不是你的直播间😅';
                $code = 403;
            } else {
                $delet = $live->delete((int)$_GET['liveId']);
                if ($delet) {
                    $msg = true;
                } else {
                    $msg = $delet;
                }
            }
            $helpers->jsonResponse($code, $msg);
            break;
        default:
            $helpers->jsonResponse(406, 'Invalid method');
            break;
    }
} else {
    // 如果 method 不符合字母数字格式，返回 400 错误
    $helpers->jsonResponse(400, "Invalid API method");
}
