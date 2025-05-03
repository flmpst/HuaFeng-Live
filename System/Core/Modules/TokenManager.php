<?php

namespace ChatRoom\Core\Modules;

use ChatRoom\Core\Database\Base;
use PDOException;
use Exception;
use PDO;

class TokenManager
{
    private $db;

    public function __construct()
    {
        $this->db = Base::getInstance()->getConnection();
    }

    /**
     * 生成一个新的 token，并将其插入到数据库中
     * @param int $userId 用户ID
     * @param string|+1 hour $expirationInterval 过期时间的间隔，默认是 '+1 hour'参阅：https://www.php.net/manual/zh/function.strtotime.php
     * @param string|null $token 手动指定的 token 注意此方法不安全！
     * @param string $type token 类型
     * @param array|null $extraData 额外数据数组，将被序列化存储
     * @return string 生成的 token
     * @throws Exception
     */
    public function generateToken(int $userId, string $type, string $expirationInterval = '+1 hour', $token = null, ?array $extraData = null): string
    {
        try {
            $this->db->beginTransaction();

            if ($token === null) {
                // 随机选择一个加密算法
                $hashAlgorithms = [
                    'sha256',
                    'sha512',
                    'md5',
                    'sha1'
                ];
                $selectedAlgorithm = $hashAlgorithms[array_rand($hashAlgorithms)];

                // 生成安全的 token
                $token = bin2hex(hash($selectedAlgorithm, random_bytes(32) . $userId . time(), true));
            }

            $expiration = date('Y-m-d H:i:s', strtotime($expirationInterval));
            $createdAt = date('Y-m-d H:i:s');
            $serializedExtra = $extraData !== null ? serialize($extraData) : null;

            // 检查记录是否存在
            $sqlCheck = "SELECT COUNT(*) FROM user_tokens WHERE user_id = :user_id AND type = :type";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmtCheck->bindParam(':type', $type, PDO::PARAM_STR);
            $stmtCheck->execute();
            $exists = $stmtCheck->fetchColumn() > 0;

            if ($exists) {
                if ($type === 'clientAuth') {
                    // clientAuth类型总是插入新记录
                    $sql = "INSERT INTO user_tokens
                        (user_id, token, expiration, created_at, updated_at, type, extra)
                        VALUES
                        (:user_id, :token, :expiration, :created_at, :updated_at, :type, :extra)";
                } else {
                    // 其他类型更新现有记录
                    $sql = "UPDATE user_tokens SET
                        token = :token,
                        expiration = :expiration,
                        updated_at = :updated_at,
                        type = :type,
                        extra = :extra
                        WHERE user_id = :user_id AND type = :type";
                }
            } else {
                // 新记录插入
                $sql = "INSERT INTO user_tokens
                    (user_id, token, expiration, created_at, updated_at, type, extra)
                    VALUES
                    (:user_id, :token, :expiration, :created_at, :updated_at, :type, :extra)";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->bindParam(':expiration', $expiration, PDO::PARAM_STR);
            $stmt->bindParam(':created_at', $createdAt, PDO::PARAM_STR);
            $stmt->bindParam(':updated_at', $createdAt, PDO::PARAM_STR);
            $stmt->bindParam(':type', $type, PDO::PARAM_STR);
            $stmt->bindParam(':extra', $serializedExtra, PDO::PARAM_LOB);
            $stmt->execute();

            $this->db->commit();
            return $token;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new PDOException("生成 token 发生错误: " . $e->getMessage());
        }
    }

    /**
     * 验证给定的 token 是否有效
     * @param string $token 要验证的 token
     * @param string $type token 类型，必须提供
     * @return bool token 是否有效
     */
    public function validateToken(string $token, string $type): bool
    {
        try {
            // 删除已过期的 token
            $deleteSql = "DELETE FROM user_tokens WHERE expiration <= NOW()";
            $this->db->exec($deleteSql);

            // 验证 token 是否有效
            $sql = "SELECT * FROM user_tokens 
                WHERE token = :token 
                AND type = :type 
                AND expiration > NOW()"; // 添加过期时间验证
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->bindParam(':type', $type, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            // 如果返回结果，token有效
            return $result ? true : false;
        } catch (PDOException $e) {
            throw new PDOException("验证 token 发生错误:" . $e->getMessage());
        }
    }

    /**
     * 根具 token 和 type 返回用户信息
     *
     * @param string $token
     * @param string $type
     * @return array
     */
    public function getInfo(string $token, string $type): array
    {
        try {
            $sql = "SELECT * FROM user_tokens WHERE token = :token AND type = :type";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->bindParam(':type', $type, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            // 返回用户信息
            return $result ? $result : [];
        } catch (PDOException $e) {
            throw new PDOException("获取 token 信息发生错误:" . $e->getMessage());
        }
    }

    /**
     * 获取指定用户下的所有 token
     * @param int $userId 用户ID
     * @return array
     * @throws PDOException
     */
    public function getTokens(int $userId): array
    {
        try {
            $sql = "SELECT * FROM user_tokens WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result ? $result : [];
        } catch (PDOException $e) {
            throw new PDOException("获取 token 信息发生错误:" . $e->getMessage());
        }
    }

    /**
     * 获取 token 图标
     *
     * @param string $type token 类型
     * @return string 图标名称
     */
    public function getTokenIcon($type)
    {
        $icons = [
            'api' => 'vpn_key',
            'clientAuth' => 'devices',
            'verifyEmail' => 'mark_email_read',
            'default' => 'code'
        ];
        return $icons[$type] ?? $icons['default'];
    }

    /**
     * 删除指定用户下的所有token
     *
     * @param int $userId
     * @return void
     */
    public function delet(int $userId): bool
    {
        try {
            $sql = "DELETE FROM user_tokens WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new PDOException("删除 token 发生错误:" . $e->getMessage());
        }
    }
}
