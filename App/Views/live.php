<?php

use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Controller\Live;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$liveId = isset(explode('/', trim($uri, '/'))[0]) ? explode('/', trim($uri, '/'))[0] : null;
$live = new Live;
$liveData = $live->get($liveId);
$userHelpers = new User;

// 如果直播间数据不存在，显示404页面
if (!$liveData) {
?>
    <!DOCTYPE html>
    <html lang="zh">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>直播间不存在 | 花枫 Live</title>
        <meta name="description" content="花枫 Live 直播间不存在">
        <link rel="stylesheet" href="/StaticResources/css/common.css?<?= FRAMEWORK_VERSION ?>">
        <link href="https://cdn.bootcdn.net/ajax/libs/mdui/1.0.2/css/mdui.min.css" rel="stylesheet">
        <link href="https://cdn.bootcdn.net/ajax/libs/normalize/8.0.1/normalize.min.css" rel="stylesheet">
        <style>
            body {
                font-family: 'Segoe UI', 'Microsoft YaHei', Arial, sans-serif;
                margin: 0;
                padding: 0;
                height: 100vh;
                background-color: #f5f5f5;
            }

            .mdui-container {
                padding: 20px;
                max-width: 600px;
                margin: 0 auto;
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                margin-top: 100px;
            }

            .notfound-card {
                padding: 30px;
                text-align: center;
            }

            .notfound-icon {
                font-size: 72px;
                margin-bottom: 20px;
            }

            .info {
                color: #2196F3;
            }

            .error {
                color: #F44336;
            }

            .warning {
                color: #FF9800;
            }

            .notfound-message {
                font-size: 18px;
                margin: 20px 0;
                line-height: 1.6;
            }

            .live-id {
                word-break: break-all;
                background: #f5f5f5;
                padding: 10px;
                border-radius: 4px;
                margin: 15px 0;
            }

            .mdui-btn {
                margin: 10px;
                border-radius: 4px;
                min-width: 120px;
            }

            @media (max-width: 600px) {
                .mdui-container {
                    margin-top: 60px;
                    padding: 15px;
                }

                .notfound-card {
                    padding: 20px;
                }

                .notfound-icon {
                    font-size: 48px;
                }
            }
        </style>
    </head>

    <body class="mdui-theme-primary-indigo mdui-theme-accent-pink">
        <div class="mdui-appbar mdui-appbar-fixed mdui-color-grey-800">
            <div class="mdui-toolbar">
                <a href="/" class="mdui-typo-headline">直播间不存在</a>
                <div class="mdui-toolbar-spacer"></div>
                <a href="https://dfggmc.top" target="_blank" class="mdui-btn mdui-btn-dense">
                    <i class="mdui-icon material-icons">link</i> 花枫官网
                </a>
            </div>
        </div>

        <div class="mdui-container">
            <div class="notfound-card">
                <div class="notfound-icon warning">
                    <i class="mdui-icon material-icons">live_tv</i>
                </div>
                <h2 class="mdui-typo-title">直播间不存在</h2>
                <div class="notfound-message">
                    您访问的直播间可能已被删除或ID不正确
                </div>

                <div class="live-id">
                    直播间ID: <?= htmlspecialchars($liveId, ENT_QUOTES, 'UTF-8') ?>
                </div>

                <p>请检查URL是否正确，或返回首页查看其他直播内容</p>

                <div>
                    <a href="/" class="mdui-btn mdui-btn-raised mdui-ripple mdui-color-theme">
                        <i class="mdui-icon material-icons">home</i> 返回首页
                    </a>
                </div>
            </div>
        </div>

        <script src="/StaticResources/js/mdui.min.js"></script>
        <script src="/StaticResources/js/jquery.min.js"></script>
    </body>

    </html>
<?php
    exit; // 结束脚本执行
}

?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimal-ui">
    <link href="https://cdn.bootcdn.net/ajax/libs/mdui/1.0.2/css/mdui.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/normalize/8.0.1/normalize.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/StaticResources/css/live.css?<?= FRAMEWORK_VERSION ?>">
    <link rel="stylesheet" href="/StaticResources/css/common.css?<?= FRAMEWORK_VERSION ?>">
    <title><?= htmlspecialchars($liveData['name'] ?? 'DFGG LIVE') ?> - DFGG LIVE</title>
    <meta name="description" content="<?= htmlspecialchars($liveData['description'] ?? '') ?>">
    <style>
        <?= htmlspecialchars($liveData['css'] ?? '') ?>
    </style>
</head>

