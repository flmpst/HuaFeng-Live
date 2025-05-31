<?php

use ChatRoom\Core\Config\Chat;
use ChatRoom\Core\Modules\FileUploader;

$chatConfig = new Chat();
$file = new FileUploader(
    $chatConfig->uploadFile["allowTypes"],
    $chatConfig->uploadFile["maxSize"]
);
$userHelpers = new ChatRoom\Core\Helpers\User;

$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method = isset(explode("/", trim($uri, "/"))[3]) ? explode("/", trim($uri, "/"))[3] : "";

// 验证 API 名称是否符合字母和数字的格式，且长度不超过 256
if (preg_match('/^[a-zA-Z0-9]{1,256}$/', $method)) {
    $userInfo = $userHelpers->getUserInfoByEnv();
    // 处理上传文件
    if (!isset($_FILES["file"])) {
        $helpers->jsonResponse(400, "未检测到上传文件");
    }
    $file = $file->upload($_FILES["file"], $userInfo["user_id"]);
    if ($file === false) {
        $helpers->jsonResponse(406, "文件上传失败或此文件类型不允许");
    }
    $helpers->jsonResponse(200, true, $file);
} else {
    // 如果 method 不符合字母数字格式，返回 400 错误
    $helpers->jsonResponse(400, "Invalid API method");
}
