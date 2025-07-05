<?php

/**
 * 当前程序根目录
 * 
 * @var string
 */
define('FRAMEWORK_DIR', dirname(__FILE__));

/**
 * 定义版本
 * 
 * @var int
 */
define('FRAMEWORK_VERSION', '2.5.0.1');

/**
 * 当前系统文件目录
 * 
 * @var string
 */
define('FRAMEWORK_SYSTEM_DIR', FRAMEWORK_DIR . '/System');

/**
 * 当前核心目录
 * 
 * @var string
 */
define('FRAMEWORK_CORE_DIR', FRAMEWORK_DIR . '/System/Core');

/**
 * 应用文件目录
 * 
 * @var string
 */
define('FRAMEWORK_APP_PATH', FRAMEWORK_DIR . '/App');

/**
 * 日志目录
 */
define('FRAMEWORK_LOG_DIR', FRAMEWORK_DIR . '/Writable/logs');

/**
 * 数据库配置
 * 
 * @var array
 */
define('FRAMEWORK_DATABASE', [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'dbname' => 'huafeng_live',
    'username' => 'root',
    'password' => '',
    'charset' => ''
]);

/**
 * 安装锁 为true时表示已经安装
 * 
 * @var bool
 */
define('FRAMEWORK_INSTALL_LOCK', true);
