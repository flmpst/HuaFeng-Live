<?php

namespace HuaFengLive\Controller;

use XQPF\Core\Modules\Database\Base;
use HuaFengLive\Config\AppConfig;
use HuaFengLive\Helpers\UserHelpers;
use XQPF\Core\Modules\Cache;

class SearchController
{
    private static ?Cache $cache = null;
    private $UserHelpers;

    public function __construct()
    {
        $this->UserHelpers = new UserHelpers;
    }

    private static function getCache(): Cache
    {
        if (self::$cache === null) {
            $appConfig = new AppConfig();
            $appConfig->cache['prefix'] = 'search_';
            self::$cache = new Cache($appConfig->cache);
        }
        return self::$cache;
    }

    private static function getCacheKey(string $method, array $params, int $page, int $perPage): string
    {
        return $method . '_' . md5(serialize($params) . $page . $perPage);
    }

    /**
     * 搜索用户
     * @param array $params 搜索参数
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @return array
     */
    public function searchUsers(array $params, int $page = 1, int $perPage = 20): array
    {
        $cache = self::getCache();
        $cacheKey = self::getCacheKey('search.users', $params, $page, $perPage);

        if ($cache->isEnabled() && $cached = $cache->get($cacheKey)) {
            return $cached;
        }

        $db = Base::getInstance()->getConnection();

        // 基础SQL
        $sql = "SELECT user_id, username, email, group_id, created_at, status FROM users WHERE 1=1";
        $countSql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
        $where[] = "status != 'delete'";
        $bindParams = [];

        // 构建WHERE条件
        if (!empty($params['username'])) {
            $where[] = "username LIKE ?";
            $bindParams[] = '%' . $params['username'] . '%';
        }

        if (isset($params['status']) && is_numeric($params['status'])) {
            $where[] = "status = ?";
            $bindParams[] = $params['status'];
        }

        if (!empty($params['group_id'])) {
            $where[] = "group_id = ?";
            $bindParams[] = $params['group_id'];
        }

        // 合并WHERE条件
        if (!empty($where)) {
            $whereClause = " AND " . implode(" AND ", $where);
            $sql .= $whereClause;
            $countSql .= $whereClause;
        }

        // 分页处理
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT {$offset}, {$perPage}";

        // 执行查询
        $stmt = $db->prepare($countSql);
        $stmt->execute($bindParams);
        $total = $stmt->fetchColumn();

        $stmt = $db->prepare($sql);
        $stmt->execute($bindParams);
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 数据替换
        foreach ($users as &$user) {
            $user['avatar'] = $this->UserHelpers->getAvatar($user['email'], 40);
            unset($user['email']);
        }

        $result = [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'data' => $users
        ];

        if ($cache->isEnabled()) {
            $cache->set($cacheKey, $result);
        }

        return $result;
    }

    /**
     * 搜索直播列表
     * @param array $params 搜索参数
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @return array
     */
    public function searchLiveList(array $params, int $page = 1, int $perPage = 20): array
    {
        $cache = self::getCache();
        $cacheKey = self::getCacheKey('search.lives', $params, $page, $perPage);

        if ($cache->isEnabled() && $cached = $cache->get($cacheKey)) {
            return $cached;
        }

        $db = Base::getInstance()->getConnection();

        // 基础SQL
        $sql = "SELECT id, user_id, name, pic, description, video_source_type, status FROM live_list WHERE 1=1";
        $countSql = "SELECT COUNT(*) as total FROM live_list WHERE 1=1";
        $where[] = "status != 'delete'";
        $bindParams = [];

        // 构建WHERE条件
        if (!empty($params['name'])) {
            $where[] = "name LIKE ?";
            $bindParams[] = '%' . $params['name'] . '%';
        }

        if (!empty($params['description'])) {
            $where[] = "description LIKE ?";
            $bindParams[] = '%' . $params['description'] . '%';
        }

        if (!empty($params['user_id'])) {
            $where[] = "user_id = ?";
            $bindParams[] = $params['user_id'];
        }

        if (!empty($params['status'])) {
            $where[] = "status = ?";
            $bindParams[] = $params['status'];
        }

        if (!empty($params['video_source_type'])) {
            $where[] = "video_source_type = ?";
            $bindParams[] = $params['video_source_type'];
        }

        // 合并WHERE条件
        if (!empty($where)) {
            $whereClause = " AND " . implode(" AND ", $where);
            $sql .= $whereClause;
            $countSql .= $whereClause;
        }

        // 分页处理
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT {$offset}, {$perPage}";

        // 执行查询
        $stmt = $db->prepare($countSql);
        $stmt->execute($bindParams);
        $total = $stmt->fetchColumn();

        $stmt = $db->prepare($sql);
        $stmt->execute($bindParams);
        $liveList = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $result = [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'data' => $liveList
        ];

        if ($cache->isEnabled()) {
            $cache->set($cacheKey, $result);
        }

        return $result;
    }

