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
define('FRAMEWORK_VERSION', '1.5.0.0');

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
 * 数据库配置
 * 
 * @var array
 */
define('FRAMEWORK_DATABASE', [
    'driver' => 'sqlite',
    'host' => '/data.db',
    'port' => 3306,
    'dbname' => 'live',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8mb4'
]);

/**
 * 安装锁 为true时表示已经安装
 * 
 * @var bool
 */
define('FRAMEWORK_INSTALL_LOCK', true);