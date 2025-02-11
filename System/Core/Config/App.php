<?php

namespace ChatRoom\Core\Config;

/**
 * 应用配置
 */
class App
{
    /**
     * 路由规则
     * @var array
     */
    public array $routeRules = [
        // 基本路由
        '/' => [
            'file' => ['/index.php'],
        ],
        '/index' => [
            'file' => ['/index.php'],
        ],
        '/user/logout' => [
            'file' => ['/user/logout.php'],
        ],
        '/[0-9]*' => [
            'file' => ['/live.php']
        ],
        '/api/v1/[\s\S]*' => [
            'file' => ['/api/v1/API_BASE.php']
        ],
        '/wap/index' => [
            'file' => ['/wap/index.html']
        ],
        '/wap/live' => [
            'file' => ['/wap/live.html']
        ]
    ];

    /**
     * API配置
     *
     * @var array
     */
    public array $api = [
        'enableCrossDomain' => false, // 是否允许跨域
        'allowCrossDomainlist' => '' // 允许跨域的域名列表
    ];

    /**
     * 极验配置
     *
     * @var array
     */
    public array $geetest = [
        'captchaId' => '',
        'captchaKey' => ''
    ];

    public array $email = [
        'smtp' => [
            'host' => '',
            'username' => '',
            'password' => '',
            'port' => '',
            //加密方法 SSL、TLS
            'secure' => ''
        ]
    ];
}
