<?php

namespace HuaFengLive\Controller;

use HuaFengLive\Helpers\UserHelpers;
use HuaFengLive\Modules\TokenModules;

class DevController extends BaseController
{
    public function base()
    {
        $userHelpers = new UserHelpers;
        $tokenManager = new TokenModules;
        if (!$userHelpers->checkUserLoginStatus()) {
            echo "<script>alert('未登录！'); window.location.href = '/';</script>";
            exit;
        }
        // 验证CSRF令牌
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !$userHelpers->verifyCsrfToken($_POST['csrf_token'])) {
                http_response_code(403);
                die('CSRF token验证失败');
            }
        }
        // 生成CSRF令牌
        $csrfToken = $userHelpers->generateCsrfToken();
        $title = match ($this->getCurrentUrlArray()['segments'][1] ?? '/') {
            'index', '/', '' => '主页', // 多个条件匹配同一个结果
            'sandbox' => '沙箱',
            'docs' => 'API 文档',
            'application' => '第三方应用信息收集',
            default => '主页', // 默认情况
        };
        $this->loadView(isset($this->getCurrentUrlArray()['segments'][1]) ? '/dev/' . $this->getCurrentUrlArray()['segments'][1] : '/dev/index', [
            'title' => $title,
            'csrfToken' => $csrfToken,
            'userHelpers' => $userHelpers,
            'tokenManager' => $tokenManager
        ]);
    }
}
