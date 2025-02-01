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
            'cache' => [null]
        ],
        '/index' => [
            'file' => ['/index.php'],
            'cache' => [null]
        ],
        '/user/logout' => [
            'file' => ['/user/logout.php'],
            'cache' => [null]
        ],
        '/[0-9]*' => [
            'file' => ['/live.php']
        ],
        '/api/v1/[\s\S]*' => [
            'file' => ['/api/v1/API_BASE.php']
        ]
    ];

    /**
     * API配置
     *
     * @var array
     */
    public array $api = [
        'enableCrossDomain' => true,
        'allowCrossDomainlist' => ''
    ];

    /**
     * 用于cloudflare
     *
     * @var array
     */
    public array $cloudflare = [
        'turnstile' => [
            'siteKey' => '1x00000000000000000000AA',
            'secretKey' => '1x0000000000000000000000000000000AA'
        ]
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
