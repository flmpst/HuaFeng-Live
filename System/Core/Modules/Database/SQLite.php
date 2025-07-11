<?php

namespace XQPF\Core\Modules\Database;

use PDO;
use PDOException;

/**
 * SQLite 数据库类
 *
 * 该类提供了对 SQLite 数据库的基本操作，包括连接、查询等。
 * 
 * @package XQPF\Core\Modules\Database
 */
class SQLite
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            // 确保数据库文件存在
            if (empty(FRAMEWORK_DIR . FRAMEWORK_DATABASE['host']) || !is_file(FRAMEWORK_DIR . FRAMEWORK_DATABASE['host'])) {
                throw new PDOException('数据库文件不存在，请检查配置文件！');
            }
            $this->connection = new PDO('sqlite:' . FRAMEWORK_DIR . FRAMEWORK_DATABASE['host']);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // 设置 SQLite 超时时间和启用 WAL 模式
            $this->connection->exec('PRAGMA busy_timeout = 5000;');
            $this->connection->exec('PRAGMA journal_mode=WAL;');
        } catch (PDOException $e) {
            throw new PDOException('SQLite数据库错误：' . $e->getMessage());
        }
    }

    /**
     * 获取 SQLite 实例
     *
     * @return SQLite
     */
    public static function getInstance(): SQLite
    {
        if (!self::$instance) {
            self::$instance = new SQLite();
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
        // 调试模式下记录查询
        if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
            $this->connection->setAttribute(PDO::ATTR_STATEMENT_CLASS, [DebugPDOStatement::class, [$this->connection]]);
        }
        return $this->connection;
    }
}
