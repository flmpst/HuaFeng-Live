<?php

namespace HuaFengLive\Controller;

use Exception;
use PDOException;
use HuaFengLive\Config\AppConfig;
use XQPF\Core\Modules\Database\Base;
use HuaFengLive\Helpers\EmailHelpers;
use HuaFengLive\Helpers\UserHelpers;
use XQPF\Core\Helpers\Helpers;
use HuaFengLive\Modules\TokenModules;

class UserController
{
    public $Helpers;
    private $userHelpers;
    private $tokenManager;

    // 状态常量
    const CAPTCHA_ERROR = '未验证CAPTCHA';
    const INVALID_METHOD_MESSAGE = '无效的方法。';
    const METHOD_NOT_PROVIDED_MESSAGE = '方法未提供。';
    const DISABLE_USER_REGIDTRATION = '新用户注册已禁用';
    const INVALID_REQUEST_METHOD_MESSAGE = '无效的请求方法。';

    /**
     * UserController
     *
     */
    public function __construct()
    {
        $this->Helpers = new Helpers;
        $this->userHelpers = new UserHelpers;
        $this->tokenManager = new TokenModules;
    }

    /**
     * 用户认证方法
     * 如果用户已存在则登录，否则注册新用户
     *
     * @param string $email 用户邮箱
     * @param string $password 用户密码
     */
    public function auth(string $email, string $password)
    {
        if ($this->userHelpers->getUserInfo(null, null, $email, false) !== []) {
            $this->login($email, $password);
        } else {
            $this->register($email, $password);
        }
    }

    /**
     * 验证用户密码
     *
     * @param int $userId 用户ID
     * @param string $password 明文密码
     * @return array|null 返回用户信息数组或null
     */
    public function verifyPassword(int $userId, string $password)
    {
        $user = $this->userHelpers->getUserInfo(null, $userId);
        if (!$user) return false;

        return password_verify($password, $user['password']);
    }

    /**
     * @param string $email
     * @param string $password
     */
    public function register($email, $password)
    {
        try {
            $emailSender = new EmailHelpers;
            $appConfig = new AppConfig;
            $ip = $this->userHelpers->getIp();
            $db = Base::getInstance()->getConnection();

            // 校验邮箱格式
            if (!$this->userHelpers->validateEmail($email)) {
                return $this->Helpers->jsonResponse(400, "邮箱格式不合法");
            }

            // 检查邮箱是否已被注册
            if ($this->userHelpers->isEmailTaken($email)) {
                return $this->Helpers->jsonResponse(400, '邮箱已被注册');
            }

            // 密码加密
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // 插入用户数据
            $stmt = $db->prepare('INSERT INTO users (username, email, password, created_at, register_ip) VALUES (?, ?, ?, ?, ?)');
            $isSuccessful = $stmt->execute([$email, $email, $passwordHash, date('Y-m-d H:i:s'), $ip]);

            // 获取用户信息
            $userInfo = $this->userHelpers->getUserInfo(null, null, $email, false);

            // 生成验证链接
            $verificationLink = $this->Helpers->getCurrentUrl() . '/verify/email?token=' . $this->tokenManager->generateToken($userInfo['user_id'], 'verifyEmail', '+1 hour', null);

            // 发送验证邮件
            $emailContent = '点击此链接验证：<a href="' . $verificationLink . '">' . $verificationLink . '</a><br><br>如果您没有请求此操作，请忽略此邮件。';
            if (!$emailSender->send($appConfig->email['smtp']['username'], '花枫直播', $email, '验证您的邮箱', $emailContent)) {
                return $this->Helpers->jsonResponse(500, '邮件发送失败');
            }

            if ($isSuccessful) {
                return $this->Helpers->jsonResponse(200, true);
            } else {
                return $this->Helpers->jsonResponse(500, '注册失败');
            }
        } catch (PDOException $e) {
            throw new PDOException('注册发生错误:' . $e->getMessage());
        }
    }

    /**
     * @param string $username
     * @param string $password 明文
     */
    public function login($email, $password)
    {
        try {
            if (!$this->userHelpers->validateEmail($email)) {
                return $this->Helpers->jsonResponse(400, "邮箱格式不合法");
            }
            $user = $this->userHelpers->getUserInfo(null, null, $email, \false);
            if (empty($user)) {
                return $this->Helpers->jsonResponse(400, '用户不存在');
            }
            if ($user['status'] === 0) {
                return $this->Helpers->jsonResponse(400, "未完成邮箱验证，请检查垃圾邮件",);
            }
            if (!password_verify($password, $user['password'])) {
                return $this->Helpers->jsonResponse(400, '密码错误');
            }
            return $this->Helpers->jsonResponse(200, '登录成功', $this->updateLoginInfo($user));
        } catch (PDOException $e) {
            throw new PDOException('登录发生错误:' . $e->getMessage());
            if ($return) {
                return "内部服务器错误。请联系管理员。";
            } else {
                return $this->Helpers->jsonResponse(500, "内部服务器错误。请联系管理员。");
            }
        }
    }

    /**
     * 更新登录信息
     * @param array $user 用户信息数组
     * @return array
     */
    private function updateLoginInfo(array $user)
    {
        // 移除无用信息
        unset($user['password']);
        $user['token'] = bin2hex(hash('sha256', random_bytes(32) . $user['user_id'], true));
        $_SESSION['user_login_info'] = json_encode($user);
        return $user;
    }

    /**
     * 更新用户信息
     *
     * @param int $userId 用户ID
     * @param array $data 包含用户更新信息的关联数组
     *                    格式为 ['username' => '新用户名', 'email' => '新邮箱']
     * @return bool 更新是否成功
     * @throws Exception
     */
    public function updateUser(int $userId, array $data = []): bool
    {
        if (empty($data)) {
            return true;
        }
        $db = Base::getInstance()->getConnection();
        try {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }
            $fields = [];
            $params = [':user_id' => $userId];
            foreach ($data as $key => $value) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
            if (empty($fields)) {
                return true;
            }
            $fieldsString = implode(', ', $fields);
            $stmt = $db->prepare("UPDATE users SET $fieldsString WHERE user_id = :user_id");
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $db->commit();
            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw new PDOException("更新用户信息出错:" . $e->getMessage());
        }
    }
}
