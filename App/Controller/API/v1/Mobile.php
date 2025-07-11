<?php

namespace HuaFengLive\Controller\API\v1;

class Mobile
{
    public function handle(array $requestData)
    {
        $method = $requestData['_method'] ?? '';

        switch ($method) {
            case 'update':
                return [
                    'status' => 200,
                    'data' => [
                        'title' => '测试更新标题',
                        'note' => '测试更新内容',
                        'url' => 'dfggmc.top'
                    ]
                ];
            default:
                return ['status' => 406, 'message' => '方法不存在'];
        }
    }
}
