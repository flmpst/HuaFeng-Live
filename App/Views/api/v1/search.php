<?php

use ChatRoom\Core\Controller\Search;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = isset(explode('/', trim($uri, '/'))[3]) ? explode('/', trim($uri, '/'))[3] : null;

// 创建 Search 实例
$search = new Search();

// 验证 API 名称是否符合字母和数字的格式，且长度不超过 30
if (preg_match('/^[a-zA-Z0-9]{1,30}$/', $method)) {
    switch ($method) {
        case 'user':
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

            $params = [
                'username' => $_GET['username'] ?? '',
                'status' => isset($_GET['status']) ? (int)$_GET['status'] : null,
                'group_id' => isset($_GET['group_id']) ? (int)$_GET['group_id'] : null
            ];

            $result = $search->searchUsers($params, $page, $perPage); // 改为实例方法调用
            $helpers->jsonResponse(200, true, $result);
            break;
        case 'live':
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

            $params = [
                'name' => $_GET['name'] ?? '',
                'description' => $_GET['description'] ?? '',
                'user_id' => isset($_GET['user_id']) ? (int)$_GET['user_id'] : null,
                'status' => $_GET['status'] ?? '',
                'video_source_type' => $_GET['video_source_type'] ?? ''
            ];

            $result = $search->searchLiveList($params, $page, $perPage); // 改为实例方法调用
            $helpers->jsonResponse(200, true, $result);
            break;
        case 'global':
            $keyword = $_GET['keyword'] ?? '';
            if (empty($keyword)) {
                $helpers->jsonResponse(400, '搜索关键词不能为空');
                break;
            }

            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

            $result = $search->globalSearch($keyword, $page, $perPage); // 改为实例方法调用
            $helpers->jsonResponse(200, true, $result);
            break;
        default:
            $helpers->jsonResponse(406, '方法不存在');
    }
} else {
    $helpers->jsonResponse(400, "Invalid API method");
}
