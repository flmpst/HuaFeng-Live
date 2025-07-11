<?php

namespace XQPF\Core;

use Exception;
use HuaFengLive\Config\AppConfig;

/**
 * 路由处理类
 *
 * 该类负责处理应用程序的路由规则，根据当前请求的URI找到对应的处理器。
 * 
 * @package XQPF\Core
 */
class Route extends AppConfig
{
    protected array $routeRules;
    private AppConfig $appConfig;
    private string $currentUri;

    public function __construct()
    {
        $this->appConfig = new AppConfig();
        $this->currentUri = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
        $this->routeRules = $this->appConfig->routeRules;
    }

    /**
     * 处理当前请求的路由
     *
     * 根据当前请求的URI，查找匹配的路由规则，并调用对应的控制器方法。
     *
     * @throws Exception 如果没有找到匹配的路由规则或控制器类/方法不存在
     */
    public function processRoutes(): void
    {
        if (empty($this->currentUri)) {
            throw new Exception("400 Bad Request URI：$this->currentUri");
        }

        $handler = $this->findHandler($this->currentUri);
        if ($handler) {
            $action = $handler['action'][0];
            list($controllerName, $methodName) = explode('::', $action);

            // 构建控制器类名
            $controllerClass = 'HuaFengLive\Controller\\' . $controllerName;

            // 检查控制器类是否存在
            if (!class_exists($controllerClass)) {
                throw new Exception("404 Not Found: 控制器类 $controllerClass 不存在！");
            }

            // 创建控制器实例
            $controller = new $controllerClass();

            // 检查方法是否存在
            if (!method_exists($controller, $methodName)) {
                throw new Exception("404 Not Found: 控制器 $controllerClass 中不存在方法 $methodName ！");
            }

            // 调用控制器方法
            $controller->$methodName();
        } else {
            http_response_code(404);
            exit("404 Not Found: 没有找到匹配的路由规则！URI: $this->currentUri");
        }
    }

    /**
     * 查找匹配的路由处理器
     *
     * 根据当前请求的URI查找匹配的路由规则，并返回对应的处理器。
     *
     * @param string $uri 当前请求的URI
     * @return array|null 返回匹配的处理器或null
     */
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
