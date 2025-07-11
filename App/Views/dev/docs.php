<?php
function generateApiDocsFromMarkdown($markdownFile, $title)
{
    // 读取Markdown文件
    $markdownContent = file_get_contents($markdownFile);

    // 初始化Parsedown
    $parsedown = new Parsedown();
    $htmlContent = $parsedown->text($markdownContent);

    // 检查是否有iframe参数且值为true
    $hideUI = isset($_GET['iframe']) && strtolower($_GET['iframe']) === 'true';

    // 根据条件决定是否显示UI元素
    $drawerClass = $hideUI ? 'mdui-drawer-close' : 'mdui-drawer-open';
    $drawerStyle = $hideUI ? 'display: none;' : '';
    $appbarStyle = $hideUI ? 'display: none;' : '';
    $menuButtonStyle = $hideUI ? 'display: none;' : '';
    $bodyClass = $hideUI ? 'mdui-theme-primary-blue mdui-theme-accent-light-blue' : 'mdui-theme-primary-blue mdui-theme-accent-light-blue mdui-appbar-with-toolbar mdui-drawer-body-left';
    $title = $title;
    // 增强HTML结构，添加MDUI框架
    $fullHtml = <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>开发者中心 - $title</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/mdui/1.0.2/css/mdui.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/normalize/8.0.1/normalize.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://lf9-cdn-tos.bytecdntp.com/cdn/expire-1-M/highlight.js/11.4.0/styles/atom-one-dark-reasonable.min.css">
    <style>
        .api-endpoint {
            background-color: #f5f5f5;
            padding: 8px 12px;
            border-radius: 4px;
            font-family: monospace;
            margin: 10px 0;
        }
        .method-get { color: #61affe !important; }
        .method-post { color: #49cc90 !important; }
        .method-put { color: #fca130 !important; }
        .method-delete { color: #f93e3e !important; }
        pre {
            background-color: #f8f8f8;
            padding: 15px;
            border-radius: 4px;
        }
        .param-table {
            width: 100%;
        }
        .param-table th {
            text-align: left;
            background-color: #f5f5f5;
        }
        .section-title {
            margin-top: 30px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        /* 增强Markdown转换后的样式 */
        h1, h2, h3, h4 {
            color: #333;
        }

        .mdui-drawer {
            padding-left: 10px;
        }

        .mdui-collapse-item {
            width: 100%;
        }
        .mdui-collapse-item-body {
            padding-left: 16px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin: 15px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px 12px;
        }
        th {
            background-color: #f5f5f5;
        }
        code {
            background-color: #f5f5f5;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
        }
        
        /* 搜索框样式 */
        .search-container {
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .search-results-count {
            margin-top: 8px;
            font-size: 0.9em;
            color: #666;
        }
        .search-highlight {
            background-color: #ffeb3b;
            padding: 0 2px;
            border-radius: 2px;
        }

        .no-results {
            padding: 20px;
            text-align: center;
            color: #757575;
        }

        /* 添加内容区域上边距，当appbar隐藏时 */
        body:not(.mdui-appbar-with-toolbar) #content-container {
            margin-top: 20px;
        }
    </style>
</head>
<body class="{$bodyClass}">
    <div class="mdui-appbar mdui-appbar-fixed mdui-color-grey-800" style="{$appbarStyle}">
        <div class="mdui-toolbar">
            <a href="javascript:;" class="mdui-btn mdui-btn-icon" mdui-drawer="{target: '#drawer'}" style="{$menuButtonStyle}">
                <i class="mdui-icon material-icons">menu</i>
            </a>
            <span class="mdui-typo-title">花枫Live API 文档</span>
            <div class="mdui-toolbar-spacer"></div>
            <a href="/dev" class="mdui-btn">
                返回开发者中心
            </a>
        </div>
    </div>

    <!-- 侧边导航 -->
    <div class="mdui-drawer {$drawerClass}" id="drawer" style="{$drawerStyle}">
        <!-- 搜索框 -->
        <div class="search-container" id="search-box">
            <div class="mdui-textfield mdui-textfield-floating-label">
                <label class="mdui-textfield-label">搜索API文档...</label>
                <input class="mdui-textfield-input" type="text" id="search-input"/>
            </div>
            <div class="search-results-count" id="search-results-count"></div>
        </div>
        
        <div class="mdui-list" id="toc-list">
            <!-- 目录将通过JavaScript动态生成 -->
        </div>
    </div>

    <div id="iframe-search-box" class="mdui-container mdui-typo"></div>
    <div class="mdui-container mdui-typo" id="content-container">
        $htmlContent
    </div>

    <script src="https://lf3-cdn-tos.bytecdntp.com/cdn/expire-1-M/highlight.js/11.4.0/highlight.min.js"></script>
    <script src="https://lf3-cdn-tos.bytecdntp.com/cdn/expire-1-M/mdui/1.0.2/js/mdui.min.js"></script>
    <script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-1-M/jquery/3.6.0/jquery.min.js"></script>
    <script src="/StaticResources/js/doc.js"></script>
    <script>
        // 检查是否有iframe参数且值为true
        const urlParams = new URLSearchParams(window.location.search);
        const iframeMode = urlParams.get('iframe') === 'true';
        
        if (iframeMode) {
            // 获取搜索框和内容容器
            const searchBox = document.getElementById('search-box');
            const contentContainer = document.getElementById('iframe-search-box');
            
            if (searchBox && contentContainer) {
                // 移动搜索框到内容容器顶部（直接移动而非克隆）
                contentContainer.insertBefore(searchBox, contentContainer.firstChild);
                
                // 调整样式以适应新的位置
                searchBox.style.position = 'static';
                searchBox.style.marginBottom = '20px';
            }
        }
    </script>
</body>
</html>
HTML;

    return $fullHtml;
}

$markdownFile = FRAMEWORK_DIR . '/StaticResources/markdown/docs.api.v1.md';
if (file_exists($markdownFile)) {
    $html = generateApiDocsFromMarkdown($markdownFile, $title);
    echo $html;
} else {
    echo "文档文件不存在";
}
?>