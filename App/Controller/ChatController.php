<?php

namespace HuaFengLive\Controller;

use PDO;
use Exception;
use Throwable;
use PDOException;
use HuaFengLive\Helpers\UserHelpers;
use XQPF\Core\Helpers\Helpers;
use XQPF\Core\Modules\Database\Base;

class ChatController
{
    public $Helpers;
    private $userHelpers;
    private $live;

    // 状态消息常量
    const MESSAGE_SUCCESS = true;
    const MESSAGE_SEND_FAILED = '发送消息失败';
    const MESSAGE_FETCH_FAILED = '获取消息失败';
    const MESSAGE_INVALID_REQUEST = '无效请求';
    const ONLINE_USERS_FILE = FRAMEWORK_DIR . '/Writable/';

    public function __construct()
    {
        $this->live = new LiveController;
        $this->userHelpers = new UserHelpers;
        $this->Helpers = new Helpers;
    }

    /**
     * 发送消息
     *
     * @param array $user 用户信息数组
     * @param string $message 发送的消息内容
     * @param int $roomId 直播间ID
     * @return bool
     */
    public function sendMessage(array $user, string $message, int $roomId, array $danmaku): bool
    {
        try {
            if ($this->live->get($roomId)) {
                $userIP = new UserHelpers;
                $db = Base::getInstance()->getConnection();
                $stmt = $db->prepare('INSERT INTO messages (user_id, content, room_id, created_at, user_ip, danmaku) VALUES (?, ?, ?, ?, ?, ?)');
                return $stmt->execute([$user['user_id'], $message, $roomId, date('Y-m-d H:i:s'), $userIP->getIp(), serialize($danmaku)]);
            }
            return false;
        } catch (PDOException $e) {
            throw new PDOException('发送消息发生错误:' . $e->getMessage());
        }
    }

    /**
     * 根据不同条件获取消息
     *
     * @param array $conditions 查询条件，包含消息ID、类型、用户、状态等
     * @return array|null 返回查询到的消息或 null
     */
    public function getMessagesByConditions(array $conditions): ?array
    {
        try {
            $db = Base::getInstance()->getConnection();
            $query = 'SELECT 
                    messages.id,
                    messages.type,
                    messages.content,
                    messages.user_name,
                    messages.user_ip,
                    messages.created_at,
                    messages.status
                FROM messages';

            // 条件数组，用于存储查询的WHERE部分
            $whereClauses = [];
            $params = [];

            // 构建 WHERE 子句
            foreach ($conditions as $key => $value) {
                switch ($key) {
                    case 'messageId':
                        $whereClauses[] = 'messages.id = :messageId';
                        $params[':messageId'] = $value;
                        break;
                    case 'type':
                        $whereClauses[] = 'messages.type = :type';
                        $params[':type'] = $value;
                        break;
                    case 'userId':
                        $whereClauses[] = 'messages.user_id = :user_id';
                        $params[':userId'] = $value;
                        break;
                    case 'status':
                        $whereClauses[] = 'messages.status = :status';
                        $params[':status'] = $value;
                        break;
                    case 'createdAtStart':
                        $whereClauses[] = 'messages.created_at >= :createdAtStart';
                        $params[':createdAtStart'] = $value;
                        break;
                    case 'createdAtEnd':
                        $whereClauses[] = 'messages.created_at <= :createdAtEnd';
                        $params[':createdAtEnd'] = $value;
                        break;
                    case 'roomId':
                        $whereClauses[] = 'messages.room_id = :roomId';
                        $params['roomId'] = $value;
                    default:
                        break;
                }
            }

            // 如果有条件，加入 WHERE 子句
            if (count($whereClauses) > 0) {
                $query .= ' WHERE ' . implode(' AND ', $whereClauses);
            }

            $stmt = $db->prepare($query);
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            $stmt->execute();
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($messages) {
                return $messages;
            } else {
                return null; // 没有找到任何符合条件的消息
            }
        } catch (PDOException $e) {
            throw new PDOException('根据条件查询消息发生错误: ' . $e->getMessage());
        }
    }

