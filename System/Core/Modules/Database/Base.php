<?php

namespace XQPF\Core\Modules\Database;

use Exception;
use PDO;

/**
 * 数据库基类
 */
class Base
{
    /**
     * 获取数据库实例
     *
     * @return MySQL|SQLite
     * @throws Exception
     */
    public static function getInstance(): MySQL|SQLite
    {
        $config = FRAMEWORK_DATABASE;
        switch ($config['driver']) {
            case 'mysql':
                return MySQL::getInstance();
            case 'sqlite':
                return SQLite::getInstance();
            default:
                throw new Exception('不支持的数据库驱动: ' . $config['driver']);
        }
    }

    /**
     * 获取数据库连接
     *
     * @return PDO
     * @throws Exception
     */
    public static function getConnection(): PDO
    {
        $connection = self::getInstance()->getConnection();
        return $connection;
    }

    /**
     * 执行 SELECT 查询
     *
     * @param string $table 表名
     * @param array $columns 要查询的列
     * @param array $conditions 查询条件
     * @return array 查询结果
     * @throws Exception
     */
    public static function select(string $table, array $columns = ['*'], array $conditions = []): array
    {
        $connection = self::getConnection();
        $columnString = implode(', ', $columns);
        $whereClause = '';
        $params = [];

        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $key => $value) {
                $whereParts[] = "$key = :$key";
                $params[":$key"] = $value;
            }
            $whereClause = 'WHERE ' . implode(' AND ', $whereParts);
        }

        $sql = "SELECT $columnString FROM $table $whereClause";
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * 执行 INSERT 查询
     *
     * @param string $table 表名
     * @param array $data 要插入的数据
     * @return int 插入的记录 ID
     * @throws Exception
     */
    public static function insert(string $table, array $data): int
    {
        $connection = self::getConnection();
        $columns = array_keys($data);
        $values = array_values($data);
        $columnString = implode(', ', $columns);
        $placeholderString = ':' . implode(', :', $columns);
        $params = array_combine(array_map(function ($col) {
            return ":$col";
        }, $columns), $values);

        $sql = "INSERT INTO $table ($columnString) VALUES ($placeholderString)";
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);

        return $connection->lastInsertId();
    }

    /**
     * 执行 UPDATE 查询
     *
     * @param string $table 表名
     * @param array $data 要更新的数据
     * @param array $conditions 更新条件
     * @return int 受影响的行数
     * @throws Exception
     */
    public static function update(string $table, array $data, array $conditions): int
    {
        $connection = self::getConnection();
        $setClause = '';
        $whereClause = '';
        $params = [];

        if (!empty($data)) {
            $setParts = [];
            foreach ($data as $key => $value) {
                $setParts[] = "$key = :$key";
                $params[":$key"] = $value;
            }
            $setClause = 'SET ' . implode(', ', $setParts);
        }

        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $key => $value) {
                $whereParts[] = "$key = :where_$key";
                $params[":where_$key"] = $value;
            }
            $whereClause = 'WHERE ' . implode(' AND ', $whereParts);
        }

        $sql = "UPDATE $table $setClause $whereClause";
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * 执行 DELETE 查询
     *
     * @param string $table 表名
     * @param array $conditions 删除条件
     * @return int 受影响的行数
     * @throws Exception
     */
    public static function delete(string $table, array $conditions): int
    {
        $connection = self::getConnection();
        $whereClause = '';
        $params = [];

        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $key => $value) {
                $whereParts[] = "$key = :$key";
                $params[":$key"] = $value;
            }
            $whereClause = 'WHERE ' . implode(' AND ', $whereParts);
        }

        $sql = "DELETE FROM $table $whereClause";
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}
