<?php

use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Config\App;
use ChatRoom\Core\Helpers\User;

try {
    $helpers = new Helpers();
    $userHelpers = new User();
    $systemSetting = new App();
    $enableCrossDomain = $systemSetting->api['enableCrossDomain'];
    $allowedDomains = explode(',', $systemSetting->api['allowCrossDomainlist']); // 获取允许的域名
    $URI = parse_url($_SERVER['REQUEST_URI'])['path'];
    $apiName = basename(explode('/', $URI)[3]);  // 提取 API 名称部分并避免路径穿越

    if ($enableCrossDomain) {
        // 检查请求的Origin是否在允许的域名列表中
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array('*', $allowedDomains) || in_array($origin, $allowedDomains)) {
            // 设置CORS头，允许跨域请求
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Authorization");
        }
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $apiName)) {
        $helpers->jsonResponse(403, '无效的API名称!');
    }

    /**
     * HTML 转义
     *
     * @param string $str
     * @return string
     */
    function h(string $str): string
    {
        return is_string($str) ? htmlspecialchars($str, ENT_QUOTES, 'UTF-8') : $str;
    }

    $apiFile = __DIR__ . "/$apiName.php";

    // 确保文件存在
    if (!file_exists($apiFile)) {
        $helpers->jsonResponse(404, 'API 不存在!');
    }

    include $apiFile;
} catch (Throwable $e) {
    if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
        $getTrace = ['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()];
    } else {
        $getTrace = [];
    }

    handleException($e);
}
