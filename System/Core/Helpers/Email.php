<?php

namespace ChatRoom\Core\Helpers;

use ChatRoom\Core\Config\App;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email
{
    private $mail;

    // 构造函数，初始化 PHPMailer 实例
    public function __construct()
    {
        $this->mail = new PHPMailer(true);
    }

    // 配置 SMTP 设置
    private function configureSMTP()
    {
        $config = new App;
        if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
            $this->mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
        }
        $this->mail->isSMTP();
        $this->mail->SMTPAuth = true;
        $this->mail->Host = $config->email['smtp']['host'];
        $this->mail->Port = $config->email['smtp']['port'];
        $this->mail->Username = $config->email['smtp']['username'];
        $this->mail->Password = $config->email['smtp']['password'];
        $this->mail->SMTPSecure = $config->email['smtp']['secure'];
    }

    /**
     * 发送邮件
     *
     * @param [type] $from
     * @param [type] $to
     * @param [type] $subject
     * @param [type] $body
     * @return bool
     */
    public function send($from, $fromName, $to, $subject, $body): bool
    {
        try {
            // 配置 SMTP
            $this->configureSMTP();

            // 设置发件人邮箱
            $this->mail->setFrom($from, $fromName);

            // 添加收件人，可以传递数组或单个邮箱地址
            if (is_array($to)) {
                foreach ($to as $address => $name) {
                    $this->mail->addAddress($address, $name); // 添加收件人
                }
            } else {
                $this->mail->addAddress($to); // 如果只有一个收件人
            }

            // 设置邮件内容格式为 HTML
            $this->mail->isHTML(true);

            // 设置邮件的编码为 UTF-8
            $this->mail->CharSet = 'UTF-8';  // 明确指定字符集

            // 设置邮件主题和正文内容
            $this->mail->Subject = $subject;
            $this->mail->Body    = $body;
            // 设置纯文本版本的邮件内容，以便不支持 HTML 的客户端使用
            $this->mail->AltBody = strip_tags($body);

            // 发送邮件
            $this->mail->send();
            return true;
        } catch (Exception) {
            throw new Exception("邮件发送失败。Mailer 错误: {$this->mail->ErrorInfo}");
        }
    }
}