    /**
     * 综合搜索
     * @param string $keyword 搜索关键词
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @return array
     */
    public function globalSearch(string $keyword, int $page = 1, int $perPage = 20): array
    {
        $cache = self::getCache();
        $cacheKey = self::getCacheKey('search.global', ['keyword' => $keyword], $page, $perPage);

        if ($cache->isEnabled() && $cached = $cache->get($cacheKey)) {
            return $cached;
        }

        $db = Base::getInstance()->getConnection();

        // 搜索用户
        $userSql = "SELECT user_id, email, username, group_id, created_at
                   FROM users
                   WHERE status != 'delete' AND (username LIKE ? OR email LIKE ?)
                   LIMIT " . (($page - 1) * $perPage) . ", {$perPage}";

        $userCountSql = "SELECT COUNT(*) as total
                        FROM users
                        WHERE status != 'delete' AND (username LIKE ? OR email LIKE ?)";

        $stmt = $db->prepare($userCountSql);
        $stmt->execute(['%' . $keyword . '%', '%' . $keyword . '%']);
        $userTotal = $stmt->fetchColumn();

        $stmt = $db->prepare($userSql);
        $stmt->execute(['%' . $keyword . '%', '%' . $keyword . '%']);
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 数据替换
        foreach ($users as &$user) {
            $user['avatar'] = $this->UserHelpers->getAvatar($user['email'], 40);
            unset($user['email']);
        }

        // 搜索直播
        $liveSql = "SELECT id, user_id, name, pic, description, video_source_type
                   FROM live_list
                   WHERE status != 'delete' AND (name LIKE ? OR description LIKE ?)
                   LIMIT " . (($page - 1) * $perPage) . ", {$perPage}";

        $liveCountSql = "SELECT COUNT(*) as total 
                        FROM live_list 
                        WHERE status != 'delete' AND (name LIKE ? OR description LIKE ?)";

        $stmt = $db->prepare($liveCountSql);
        $stmt->execute(['%' . $keyword . '%', '%' . $keyword . '%']);
        $liveTotal = $stmt->fetchColumn();

        $stmt = $db->prepare($liveSql);
        $stmt->execute(['%' . $keyword . '%', '%' . $keyword . '%']);
        $liveList = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $result = [
            'users' => [
                'total' => $userTotal,
                'data' => $users
            ],
            'live_list' => [
                'total' => $liveTotal,
                'data' => $liveList
            ],
            'page' => $page,
            'per_page' => $perPage
        ];

        if ($cache->isEnabled()) {
            $cache->set($cacheKey, $result);
        }

        return $result;
    }

    /**
     * 清除搜索缓存
     * @param string $method 方法名 (searchUsers, searchLiveList, globalSearch)
     * @param array $params 原始搜索参数
     * @param int $page 页码
     * @param int $perPage 每页数量
     */
    public static function clearSearchCache(string $method, array $params, int $page, int $perPage): bool
    {
        $cache = self::getCache();
        $cacheKey = self::getCacheKey($method, $params, $page, $perPage);
        return $cache->delete($cacheKey);
    }
}