    /**
     * 获取消息
     *
     * @param int $offset 偏移量
     * @param int $limit 限制条数
     * @param int $roomId 直播间ID
     * @return array
     */
    public function getMessages(int $roomId, int $offset = 0, int $limit = 10,): array
    {
        try {
            $db = Base::getInstance()->getConnection();

            // 构建查询
            $query = 'SELECT
                    messages.id,
                    messages.type,
                    messages.content,
                    messages.created_at,
                    messages.status,
                    messages.danmaku,
                    users.username,
                    users.email
                FROM messages
                LEFT JOIN users ON messages.user_id = users.user_id
                WHERE messages.room_id = :roomId
                AND messages.status = "active"
                ORDER BY messages.created_at ASC
                LIMIT :limit OFFSET :offset';

            $stmt = $db->prepare($query);
            $stmt->bindValue(':roomId', (int)$roomId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

            $stmt->execute();
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->updataOnlineUsers($roomId);

            // 处理每条消息
            foreach ($messages as &$message) {
                $avatarUrl = $this->userHelpers->getAvatar($message['email'], 25);
                $message['avatar'] = $avatarUrl;
                unset($message['email']);

                // 解析弹幕数据
                if ($message['danmaku']) {
                    $message['danmaku'] = unserialize($message['danmaku']);
                } else {
                    $message['danmaku'] = null;
                }
            }

            return [
                'onlineUsers' => $this->getOnlineUsers($roomId),
                'messages' => $messages
            ];
        } catch (PDOException $e) {
            throw new PDOException('获取消息发送错误:' . $e->getMessage());
        }
    }

    /**
     * 获取消息总数
     *
     * @param int $roomId 直播间ID
     * @return int
     */
    public function getMessageCount(int $roomId): int
    {
        try {
            $db = Base::getInstance()->getConnection();
            $countQuery = 'SELECT COUNT(*) as total FROM messages WHERE room_id = :roomId';
            $stmt = $db->prepare($countQuery);
            $stmt->bindValue(':roomId', $roomId, PDO::PARAM_INT);
            $stmt->execute();
            $totalMessages = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            return (int)$totalMessages;
        } catch (PDOException $e) {
            throw new PDOException('获取消息总数发生错误:' . $e->getMessage());
        }
    }

    /**
     * 获取当前所有在线用户
     * @param int $roomId 直播间ID
     * 
     * @return array 在线用户数据
     */
    public function getOnlineUsers(int $roomId): array
    {
        // 确保直播间存在
        if ($this->live->get($roomId)) {
            $file = self::ONLINE_USERS_FILE . 'room.' . $roomId . '.user.online.list.json';
            if (file_exists($file)) {
                $data = file_get_contents($file);
                $onlineUsers = json_decode($data, true);
                // 确保房间ID存在且是数组
                return isset($onlineUsers) ? $onlineUsers : [];
            }
            return [];
        }
        return [];
    }

    /**
     * 更新在线用户数据
     * @param int $roomId 直播间ID
     *
     * @return int|false
     */
    private function updataOnlineUsers(int $roomId): int|false
    {
        try {
            // 确保直播间存在
            if ($this->live->get($roomId)) {
                $userHelpers = new UserHelpers;
                $userInfo = $userHelpers->getUserInfoByEnv();
                if (empty($userInfo)) {
                    return false;
                }
                // 获取当前房间的在线用户数据
                $onlineUsers = $this->getOnlineUsers($roomId);
                // 更新房间内的用户数据
                $userUpdated = false;
                foreach ($onlineUsers as &$user) {
                    if ($user['user_id'] == $userInfo['user_id']) {
                        // 如果用户已存在，更新用户数据
                        $user['last_time'] = time();
                        $userUpdated = true;
                        break;
                    }
                }
                // 如果该用户还未在房间中，则添加该用户
                if (!$userUpdated) {
                    $onlineUsers[] = [
                        'user_id' => $userInfo['user_id'],
                        'avatar_url' => $userHelpers->getAvatar($userInfo['email']),
                        'last_time' => time()
                    ];
                }
                // 更新当前房间的在线用户数据
                $allOnlineUsers = $onlineUsers;
                // 将更新后的所有在线用户数据写回文件
                return file_put_contents(self::ONLINE_USERS_FILE . 'room.' . $roomId . '.user.online.list.json', json_encode($allOnlineUsers, JSON_UNESCAPED_UNICODE));
            }
            return false;
        } catch (Throwable $e) {
            throw new Exception('更新在线用户列表失败:' . $e->getMessage());
        }
    }
}
