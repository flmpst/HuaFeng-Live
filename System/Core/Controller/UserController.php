<?php

namespace ChatRoom\Core\Controller;

use PDOException;
use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Helpers\Helpers;
use ChatRoom\Core\Database\SqlLite;
use ChatRoom\Core\Helpers\Email;
use ChatRoom\Core\Modules\TokenManager;

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
        $this->userHelpers = new User;
        $this->Helpers = new Helpers;
        $this->userHelpers = new User;
        $this->tokenManager = new TokenManager;
    }

    public function auth($email, $password)
    {
        if ($this->userHelpers->getUserInfo(null, null, $email, false) !== []) {
            $this->login($email, $password);
        } else {
            $this->register($email, $password);
        }
    }

    /**
     * @param string $email
     * @param string $password
     */
    public function register($email, $password)
    {
        try {
            $sedEmail = new Email;
            $ip = $this->userHelpers->getIp();
            $db = SqlLite::getInstance()->getConnection();

            // 检查邮箱是否重复
            if ($this->userHelpers->isEmailTaken($email)) {
                return $this->Helpers->jsonResponse(400, '邮箱已被注册');
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // 插入用户数据
            $stmt = $db->prepare('INSERT INTO users (username, email, password, created_at, register_ip) VALUES (?, ?, ?, ?, ?)');
            $isSuccessful = $stmt->execute([$email, $email, $passwordHash, date('Y-m-d H:i:s'), $ip]);

            if ($isSuccessful) {
                // 发送验证邮件
                $userInfo = $this->userHelpers->getUserInfo(null, null, $email, false);
                $verificationLink = $this->Helpers->getCurrentUrl() . '/api/v1/user/verifyEmail?token=' . $this->tokenManager->generateToken($userInfo['user_id'], '+1 hour');
                if ($sedEmail->send('live@email.dfggmc.top', '花枫直播', $email, '验证您的邮箱', '点击此链接验证：<a href="' . $verificationLink . '">' . $verificationLink . '</a><br><br>如果您没有请求此操作，请忽略此邮件。')) {
                    return $this->Helpers->jsonResponse(200, true);
                } else {
                    return $this->Helpers->jsonResponse(500, '邮件发送失败');
                }
            } else {
                return $this->Helpers->jsonResponse(500, '注册失败，请重试');
            }
        } catch (PDOException $e) {
            throw new PDOException('注册发生错误:' . $e);
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
            throw new PDOException('登录发生错误:' . $e);
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
}
