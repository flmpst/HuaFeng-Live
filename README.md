# 基于子辰聊天室魔改

确保您的服务器安装了以下 PHP 扩展(默认情况下虚拟主机厂商已开启)：

- curl
- sqlite3
- mbstring
- pdo_sqlite

初次使用需要设置

``/System/Core/Config/App.php``

```php
public array $geetest
public array $email
```