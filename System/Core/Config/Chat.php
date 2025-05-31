<?php

namespace ChatRoom\Core\Config;

/**
 * 聊天配置
 *
 * @var array
 */
class Chat
{
    /**
     * 上传文件
     *
     * @var array
     */
    public array $uploadFile = [
        // 运行上传文件类型
        'allowTypes' => [
            //** image **//
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp',
            'image/svg+xml',
            'image/x-icon',
        ],
        'maxSize' => 9097152 // 最大文件大小 9MB
    ];
}
