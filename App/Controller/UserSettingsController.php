<?php

namespace HuaFengLive\Controller;

use XQPF\Core\Modules\Database\Base;
use PDO;
use PDOException;
use Exception;

class UserSettingsController
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Base::getConnection();
    }

    /**
     * 获取用户设置
     * 
     * @param string $user_id 用户ID
     * @param string $client_id 客户端ID
     * @param string|null $setting_name 设置名(可选)
     * @return array
     */
    public function getSettings(string $user_id, string $client_id, ?string $setting_name = null): array
    {
        try {
            $query = "SELECT setting_name, setting_value FROM user_settings 
                      WHERE user_id = :user_id AND client_id = :client_id";
            $params = [
                ':user_id' => $user_id,
                ':client_id' => $client_id
            ];

            if ($setting_name !== null) {
                $query .= " AND setting_name = :setting_name";
                $params[':setting_name'] = $setting_name;
            }

            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);

            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[$row['setting_name']] = unserialize($row['setting_value']);
            }

            return $setting_name !== null ? ($result[$setting_name] ?? []) : $result;
        } catch (PDOException $e) {
            throw new Exception("获取用户设置失败: " . $e->getMessage());
        }
    }

    /**
     * 设置用户配置项
     * 
     * @param string $user_id 用户ID
     * @param string $client_id 客户端ID
     * @param string $setting_name 设置名
     * @param mixed $setting_value 设置值
     * @param string $update_ip 更新IP
     * @return bool
     */
    public function setSetting(string $user_id, string $client_id, string $setting_name, $setting_value, string $update_ip): bool
    {
        try {
            $serialized_value = serialize($setting_value);
            $uuid = $this->generateUuid();

            $query = "INSERT INTO user_settings (uuid, user_id, client_id, setting_name, setting_value, update_ip)
                      VALUES (:uuid, :user_id, :client_id, :setting_name, :setting_value, :update_ip)
                      ON DUPLICATE KEY UPDATE 
                          setting_value = VALUES(setting_value),
                          update_ip = VALUES(update_ip),
                          update_time = CURRENT_TIMESTAMP";

            $stmt = $this->connection->prepare($query);
            return $stmt->execute([
                ':uuid' => $uuid,
                ':user_id' => $user_id,
                ':client_id' => $client_id,
                ':setting_name' => $setting_name,
                ':setting_value' => $serialized_value,
                ':update_ip' => $update_ip
            ]);
        } catch (PDOException $e) {
            throw new Exception("设置用户配置失败: " . $e->getMessage());
        }
    }

    /**
     * 删除用户配置项
     * 
     * @param string $user_id 用户ID
     * @param string $client_id 客户端ID
     * @param string $setting_name 设置名
     * @return bool
     */
    public function deleteSetting(string $user_id, string $client_id, string $setting_name): bool
    {
        try {
            $query = "DELETE FROM user_settings 
                      WHERE user_id = :user_id AND client_id = :client_id AND setting_name = :setting_name";
            
            $stmt = $this->connection->prepare($query);
            return $stmt->execute([
                ':user_id' => $user_id,
                ':client_id' => $client_id,
                ':setting_name' => $setting_name
            ]);
        } catch (PDOException $e) {
            throw new Exception("删除用户配置失败: " . $e->getMessage());
        }
    }

    /**
     * 同步用户设置到其他客户端
     * 
     * @param string $user_id 用户ID
     * @param string $source_client_id 源客户端ID
     * @param string $target_client_id 目标客户端ID
     * @return bool
     */
    public function syncSettings(string $user_id, string $source_client_id, string $target_client_id): bool
    {
        try {
            // 开始事务
            $this->connection->beginTransaction();

            // 获取源客户端的所有设置
            $source_settings = $this->getSettings($user_id, $source_client_id);

            // 删除目标客户端现有设置
            $delete_query = "DELETE FROM user_settings 
                             WHERE user_id = :user_id AND client_id = :client_id";
            $delete_stmt = $this->connection->prepare($delete_query);
            $delete_stmt->execute([
                ':user_id' => $user_id,
                ':client_id' => $target_client_id
            ]);

            // 插入同步的设置
            foreach ($source_settings as $name => $value) {
                $this->setSetting($user_id, $target_client_id, $name, $value, 'SYNC');
            }

            // 提交事务
            return $this->connection->commit();
        } catch (PDOException $e) {
            $this->connection->rollBack();
            throw new Exception("同步用户设置失败: " . $e->getMessage());
        }
    }

    /**
     * 生成UUID
     * 
     * @return string
     */
    private function generateUuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}