<body>
    <div class="mdui-appbar mdui-appbar-scroll-hide">
        <div class="mdui-toolbar mdui-color-theme">
            <h1 class="mdui-typo-headline">
                <?= htmlspecialchars($liveData['name'] ?? '直播间不存在') ?>
                <small>
                    <span id="online-users-list-count"></span>个人正在看
                </small>
            </h1>
            <div class="mdui-toolbar-spacer"></div>
            <a class="mdui-typo" href="/">返回首页</a>
            <?php
            // 安全获取用户信息
            $currentUser = $userHelpers->getUserInfoByEnv() ?? [];
            $isAdmin = isset($currentUser['group_id']) && $currentUser['group_id'] === 1;
            $isOwner = isset($currentUser['user_id'], $liveData['user_id']) && $liveData['user_id'] === $currentUser['user_id'];

            if ($isAdmin || $isOwner) {
                // 安全获取直播数据，设置默认值
                $liveName = htmlspecialchars($liveData['name'] ?? '');
                $liveDescription = htmlspecialchars($liveData['description'] ?? '');
                $livePic = htmlspecialchars($liveData['pic'] ?? '');
                $liveVideoSource = htmlspecialchars($liveData['video_source'] ?? '');
                $liveVideoSourceType = htmlspecialchars($liveData['video_source_type'] ?? '');
                $liveCss = htmlspecialchars($liveData['css'] ?? '');
            ?>
                <button class="mdui-btn mdui-ripple mdui-btn-icon" mdui-dialog="{target: '#edit-live'}"><i class="mdui-icon material-icons">settings</i></button>
                <div class="mdui-dialog custom-dialog" id="edit-live">
                    <div class="mdui-dialog-title mdui-color-grey-800">
                        修改直播间信息 <span id="edit-live-msg" class="mdui-color-red"></span>
                    </div>
                    <form class="mdui-dialog-content" id="edit-live-form">
                        <div class="mdui-textfield">
                            <label class="mdui-textfield-label mdui-text-color-white">直播间名称</label>
                            <input class="mdui-textfield-input mdui-text-color-white" name="name" type="text" value="<?= $liveName ?>" />
                        </div>
                        <div class="mdui-textfield">
                            <label class="mdui-textfield-label mdui-text-color-white">描述</label>
                            <textarea class="mdui-textfield-input mdui-text-color-white" name="description" type="text"><?= $liveDescription ?></textarea>
                        </div>
                        <div class="mdui-textfield">
                            <label class="mdui-textfield-label mdui-text-color-white">*封面url（最大1280*720）</label>
                            <input class="mdui-textfield-input mdui-text-color-white" name="pic" type="url" value="<?= $livePic ?>" />
                        </div>
                        <div class="mdui-textfield">
                            <label class="mdui-textfield-label mdui-text-color-white">直播源（推荐ipv4地址）</label>
                            <input class="mdui-textfield-input mdui-text-color-white" name="videoSource" type="url" value="<?= $liveVideoSource ?>" />
                        </div>
                        <div class="mdui-textfield">
                            <label class="mdui-textfield-label mdui-text-color-white">直播源类型</label>
                            <input class="mdui-textfield-input mdui-text-color-white" name="videoSourceType" type="text" value="<?= $liveVideoSourceType ?>" />
                        </div>
                        <div class="mdui-textfield">
                            <label class="mdui-textfield-label mdui-text-color-white">直播间自定义CSS样式不用写&lt;style&gt;&lt;/style&gt;</label>
                            <textarea class="mdui-textfield-input mdui-text-color-white" autocomplete="css" name="css" type="css" rows="15"><?= $liveCss ?></textarea>
                        </div>
                    </form>
                    <div class="mdui-dialog-actions">
                        <button class="mdui-btn mdui-ripple mdui-float-left" mdui-dialog-close id="delet-live">删除直播间</button>
                        <button class="mdui-btn mdui-ripple mdui-float-left" mdui-dialog-close>关闭</button>
                        <button class="mdui-btn mdui-btn-raised mdui-ripple" id="edit-live-btn">更新</button>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    </div>

    <div id="container">
        <div id="video-container">
            <div id="videoElement" controls></div>
            <div id="error-msg"></div>
            <div id="liveInfo">
                主播:
                <?php
                // 安全获取主播信息
                $broadcasterInfo = $userHelpers->getUserInfo(null, $liveData['user_id'] ?? null) ?? [];
                $broadcasterAvatar = $userHelpers->getAvatar($broadcasterInfo['email'] ?? '', 25);
                $broadcasterName = htmlspecialchars($broadcasterInfo['username'] ?? '未知用户');
                $liveDescription = htmlspecialchars($liveData['description'] ?? '');

                if (!empty($broadcasterAvatar)) {
                    echo '<img src="' . htmlspecialchars($broadcasterAvatar) . '" alt="主播头像">';
                }
                echo $broadcasterName . ' - ' . $liveDescription;
                ?>
            </div>
        </div>
        <div id="danmaku-container"></div>
    </div>

    <script src="/StaticResources/js/mdui.min.js"></script>
    <script src="/StaticResources/js/jquery.min.js"></script>
    <script src="/StaticResources/js/DPlayer.min.js"></script>
    <script src="/StaticResources/js/flv.min.js"></script>
    <script src="https://lf3-cdn-tos.bytecdntp.com/cdn/expire-1-M/hls.js/8.0.0-beta.3/hls.min.js" type="application/javascript"></script>
    <script src="/StaticResources/js/live.js?<?= FRAMEWORK_VERSION ?>"></script>
</body>

</html>
<?php
require_once FRAMEWORK_APP_PATH . '/Views/module/common.php';
