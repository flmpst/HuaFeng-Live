<?php

namespace HuaFengLive\Controller\API\v1;

use HuaFengLive\Controller\APIController;
use HuaFengLive\Controller\SearchController;

class Search extends APIController
{
    public function handle(array $requestData)
    {
        $search = new SearchController();
        $method = $requestData['_method'] ?? '';

        switch ($method) {
            case 'user':
                $page = isset($requestData['page']) ? (int)$requestData['page'] : 1;
                $perPage = isset($requestData['per_page']) ? (int)$requestData['per_page'] : 20;

                $params = [
                    'username' => $this->h($requestData['username'] ?? ''),
                    'status' => isset($requestData['status']) ? (int)$requestData['status'] : null,
                    'group_id' => isset($requestData['group_id']) ? (int)$requestData['group_id'] : null
                ];

                $result = $search->searchUsers($params, $page, $perPage);
                return ['status' => 200, 'data' => $result];

            case 'live':
                $page = isset($requestData['page']) ? (int)$requestData['page'] : 1;
                $perPage = isset($requestData['per_page']) ? (int)$requestData['per_page'] : 20;

                $params = [
                    'name' => $this->h($requestData['name'] ?? ''),
                    'description' => $this->h($requestData['description'] ?? ''),
                    'user_id' => isset($requestData['user_id']) ? (int)$requestData['user_id'] : null,
                    'status' => $this->h($requestData['status'] ?? ''),
                    'video_source_type' => $this->h($requestData['video_source_type'] ?? '')
                ];

                $result = $search->searchLiveList($params, $page, $perPage);
                return ['status' => 200, 'data' => $result];

            case 'global':
                $keyword = $this->h($requestData['keyword'] ?? '');
                if (empty($keyword)) {
                    return ['status' => 400, 'message' => '搜索关键词不能为空'];
                }

                $page = isset($requestData['page']) ? (int)$requestData['page'] : 1;
                $perPage = isset($requestData['per_page']) ? (int)$requestData['per_page'] : 20;

                $result = $search->globalSearch($keyword, $page, $perPage);
                return ['status' => 200, 'data' => $result];

            default:
                return ['status' => 406, 'message' => '方法不存在'];
        }
    }
}
