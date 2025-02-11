<?php

namespace ChatRoom\Core\Database;

use PDO;
use PDOException;

class MySQL
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            $dsn = 'mysql:host=' . FRAMEWORK_DATABASE['host'] . ';dbname=' . FRAMEWORK_DATABASE['dbname'];
            $username = FRAMEWORK_DATABASE['username'];
            $password = FRAMEWORK_DATABASE['password'];
            $options = [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            ];

            $this->connection = new PDO($dsn, $username, $password, $options);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new PDOException('MySQL数据库错误：' . $e);
        }
    }

    /**
     * 获取 MySQL 实例
     *
     * @return MySQL
     */
    public static function getInstance(): MySQL
    {
        if (!self::$instance) {
            self::$instance = new MySQL();
        }
        return self::$instance;
    }

    /**
     * 获取数据库连接
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
