<?php

namespace ChatRoom\Core\Helpers;

use PDO;
use Exception;
use ChatRoom\Core\Database\Base;
use ChatRoom\Core\Modules\TokenManager;
use PDOException;
use Throwable;

/**
 * 用户辅助类
 */
class User
{
    private $db;
    protected $tokenManager;

    public function __construct()
    {
        $this->db = Base::getInstance()->getConnection();
        $this->tokenManager = new TokenManager;;
    }

    /**
     * 验证邮箱格式
     *
     * @param string $email
     * @return bool
     */
    public function validateEmail(string $email): bool
    {
        return (bool)preg_match('/^[a-zA-Z0-9]+([-_.][a-zA-Z0-9]+)*@([a-zA-Z0-9]+[-.])+([a-z]{2,5})$/ims', $email);
    }

    /**
     * 获取特定用户信息
     * -------------
     * 返回用户信息数组
     * 
     * @param string|null $username
     * @param int|null $userId
     * @param string|null $email
     * @param bool $verifyEmail
     * @return array
     * @throws Exception
     */
    public function getUserInfo(?string $username = null, ?int $userId = null, ?string $email = null, $verifyEmail = true): array
    {
        try {
            // 基础查询语句
            $sql = "SELECT * FROM users WHERE ";
            // 判断查询条件
            if ($userId !== null) {
                $sql .= "user_id = :user_id";
            } elseif ($username !== null) {
                $sql .= "username = :username";
            } elseif ($email !== null) {
                $sql .= "email = :email";
            } else {
                return [];
            }
            if ($verifyEmail) {
                $sql .= " AND status = 1";
            }
            $stmt = $this->db->prepare($sql);
            if ($userId !== null) {
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            } elseif ($username !== null) {
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            } elseif ($email !== null) {
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            }
            $stmt->execute();
            $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            return $userInfo ?: [];
        } catch (Exception $e) {
            throw new PDOException("查询用户信息出错:" . $e->getMessage());
        }
    }

    /**
     * 获取用户数据
     * ----------
     * 支持分页
     *
     * @param int $limit 每页显示的记录数
     * @param int $offset 偏移量
     * @return array
     * @throws Exception
     */
    public function getUsersWithPagination(int $limit, int $offset): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users LIMIT :limit OFFSET :offset");
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new PDOException("查询分页用户出错:" . $e->getMessage());
        }
    }

    /**
     * 根据当前环境获取用户信息
     * --------------------
     * 返回结构与数据库一致
     *
     * @return array
     * @throws Exception
     */
    public function getUserInfoByEnv(): array
    {
        try {
            // 首先检查请求头中是否有 Authorization Token
            $token = $_POST['token'] ?? null;
            $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

            // 如果请求头中有 Authorization Token，优先使用
            if ($authorizationHeader) {
                $token = str_replace('Bearer ', '', $authorizationHeader);  // 如果是 Bearer Token 格式
            }

            // 从 session 中获取用户登录信息
            $userLoginInfo = json_decode($_SESSION['user_login_info'] ?? '', true);

            // 如果 session 中有 token，使用 session 中的 token
            if (!empty($userLoginInfo['token'])) {
                $token = $userLoginInfo['token'];
                $userId = $userLoginInfo['user_id'];
            } elseif (!empty($token)) {
                // 如果传入了 token，根据 token 获取用户信息
                $tokenInfo = $this->tokenManager->getInfo($token, 'clientAuth') ?? $this->tokenManager->getInfo($token, 'api');
                $userId = $tokenInfo['user_id'] ?? null;
            } else {
                return [];
            }

            // 获取用户信息
            $userInfo = $this->getUserInfo(null, $userId);
            if ($token) {
                $userInfo['token'] = $token;
            }

            return $userInfo ?? [];
        } catch (Throwable $e) {
            throw new Exception('根据当前环境获取用户信息出错：' . $e->getMessage());
        }
    }

    /**
     * 获取用户总数
     *
     * @return int
     * @throws Exception
     */
    public function getUserCount(): int
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM users");
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            throw new PDOException("获取用户总数出错:" . $e->getMessage());
        }
    }

    /**
     * 检查邮箱是否已被使用
     *
     * @param string $email
     * @return bool
     */
    public function isEmailTaken(string $email): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            throw new PDOException("检查用户名是否被使用出错:" . $e->getMessage());
        }
    }

    /**
     * 获取用户IP
     *
     * @return string
     */
    public function getIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return 'unknown';
    }

    /**
     * 获取指定电子邮件地址的Cravatar URL或完整的图像标签。
     *
     * @param string $email 邮箱地址
     * @param string $s 的像素大小，默认为80px [1 - 2048]
     * @param string $d 默认图像集使用[404 | mp | identicon | monsterid | wavatar]
     * @param string $r 最大评级（包括）[g | pg | r | x]
     * @param boole $img True仅为URL返回完整的img标签False
     * @param array $atts 可选，IMG标签中包含的额外的键值对属性
     * @return string URL或img标签
     * @来源https://cravatar.com/developer/php-image-requests
     */
    public function getAvatar($email, $s = 80, $d = 'mp', $r = 'g', $img = false, $atts = array())
    {
        $url = 'https://cravatar.com/avatar/';
        $url .= md5(strtolower(trim($email)));
        $url .= "?s=$s&d=$d&r=$r";
        if ($img) {
            $url = '<img src="' . $url . '"';
            foreach ($atts as $key => $val)
                $url .= ' ' . $key . '="' . $val . '"';
            $url .= ' />';
        }
        return $url;
    }

    /**
     * 检查用户登录状态
     *
     * @return bool
     */
    public function checkUserLoginStatus(): bool
    {
        try {
            if (empty($_SESSION['user_login_info'])) {
                return false;
            }
            $data = json_decode($_SESSION['user_login_info'], true);
            // 检查会话信息是否完整
            if (empty($data['token']) || empty($data['user_id'])) {
                return false;
            }
            // 使用 验证用户是否存在
            if ($this->getUserInfo(null, $data['user_id'])) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            throw new PDOException("获取用户登录状态出错:" . $e->getMessage());
        }
    }
}
