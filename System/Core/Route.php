<?php

namespace ChatRoom\Core;

/**
 * 路由类
 * 
 */

use ChatRoom\Core\Config\App;
use Exception;

class Route
{
    private array $routeRules;
    private App $appConfig;
    private string $currentUri;

    public function __construct()
    {
        $this->appConfig = new App();
        $this->currentUri = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
        $this->routeRules = $this->appConfig->routeRules;
    }

    public function processRoutes(): void
    {
        if (empty($this->currentUri)) {
            throw new Exception("400 Bad Request URI：$this->currentUri");
        }

        $handler = $this->findHandler($this->currentUri);
        if ($handler) {
            $filePath = realpath(FRAMEWORK_APP_PATH . '/Views' . $handler['file'][0]);

            if ($filePath && is_file($filePath)) {
                include $filePath;
            } else {
                throw new Exception("路由规则配置错误，视图文件不存在！");
            }
        } else {
            throw new Exception('404 页面不存在，请刷新重试');
        }
    }

    private function findHandler(string $uri): ?array
    {
        $uriWithoutQuery = strtok($uri, '?');
        foreach ($this->routeRules as $pattern => $handler) {
            if (strpos($pattern, '/') === 0) {
                if (preg_match("#^$pattern$#", $uriWithoutQuery)) {
                    return $handler;
                }
            } else {
                if ($uriWithoutQuery === $pattern) {
                    return $handler;
                }
            }
        }
        return null;
    }
}
