<?php

namespace HuaFengLive\Controller;

use PDO;
use Exception;
use PDOException;
use XQPF\Core\Modules\Database\Base;

class LiveController
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
            $stmt = $this->db->prepare("SELECT * FROM live_list WHERE id = :id AND status = 'active'");
            $stmt->bindParam(':id', $liveId, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                return $result;
            }
            return [];
        } catch (PDOException $e) {
            throw new Exception("获取直播信息出错: " . $e->getMessage());
        }
    }

    /**
     * 设置值，如果不存在则进行创建
     *
     * @param array $value 直播信息数组值
     * @param int|null $liveId 直播间ID，若存在则执行更新
     * @return int 返回直播间ID
     * @throws Exception 如果数据库操作出错，抛出异常
     */
    public function set(array $value, ?int $liveId = null): int
    {
        try {
            // 如果提供了 $liveId，则先检查是否存在
            if ($liveId) {
                $stmt = $this->db->prepare("SELECT id FROM live_list WHERE id = :id");
                $stmt->bindParam(':id', $liveId, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $result = false;
            }

            // 动态生成 SET 子句和绑定参数
            $columns = [];
            $bindings = [];
            foreach ($value as $column => $columnValue) {
                $columns[] = "`$column` = :$column";
                $bindings[":$column"] = $columnValue;
            }

            if ($result) {
                // 如果存在 $liveId，则执行 UPDATE
                $sql = "UPDATE live_list SET " . implode(", ", $columns) . " WHERE id = :id";
                $bindings[':id'] = $liveId;
            } else {
                // 如果不存在，则执行 INSERT
                $sql = "INSERT INTO live_list (" . implode(", ", array_keys($value)) . ") VALUES (" . implode(", ", array_map(function ($column) {
                    return ":$column";
                }, array_keys($value))) . ")";
            }

            // 执行 SQL 语句
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindings);

            // 返回插入的 ID（如果是插入操作）
            return $liveId ?? $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new PDOException("获取直播信息值出错: " . $e->getMessage());
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
            $stmt = $this->db->query("SELECT * FROM live_list WHERE status = 'active'");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            $stmt = $this->db->prepare("UPDATE live_list SET status = 'delete' WHERE id = :id");
            $stmt->bindParam(':id', $liveId, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new PDOException("删除直播信息出错:" . $e->getMessage());
        }
    }
}
