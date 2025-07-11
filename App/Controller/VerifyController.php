<?php

namespace HuaFengLive\Controller;

use HuaFengLive\Helpers\UserHelpers;

/**
 * 验证控制器类
 *
 * 该类提供了验证功能，如邮箱激活验证、第三方客户端验证。
 * 
 * @package HuaFengLive\Controller
 */
class VerifyController extends BaseController
{
    private $userHelpers;

    public function __construct()
    {
        $this->userHelpers = new UserHelpers;
    }

    public function base()
    {
        $getCurrentUrlArray = $this->getCurrentUrlArray();
        $title = match ($getCurrentUrlArray['segments'][3] ?? '/') {
            'index', '/', '' => '主页', // 多个条件匹配同一个结果
            'clientAuth' => '第三方客户端授权',
            'verifyEmail' => '验证邮箱',
            default => '主页', // 默认情况
        };
        $this->loadView(isset($getCurrentUrlArray['segments'][3]) ? '/verify/' . $getCurrentUrlArray['segments'][3] : '/index', [
            'title' => $title,
            'state' => [
                'success' => false,
                'message' => '',
                'show_form' => true,
                'clientid' => htmlspecialchars($getCurrentUrlArray['query']['clientid'] ?? '', ENT_QUOTES, 'UTF-8'),
                'app_info' => null,
                'redirect' => '/', // 默认跳转首页
                'show_retry' => false // 是否显示重新发送验证邮件的按钮
            ],
            'userHelpers' => $this->userHelpers,
            'appConfig' => $this->getAppConfig(),
        ]);
    }
}
