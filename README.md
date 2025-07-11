# 花枫直播间 | 自托管式直播列表

确保您的服务器安装了以下 PHP 扩展(默认情况下虚拟主机厂商已开启)：

- curl
- sqlite / mysql
- mbstring
- pdo_sqlite

初次使用需要设置
``config.global.php``
```php
/**
 * 数据库配置
 * 
 * @var array
 */
define('FRAMEWORK_DATABASE', [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'dbname' => 'live',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8mb4'
]);
```

``/App/Config/AppConfig.php``
```php
public array $geetest
public array $email
```

导入.sql数据库文件
