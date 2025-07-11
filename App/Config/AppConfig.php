<?php

namespace HuaFengLive\Config;

/**
 * 应用配置
 */
class AppConfig
{
    /**
     * 路由规则
     * @var array
     */
    protected array $routeRules = [
        '/' => [
            'action' => ['HomeController::index'],
        ],
        '/[0-9]*' => [
            'action' => ['HomeController::live']
        ],
        '/module/search' => [
            'action' => ['HomeController::module']
        ],
        '/dev[\s\S]*' => [
            'action' => ['DevController::base']
        ],
        '/verify/[\s\S]*' => [
            'action' => ['VerifyController::base']
        ],
        '/api/v1/verify/[\s\S]*' => [
            'action' => ['VerifyController::base']
        ],
        '/api/v1/[\s\S]*' => [
            'action' => ['APIController::base']
        ],
    ];

    /**
     * API配置
     *
     * @var array
     */
    public array $api = [
        'enableCrossDomain' => true, // 是否允许跨域
        'allowCrossDomainlist' => '*' // 允许跨域的域名列表
    ];

    /**
     * 极验配置
     *
     * @var array
     */
    public array $geetest = [
        'captchaId' => '923777251d36339c575db1bef11bf24b',
        'captchaKey' => '0165d8e6826dd61f46c5c79b161558a3'
    ];

    public array $email = [
        'smtp' => [
            'host' => '',
            'username' => '',
            'password' => '',
            'port' => '',
            //加密方法 SSL、TLS
            'secure' => 'ssl'
        ]
    ];

    /**
     * 缓存配置
     * 
     * @var array
     */
    public array $cache = [
        'enabled' => true,
        'ttl' => 300,
        'path' => FRAMEWORK_DIR . '/Writable/cache',
        'prefix' => 'cache_'
    ];
}
