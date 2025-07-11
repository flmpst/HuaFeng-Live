<?php

namespace HuaFengLive\Controller;

use XQPF\Core\Helpers\Helpers;
use HuaFengLive\Config\AppConfig;
use HuaFengLive\Helpers\UserHelpers;
use Throwable;

class APIController extends BaseController
{
    protected $helpers;
    protected $userHelpers;
    protected $systemSetting;
    protected $enableCrossDomain;
    protected $allowedDomains;
    protected $apiName;

    /**
     * 初始化API控制器
     */
    public function __construct()
    {
        $this->helpers = new Helpers();
        $this->userHelpers = new UserHelpers();
        $this->systemSetting = new AppConfig();
        $this->enableCrossDomain = $this->systemSetting->api['enableCrossDomain'];
        $this->allowedDomains = explode(',', $this->systemSetting->api['allowCrossDomainlist']);

        $URI = parse_url($_SERVER['REQUEST_URI'])['path'];
        $this->apiName = basename(explode('/', $URI)[3]);
    }

    /**
     * API基类方法
     *
     * @return void
     */
    public function base()
    {
        try {
            $this->handleCrossDomain();
            $this->validateApiName();
            if (in_array($this->apiName, ['verify'])) {
                if ($this->getCurrentUrlArray()['segments'][3] === 'email') {
                    exit($this->loadView('/verifyEmail'));
                }
                if ($this->getCurrentUrlArray()['segments'][3] === 'client') {
                    exit($this->loadView('/clientAuth'));
                }
            }
            // 构建API类名
            $apiClassName = 'HuaFengLive\\Controller\\API\\' . $this->getCurrentUrlArray()['segments'][1] . '\\' . ucfirst($this->apiName);

            if (!class_exists($apiClassName)) {
                $this->helpers->jsonResponse(404, 'API 不存在!');
            }

            // 实例化并调用API类
            $apiInstance = new $apiClassName();
            if (!method_exists($apiInstance, 'handle')) {
                $this->helpers->jsonResponse(501, 'API未实现handle方法');
            }
            $return = $apiInstance->handle($this->parseRequestData());
            $this->helpers->jsonResponse($return['status'], $return['message'] ?? true, $return['data'] ?? []);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * 处理跨域请求
     */
    protected function handleCrossDomain()
    {
        if ($this->enableCrossDomain) {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if (in_array('*', $this->allowedDomains) || in_array($origin, $this->allowedDomains)) {
                header("Access-Control-Allow-Origin: $origin");
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
                header("Access-Control-Allow-Headers: Content-Type, Authorization");
            }
        }
    }

    /**
     * 验证API名称有效性
     */
    protected function validateApiName()
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->apiName)) {
            $this->helpers->jsonResponse(403, '无效的API名称!');
        }
    }

    /**
     * HTML 转义
     */
    public function h(string $str): string
    {
        return is_string($str) ? htmlspecialchars($str, ENT_QUOTES, 'UTF-8') : $str;
    }

    /**
     * 解析请求数据
     */
    public function parseRequestData()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $data = [];

        // 如果是 JSON 请求
        if (strpos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->helpers->jsonResponse(400, 'Invalid JSON data');
            }
        }
        // 如果是表单 POST 请求
        else {
            $data = $_POST;
        }

        // 合并 GET 参数
        $data = array_merge($_GET, $data);

        $data['_method'] = $this->getCurrentUrlArray()['segments'][3];

        // 处理文件上传
        if (!empty($_FILES)) {
            $data['_files'] = $_FILES;
        }

        return $data;
    }

    /**
     * 处理异常
     */
    protected function handleException(Throwable $e)
    {
        if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
            $getTrace = ['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()];
        } else {
            $getTrace = [];
        }

        $this->helpers->jsonResponse(500, '服务器错误', $getTrace);
    }
}
