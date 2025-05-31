<?php

use ChatRoom\Core\Config\App;
use ChatRoom\Core\Controller\Live;
use ChatRoom\Core\Controller\Chat;
use ChatRoom\Core\Modules\FileUploader;
use ChatRoom\Core\Modules\TokenManager;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = isset(explode('/', trim($uri, '/'))[3]) ? explode('/', trim($uri, '/'))[3] : null;
$liveId = isset(explode('/', trim($uri, '/'))[4]) ? explode('/', trim($uri, '/'))[4] : null;

// 验证 API 名称是否符合字母和数字的格式，且长度不超过 30
if (preg_match('/^[a-zA-Z0-9]{1,30}$/', $method)) {
    $appConfig = new App;
    $live = new Live($liveId);
    $tokenManager = new TokenManager;
    $chatController = new Chat;
    unset($_POST['token']);

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg', 'image/bmp'];
    $fileUpload = new FileUploader($allowedTypes, 5 * 1024 * 1024); // 5MB
    switch ($method) {
        case 'list':
            $list = $live->getAll();
            $data = []; // 用来存放格式化后的数据
            foreach ($list as $id => $item) {
                // 获取作者用户名
                $author = $userHelpers->getUserInfo(null, $item['user_id']);
                // 获取当前聊天室的在线用户
                $OnlineUsers = $chatController->getOnlineUsers($item['id']);
                // 获取当前时间戳
                $currentTime = time();
                // 过滤在线用户，仅保留 last_time 在三分钟内的用户
                $filteredOnlineUsers = array_filter($OnlineUsers, function ($user) use ($currentTime) {
                    // 判断 last_time 是否大于等于三分钟前的时间戳
                    return isset($user['last_time']) && ($user['last_time'] >= $currentTime - 60);
                });
                // 将过滤后的在线用户数量存入 'peoples'
                $data[] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'pic' => $item['pic'],
                    'status' => $item['status'],
                    'author' => $author['username'],
                    'authorAvatar' => $userHelpers->getAvatar($userHelpers->getUserInfo(null, $item['user_id'])['email']),
                    'peoples' => count($filteredOnlineUsers),
                    // 显示符合条件的用户数量
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
            $data['videoSource'] = $data['video_source'];
            $data['videoSourceType'] = $data['video_source_type'];
            $data['author'] = $userHelpers->getUserInfo(null, $data['user_id'])['username'];
            $data['authorAvatar'] = $userHelpers->getAvatar($userHelpers->getUserInfo(null, $data['user_id'])['email']);
            unset($data['video_source_type']);
            unset($data['video_source']);
            $helpers->jsonResponse(200, true, $data);
            break;
        case 'create':
            $userInfo = $userHelpers->getUserInfoByEnv();
            $return = [];
            $code = 200;
            $msg = true;

            if ($userInfo['user_id'] === null) {
                $helpers->jsonResponse(403, '未登录');
                exit;
            }

            // 验证必填字段
            $requiredFields = ['name', 'description', 'videoSource', 'videoSourceType'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    $helpers->jsonResponse(400, "字段 {$field} 不能为空");
                    exit;
                }
            }

            // 验证图片上传
            $picUrl = null;
            if (!empty($_POST['pic'])) {
                if (!filter_var($_POST['pic'], FILTER_VALIDATE_URL)) {
                    $helpers->jsonResponse(400, '封面URL格式不正确');
                    exit;
                }
                $picUrl = $_POST['pic'];
            }

            // 验证视频源URL
            if (!filter_var($_POST['videoSource'], FILTER_VALIDATE_URL)) {
                $helpers->jsonResponse(400, '直播源URL格式不正确');
                exit;
            }

            // 验证视频源类型
            if (!in_array($_POST['videoSourceType'], ['flv', 'mp4', 'm3u8'])) {
                $helpers->jsonResponse(400, '直播源类型不支持');
                exit;
            }

            // 准备数据
            $data = [
                'user_id' => $userInfo['user_id'],
                'name' => $_POST['name'],
                'pic' => $picUrl,
                'video_source' => $_POST['videoSource'],
                'video_source_type' => $_POST['videoSourceType'],
                'description' => $_POST['description'],
            ];

            // 保存直播信息
            $add = $live->set($data);
            if ($add) {
                $helpers->jsonResponse(200, '创建成功', ['id' => $add]);
            } else {
                $helpers->jsonResponse(500, '创建直播间失败');
            }
            break;
        case 'update':
            $userInfo = $userHelpers->getUserInfoByEnv();
            if ($userInfo['user_id'] === null) {
                $helpers->jsonResponse(403, '未登录');
                exit;
            }

            $liveData = $live->get((int)$_GET['liveId']);
            if ($liveData === null) {
                $helpers->jsonResponse(404, '直播间不存在');
                exit;
            }

            // 权限检查：管理员或直播间所有者
            if ($userInfo['group_id'] !== 1 && $userInfo['user_id'] !== $liveData['user_id']) {
                $helpers->jsonResponse(403, '无权操作此直播间');
                exit;
            }

            // 验证必填字段
            $requiredFields = ['name', 'description', 'videoSource', 'videoSourceType'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    $helpers->jsonResponse(400, "字段 {$field} 不能为空");
                    exit;
                }
            }

            // 验证图片上传
            $picUrl = $liveData['pic']; // 默认使用原来的图片
            if (isset($_FILES['pic']) && $_FILES['pic']['error'] === UPLOAD_ERR_OK) {
                // 处理上传
                $picUrl = $fileUpload->upload($_FILES['pic'], $userInfo['user_id']);
            } elseif (!empty($_POST['pic'])) {
                if (!filter_var($_POST['pic'], FILTER_VALIDATE_URL)) {
                    $helpers->jsonResponse(400, '封面URL格式不正确');
                    exit;
                }
                $picUrl = $_POST['pic'];
            }

            // 验证视频源URL
            if (!filter_var($_POST['videoSource'], FILTER_VALIDATE_URL)) {
                $helpers->jsonResponse(400, '直播源URL格式不正确');
                exit;
            }

            // 验证视频源类型
            if (!in_array($_POST['videoSourceType'], ['flv', 'mp4', 'm3u8'])) {
                $helpers->jsonResponse(400, '直播源类型不支持');
                exit;
            }

            // 准备数据
            $data = [
                'user_id' => $liveData['user_id'],
                'name' => $_POST['name'],
                'pic' => $picUrl,
                'video_source' => $_POST['videoSource'],
                'video_source_type' => $_POST['videoSourceType'],
                'description' => $_POST['description'],
                'css' => $_POST['css'] ?? $liveData['css'],
            ];

            // 更新直播信息
            $update = $live->set($data, (int)$_GET['liveId']);
            if ($update) {
                $helpers->jsonResponse(200, '更新成功');
            } else {
                $helpers->jsonResponse(500, '更新失败');
            }
            break;
        case 'delet':
            $userInfo = $userHelpers->getUserInfoByEnv();
            if ($userInfo['user_id'] === null) {
                $helpers->jsonResponse(403, '未登录');
                exit;
            }

            $liveData = $live->get((int)$_GET['liveId']);
            if (empty($liveData)) {
                $helpers->jsonResponse(404, '直播间不存在');
                exit;
            }

            // 权限检查：管理员或直播间所有者
            if ($userInfo['group_id'] !== 1 && $userInfo['user_id'] !== $liveData['user_id']) {
                $helpers->jsonResponse(403, '无权删除此直播间');
                exit;
            }

            $delet = $live->delete((int)$_GET['liveId']);
            if ($delet) {
                $helpers->jsonResponse(200, '删除成功');
            } else {
                $helpers->jsonResponse(500, '删除失败');
            }
            break;
        default:
            $helpers->jsonResponse(406, 'Invalid method');
            break;
    }
} else {
    // 如果 method 不符合字母数字格式，返回 400 错误
    $helpers->jsonResponse(400, "Invalid API method");
}
