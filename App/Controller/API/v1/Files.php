<?php

namespace HuaFengLive\Controller\API\v1;

use HuaFengLive\Config\ChatConfig;
use HuaFengLive\Helpers\UserHelpers;
use HuaFengLive\Modules\FileModules;

class Files
{
    public function handle(array $requestData)
    {
        $chatConfig = new ChatConfig();
        $fileUploader = new FileModules(
            $chatConfig->uploadFile["allowTypes"],
            $chatConfig->uploadFile["maxSize"]
        );
        $userHelpers = new UserHelpers();

        if (!isset($requestData['_files']['file'])) {
            return ['status' => 400, 'message' => '未检测到上传文件'];
        }

        $userInfo = $userHelpers->getUserInfoByEnv();
        $file = $fileUploader->upload($requestData['_files']['file'], $userInfo["user_id"]);

        if ($file === false) {
            return ['status' => 406, 'message' => '文件上传失败或此文件类型不允许'];
        }

        return ['status' => 200, 'data' => $file];
    }
}
