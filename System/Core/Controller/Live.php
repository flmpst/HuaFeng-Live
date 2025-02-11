<?php

namespace ChatRoom\Core\Controller;

use PDO;
use Exception;
use PDOException;
use ChatRoom\Core\Database\Base;

class Live
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Base::getInstance()->getConnection();
    }

    /**
     * 获取指定直播信息
     * 
     * @param string $liveId 直播的ID
     * @return mixed 返回直播信息的值，如果是JSON字符串则解析为数组，否则返回原始值，如果不存在则返回 null
     * @throws Exception 如果数据库操作出错，抛出异常
     */
    public function get($liveId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM room_sets WHERE id = :id AND status = 'active'");
            $stmt->bindParam(':id', $liveId, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $value = $result['value'];
                return unserialize($value);
            }
            return null; // 如果没有找到相关数据，返回 null
        } catch (PDOException $e) {
            throw new Exception("获取直播信息出错: " . $e->getMessage());
        }
    }

    /**
     * 设置值，如果不存在则进行创建
     *
     * @param array $value 直播信息数组值
     * @return int 返回直播间ID
     * @throws Exception 如果数据库操作出错，抛出异常
     */
    public function set(array $value, $liveId = null): int
    {
        try {
            // 检查直播信息是否存在
            $stmt = $this->db->prepare("SELECT id FROM room_sets WHERE id = :id");
            $stmt->bindParam(':id', $liveId, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // 根据结果执行更新或插入操作
            if ($result) {
                $stmt = $this->db->prepare("UPDATE room_sets SET value = :value WHERE id = :id");
                $stmt->bindParam(':id', $liveId, PDO::PARAM_STR);
                $stmt->bindParam(':value', serialize($value), PDO::PARAM_STR);
            } else {
                $stmt = $this->db->prepare("INSERT INTO room_sets (user_id, value) VALUES (:user_id, :value)");
                $stmt->bindParam(':user_id', $value['user_id'], PDO::PARAM_INT);
                $stmt->bindParam(':value', serialize($value), PDO::PARAM_STR);
            }

            $stmt->execute();
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new PDOException("获取直播信息值出错: " . $e);
        }
    }

    /**
     * 获取所有直播信息
     *
     * @return array 所有直播信息的键值对数组
     * @throws Exception 如果数据库操作出错，抛出异常
     */
    public function getAll(): array
    {
        try {
            // 执行查询，获取所有状态为 active 的直播设置
            $stmt = $this->db->query("SELECT * FROM room_sets WHERE status = 'active'");
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result = [];
            foreach ($settings as $setting) {
                // 获取原始 value 字段
                $value = $setting['value'];
                $parsedValue = unserialize($value);
                $result[$setting['id']] = $parsedValue;
            }
            return $result;
        } catch (PDOException $e) {
            throw new Exception("获取所有直播信息出错: " . $e->getMessage());
        }
    }

    /**
     * 删除直播（标记为删除）
     *
     * @param string $liveId 直播信息ID
     * @throws Exception 如果数据库操作出错，抛出异常
     */
    public function delete(int $liveId): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE room_sets SET status = 'delete' WHERE id = :id");
            $stmt->bindParam(':id', $liveId, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new PDOException("删除直播信息出错:" . $e);
        }
    }
}
