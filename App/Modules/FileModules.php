<?php

namespace HuaFengLive\Modules;

use Exception;
use Throwable;

class FileModules
{
    private $allowedTypes;
    private $maxSize;
    private $uploadDir;

    public function __construct(array $allowedTypes, int $maxSize)
    {
        $this->allowedTypes = $allowedTypes;
        $this->maxSize = $maxSize;
        $this->uploadDir = FRAMEWORK_DIR . "/StaticResources/uploads/";
    }

    /**
     * 上传文件并返回相对路径及更多信息
     *
     * @param array $file $_FILES数组
     * @param int $userId 用户ID
     * @return array 返回包含成功状态、路径、文件名、大小、类型等信息的数组
     * @throws Exception 如果上传失败抛出异常
     */
    public function upload(array $file, int $userId)
    {
        try {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception($this->getUploadErrorMessage($file['error']));
            }

            $fileType = mime_content_type($file['tmp_name']);
            if (!in_array($fileType, $this->allowedTypes)) {
                throw new Exception('不允许的文件类型: ' . $fileType);
            }

            if ($file['size'] > $this->maxSize) {
                throw new Exception('文件大小超过限制');
            }

            // uploads/Y/m/d/u_userId/
            $relativePath = date('Y/m/d') . "/u_$userId/";
            $absolutePath = $this->uploadDir . $relativePath;

            if (!is_dir($absolutePath)) {
                if (!mkdir($absolutePath, 0775, true)) {
                    throw new Exception('无法创建上传目录');
                }
            }

            // 使用文件内容的md5作为文件名
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileMd5 = md5_file($file['tmp_name']);
            $filename = $fileMd5 . '.' . $extension;
            $relativeFilePath = $relativePath . $filename;
            $absoluteFilePath = $absolutePath . $filename;

            if (!move_uploaded_file($file['tmp_name'], $absoluteFilePath)) {
                throw new Exception('无法移动上传的文件');
            }

            // 返回
            return [
                'path'       => '/StaticResources/uploads/' . $relativeFilePath,
                'filename'   => $filename,
                'original'   => $file['name'],
                'size'       => $file['size'],
                'type'       => $fileType,
                'extension'  => $extension,
                'md5'        => $fileMd5,
                'uploadTime' => date('Y-m-d H:i:s'),
            ];
        } catch (Throwable $e) {
            throw new Exception('文件上传失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取上传错误信息
     * 
     * @param int $errorCode 错误代码
     * @return string 错误信息
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => '文件大小超过服务器限制',
            UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
            UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败',
            UPLOAD_ERR_EXTENSION => '上传被PHP扩展中断'
        ];

        return $uploadErrors[$errorCode] ?? '未知上传错误';
    }
}
