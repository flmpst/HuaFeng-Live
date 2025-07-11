<?php

namespace XQPF\Core\Modules;

use Exception;
use XQPF\Core\Modules\Database\Base;

class AppAuth
{
    /**
     * 验证应用凭证
     *
     * @param string $appId
     * @param string $appSecret
     * @return array 应用信息
     * @throws Exception
     */
    public static function verifyAppCredentials(string $appId, string $appSecret): array
    {
        $app = Base::select('third_party_apps', ['*'], ['app_id' => $appId]);

        if (empty($app)) {
            throw new Exception('应用不存在');
        }

        $app = $app[0];

        if (!hash_equals($app['app_secret'], $appSecret)) {
            throw new Exception('应用密钥无效');
        }

        return $app;
    }

    /**
     * 获取用户的所有应用
     *
     * @param int $userId
     * @return array
     * @throws Exception
     */
    public static function getUserApps(int $userId): array
    {
        return Base::select('third_party_apps', ['*'], ['user_id' => $userId]);
    }

    /**
     * 通过App ID获取应用信息
     *
     * @param string $appId
     * @return array
     * @throws Exception
     */
    public static function getAppById(string $appId): array
    {
        $apps = Base::select('third_party_apps', ['*'], ['app_id' => $appId]);

        if (empty($apps)) {
            throw new Exception('应用不存在');
        }

        return $apps[0];
    }

    /**
     * 重置应用密钥
     *
     * @param string $appId
     * @param int $userId 用于验证应用所有者
     * @return string 新的App Secret
     * @throws Exception
     */
    public static function resetAppSecret(string $appId, int $userId): string
    {
        $app = self::getAppById($appId);

        if ($app['user_id'] != $userId) {
            throw new Exception('无权操作此应用');
        }

        $newSecret = bin2hex(random_bytes(16));
        Base::update(
            'third_party_apps',
            ['app_secret' => $newSecret],
            ['app_id' => $appId]
        );

        return $newSecret;
    }

    /**
     * 验证回调地址
     *
     * @param string $appId
     * @param string $redirectUri
     * @return bool
     * @throws Exception
     */
    public static function verifyRedirectUri(string $appId, string $redirectUri): bool
    {
        $app = self::getAppById($appId);

        if (empty($app['redirect_uri'])) {
            return true; // 如果应用没有设置回调地址，则允许任何地址
        }

        return $app['redirect_uri'] === $redirectUri;
    }
}