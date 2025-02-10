<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = isset(explode('/', trim($uri, '/'))[3]) ? explode('/', trim($uri, '/'))[3] : null;

// 验证 API 名称是否符合字母和数字的格式，且长度不超过 30
if (preg_match('/^[a-zA-Z0-9]{1,30}$/', $method)) {
    switch ($method) {
        case 'update':
            $helpers->jsonResponse(200, true, ['title' => '测试更新标题', 'note' => '测试更新内容', 'url' => 'dfggmc.top']);
            break;
        default:
    }
}
