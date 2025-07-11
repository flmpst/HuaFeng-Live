<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>开发者中心 - <?= $title ?></title>
    <link href="https://cdn.bootcdn.net/ajax/libs/mdui/1.0.2/css/mdui.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/normalize/8.0.1/normalize.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/highlight.js/11.7.0/styles/github.min.css">
    <link rel="stylesheet" href="/StaticResources/css/common.css?<?= FRAMEWORK_VERSION ?>">
    <script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-1-M/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://lf3-cdn-tos.bytecdntp.com/cdn/expire-1-M/highlight.js/11.4.0/highlight.min.js"></script>
    <script src="/StaticResources/js/jquery.draggable-iframe.js"></script>
    <style>
        main {
            padding-bottom: 100px;
        }

        .api-example {
            cursor: pointer;
            color: #2196F3;
        }

        .api-example:hover {
            text-decoration: underline;
        }

        .response-container {
            max-height: 500px;
            overflow-y: auto;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        pre {
            margin: 0;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 4px;
        }

        #loading-spinner {
            display: none;
            margin-left: 10px;
        }
    </style>
</head>

<body class="mdui-theme-primary-blue mdui-theme-accent-light-blue mdui-appbar-with-toolbar mdui-drawer-body-left">
    <div class="mdui-appbar mdui-appbar-fixed mdui-color-grey-800">
        <nav class="mdui-toolbar">
            <a href="javascript:;" class="mdui-btn mdui-btn-icon" mdui-drawer="{target: '#main-drawer'}">
                <i class="mdui-icon material-icons">menu</i>
            </a>
            <a>花枫 Live - <?= $title ?></a>
            <div class="mdui-toolbar-spacer"></div>
            <a href="javascript:;" class="mdui-btn mdui-btn-icon" onclick="window.location.href='/'"><i class="mdui-icon material-icons">home</i></a>
            <a href="https://dfggmc.top" target="_blank" class="mdui-btn">
                花枫官网 <i class="mdui-icon material-icons">link</i>
            </a>
            <?php if ($userHelpers->checkUserLoginStatus()): ?>
                <button class="mdui-btn mdui-ripple"><?= $userHelpers->getAvatar($userHelpers->getUserInfoByEnv()['email'], 35, 'mp', 'g', true, ['style' => 'height:40px;']) ?></button>
            <?php else: ?>
                <button class="mdui-btn">当前为未登录</button>
            <?php endif; ?>
        </nav>
    </div>
    <!-- 侧边栏 -->
    <div class="mdui-drawer" id="main-drawer">
        <div class="mdui-list">
            <div class="mdui-subheader">开发者工具</div>
            <a href="/dev/index" class="mdui-list-item mdui-ripple active">
                <i class="mdui-list-item-icon mdui-icon material-icons">vpn_key</i>
                <div class="mdui-list-item-content">API密钥管理</div>
            </a>
            <a href="/dev/sandbox" class="mdui-list-item mdui-ripple">
                <i class="mdui-list-item-icon mdui-icon material-icons">code</i>
                <div class="mdui-list-item-content">API沙盒</div>
            </a>
            <a href="/dev/application" class="mdui-list-item mdui-ripple">
                <i class="mdui-list-item-icon mdui-icon material-icons">apps</i>
                <div class="mdui-list-item-content">第三方应用接入</div>
            </a>
            <a href="/dev/docs" target="_blank" rel="noopener noreferrer" class="mdui-list-item mdui-ripple">
                <i class="mdui-list-item-icon mdui-icon material-icons">library_books</i>
                <div class="mdui-list-item-content">API文档 <i class="mdui-icon material-icons">open_in_new</i></div>
            </a>
            <div class="mdui-subheader">其他资源</div>
            <a href="https://github.com/flmpst/HuaFeng-Live" target="_blank" class="mdui-list-item mdui-ripple">
                <i class="mdui-list-item-icon mdui-icon material-icons">code</i>
                <div class="mdui-list-item-content">GitHub仓库</div>
            </a>
            <a href="https://dfggmc.top" target="_blank" class="mdui-list-item mdui-ripple">
                <i class="mdui-list-item-icon mdui-icon material-icons">public</i>
                <div class="mdui-list-item-content">花枫官网</div>
            </a>
        </div>
    </div>
    <main class="mdui-container mdui-typo">