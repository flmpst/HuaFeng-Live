<?php

namespace HuaFengLive\Controller;

use Exception;
use HuaFengLive\Config\AppConfig;

/**
 * 基础控制器类
 *
 * 该类提供了基本的控制器功能，包括获取应用配置和处理异常。
 * 
 * @package HuaFengLive\Controller
 */
class BaseController extends AppConfig
{
    /**
     * 获取应用配置
     *
     * @return App 应用配置实例
     */
    public function getAppConfig(): AppConfig
    {
        return new AppConfig;
    }

    /**
     * 加载视图文件
     *
     * @param string $viewName 视图名称
     * @param array $data 传递给视图的数据
     * @throws Exception 如果视图文件不存在则抛出异常
     */
    public function loadView(string $viewName, array $data = []): void
    {
        $viewFile = FRAMEWORK_APP_PATH . '/Views' . $viewName . '.php';
        if (!file_exists($viewFile)) {
            throw new Exception("404 Not Found: 视图文件 $viewFile 不存在！");
        }
        extract($data);
        include $viewFile;
    }

    /**
     * 获取当前完整URL的数组形式
     *
     * @return array 包含URL各部分的数组
     */
    public function getCurrentUrlArray(): array
    {
        // 确定协议
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';

        // 获取主机名
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];

        // 获取请求URI
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        // 解析查询字符串
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        parse_str($queryString, $queryParams);

        // 获取路径部分
        $path = parse_url($requestUri, PHP_URL_PATH);

        // 分割路径为数组，并过滤空值，同时重置索引从0开始
        $segments = array_values(array_filter(explode('/', $path), function ($segment) {
            return $segment !== '';
        }));

        // 构建URL数组
        $urlArray = [
            'full_url' => $protocol . $host . $requestUri,
            'protocol' => $protocol,
            'host' => $host,
            'path' => $path,
            'query' => $queryParams,
            'segments' => $segments, // 现在索引从0开始
        ];

        return $urlArray;
    }
}
