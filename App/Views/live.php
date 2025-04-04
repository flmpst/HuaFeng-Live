<?php

use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Controller\Live;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$liveId = isset(explode('/', trim($uri, '/'))[0]) ? explode('/', trim($uri, '/'))[0] : null;
$live = new Live;
$liveData = $live->get($liveId);
$userHelpers = new User;
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimal-ui">
    <link href="https://cdn.bootcdn.net/ajax/libs/mdui/1.0.2/css/mdui.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/normalize/8.0.1/normalize.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
    <link rel="stylesheet" href="/StaticResources/css/live.css?<?= FRAMEWORK_VERSION ?>">
    <link rel="stylesheet" href="/StaticResources/css/common.css?<?= FRAMEWORK_VERSION ?>">
    <title><?= $liveData['name']; ?> - DFGG LIVE</title>
    <meta name="description" content="<?= $liveData['description']; ?>">
    <style>
        <?= $liveData['css'] ?>
    </style>
</head>

<body>
    <div class="mdui-appbar mdui-appbar-scroll-hide">
        <div class="mdui-toolbar mdui-color-theme">
            <h1 class="mdui-typo-headline">
                <?= $liveData['name'] ?? '直播间不存在'; ?>
                <small>
                    <span id="online-users-list-count"></span>个人正在看
                </small>
            </h1>
            <div class="mdui-toolbar-spacer"></div>
            <a class="mdui-typo" href="/">返回首页</a>
            <?php
            if ($userHelpers->getUserInfoByEnv()['group_id'] === 1 || $liveData['user_id'] === $userHelpers->getUserInfoByEnv()['user_id']) {
            ?>
                <button class="mdui-btn mdui-ripple mdui-btn-icon" mdui-dialog="{target: '#edit-live'}"><i class="mdui-icon material-icons">settings</i></button>
                <div class="mdui-dialog custom-dialog" id="edit-live">
                    <div class="mdui-dialog-title mdui-color-grey-800">
                        修改直播间信息 <span id="edit-live-msg" class="mdui-color-red"></span>
                    </div>
                    <form class="mdui-dialog-content" id="edit-live-form">
                        <div class="mdui-textfield">
                            <label class="mdui-textfield-label mdui-text-color-white">直播间名称</label>
                            <input class="mdui-textfield-input mdui-text-color-white" name="name" type="text" value="<?= $liveData['name'] ?>" />
                        </div>
                        <div class="mdui-textfield">
                            <label class="mdui-textfield-label mdui-text-color-white">描述</label>
                            <textarea class="mdui-textfield-input mdui-text-color-white" name="description" type="text"><?= $liveData['description'] ?></textarea>
                        </div>
                        <div class="mdui-textfield">
                            <label class="mdui-textfield-label mdui-text-color-white">*封面url（最大1280*720）</label>
                            <input class="mdui-textfield-input mdui-text-color-white" name="pic" type="url" value="<?= $liveData['pic'] ?>" />
                        </div>
                        <div class="mdui-textfield">
                            <label class="mdui-textfield-label mdui-text-color-white">直播源（推荐ipv4地址）</label>
                            <input class="mdui-textfield-input mdui-text-color-white" name="videoSource" type="url" value="<?= $liveData['video_source'] ?>" />
                        </div>
                        <div class="mdui-textfield">
                            <label class="mdui-textfield-label mdui-text-color-white">直播源类型</label>
                            <input class="mdui-textfield-input mdui-text-color-white" name="videoSourceType" type="text" value="<?= $liveData['video_source_type'] ?>" />
                        </div>
                        <div class="mdui-textfield">
                            <label class="mdui-textfield-label mdui-text-color-white">直播间自定义CSS样式不用写&lt;style&gt;&lt;/style&gt;</label>
                            <textarea class="mdui-textfield-input mdui-text-color-white" autocomplete="css" name="css" type="css" rows="15"><?= $liveData['css'] ?></textarea>
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
            <video id="videoElement" controls></video>
            <div id="error-msg"></div>
        </div>
        <div id="danmaku-container">
            <div id="liveInfo">主播: <img src="<?= $userHelpers->getAvatar($userHelpers->getUserInfo(null, $liveData['user_id'])['email'], 25) ?>" alt=""><?= $userHelpers->getUserInfo(null, $liveData['user_id'])['username'] ?> - <?= $liveData['description'] ?></div>
        </div>
    </div>

    <div id="danmaku-input-container" class="mdui-textfield">
        <?php
        if ($userHelpers->checkUserLoginStatus()) {
        ?>
            <input class="mdui-textfield-input mdui-text-color-white-text" type="text" id="danmaku-input" placeholder="输入弹幕内容">
            <button id="sendDanmakuBtn" class="mdui-btn mdui-btn-raised">发送</button>
        <?php
        } else {
        ?>
            <span style="font-style: italic;">返回首页登录即可互动</span>
        <?php
        }
        ?>
    </div>

    <script src="/StaticResources/js/mdui.min.js"></script>
    <script src="/StaticResources/js/jquery.min.js"></script>
    <script src="/StaticResources/js/plyr.js"></script>
    <script src="/StaticResources/js/flv.min.js"></script>
    <script src="https://lf3-cdn-tos.bytecdntp.com/cdn/expire-1-M/hls.js/8.0.0-beta.3/hls.min.js" type="application/javascript"></script>
    <script src="/StaticResources/js/live.js?<?= FRAMEWORK_VERSION ?>"></script>
    <script>
        const liveData = {
            videoSource: "<?= $liveData['video_source'] ?>",
            videoSourceType: "<?= $liveData['video_source_type'] ?>"
        };
        initializePlayer(liveData)
    </script>
</body>

</html>
<?php
require_once FRAMEWORK_APP_PATH . '/Views/module/common.php';
