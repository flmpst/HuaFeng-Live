<?php

use ChatRoom\Core\Helpers\User;

$userHelpers = new User;
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>花枫 Live - 首页</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/mdui/1.0.2/css/mdui.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/normalize/8.0.1/normalize.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/StaticResources/css/index.css?<?= FRAMEWORK_VERSION ?>">
    <link rel="stylesheet" href="/StaticResources/css/common.css?<?= FRAMEWORK_VERSION ?>">
</head>

<body>
    <canvas id="canvas"></canvas>

    <div class="mdui-appbar mdui-appbar-fixed mdui-color-grey-800">
        <nav class="mdui-toolbar">
            <a>花枫 Live - 首页</a>
            <div class="mdui-toolbar-spacer"></div>
            <a href="https://dfggmc.top" target="_blank" class="mdui-btn">
                花枫官网 <i class="mdui-icon material-icons">link</i>
            </a>
            <?php
            if ($userHelpers->checkUserLoginStatus()) {
            ?>
                <button class="mdui-btn mdui-ripple" mdui-dialog-close mdui-dialog="{target: '#add-live'}">创建直播</button>
                <button class="mdui-btn mdui-ripple" mdui-dialog="{target: '#user-panel'}"><?= $userHelpers->getAvatar($userHelpers->getUserInfoByEnv()['email'], 35, 'mp', 'g', true) ?></button>
            <?php
            } else {
            ?>
                <button class="mdui-btn" mdui-dialog="{target: '#user'}">
                    登录/注册 <i class="mdui-icon material-icons">account_box</i>
                </button>
            <?php
            } ?>
        </nav>
    </div>

    <main class="mdui-container">
        <div id="list" class="mdui-row"></div>
    </main>

    <div class="mdui-container" id="searchContainer">
        <div class="mdui-container mdui-textfield mdui-textfield-expandable">
            <button class="mdui-textfield-icon mdui-btn mdui-btn-icon">
                <i class="mdui-icon material-icons">search</i>
            </button>
            <input class="mdui-textfield-input" type="text" id="search" placeholder="实时搜索……">
            <button class="mdui-textfield-close mdui-btn mdui-btn-icon">
                <i class="mdui-icon material-icons">close</i>
            </button>
        </div>
    </div>

    <div id="copy">
        V<?= FRAMEWORK_VERSION ?>
    </div>

    <script src="/StaticResources/js/mdui.min.js"></script>
    <script src="/StaticResources/js/jquery.min.js"></script>
    <script src="/StaticResources/js/backgroundScript.js"></script>
    <script src="/StaticResources/js/index.js?<?= FRAMEWORK_VERSION ?>"></script>
    <?php
    require_once FRAMEWORK_APP_PATH . '/Views/module/user.php'
    ?>
</body>

</html>