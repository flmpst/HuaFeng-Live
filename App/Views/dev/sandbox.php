<?php

use HuaFengLive\Helpers\UserHelpers;
use HuaFengLive\Modules\TokenModules;
use XQPF\Core\Helpers\Helpers;

$helpers = new Helpers;
$userHelpers = new UserHelpers;
$tokenManager = new TokenModules;

// 获取用户信息
$userInfo = $userHelpers->getUserInfoByEnv();
$userId = $userInfo['user_id'];

// 获取用户API Token
$tokens = $tokenManager->getTokens($userId);
$apiToken = !empty($tokens) ? $tokens[0]['token'] : '';

$this->loadView('/dev/head', [
    'title' => $title,
    'userHelpers' => $userHelpers,
    'tokenManager' => $tokenManager
]);
?>
<h1>API沙盒</h1>
<hr>
<div class="mdui-card">
    <div class="mdui-card-header mdui-color-grey-800">
        <div class="mdui-card-primary-title">API测试工具 <button id="openDocsBtn" class="mdui-btn mdui-btn-raised mdui-ripple mdui-color-theme"><i class="mdui-icon material-icons">open_with</i> 打开开发文档</button></div>
    </div>
    <div class="mdui-card-content">
        <form method="post" id="api-test-form">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="mdui-row">
                <div class="mdui-col-xs-2">
                    <select class="mdui-select" name="api_method" mdui-select>
                        <option value="GET" selected>GET</option>
                        <option value="POST">POST</option>
                        <option value="PUT">PUT</option>
                        <option value="DELETE">DELETE</option>
                    </select>
                </div>
                <div class="mdui-col-xs-10">
                    <div class="mdui-textfield mdui-textfield-floating-label">
                        <label class="mdui-textfield-label">API端点</label>
                        <input class="mdui-textfield-input" type="text" name="api_endpoint" value="/live/list" required />
                        <div class="mdui-textfield-helper">例如: /live/list, /live/get?live_id=1</div>
                    </div>
                </div>
            </div>

            <div class="mdui-panel" mdui-panel>
                <div class="mdui-panel-item">
                    <div class="mdui-panel-item-header">
                        <div class="mdui-panel-item-title">请求参数</div>
                        <i class="mdui-panel-item-arrow mdui-icon material-icons">keyboard_arrow_down</i>
                    </div>
                    <div class="mdui-panel-item-body">
                        <div id="request-params-container">
                            <div class="mdui-row param-row">
                                <div class="mdui-col-xs-5">
                                    <div class="mdui-textfield">
                                        <input class="mdui-textfield-input" type="text" name="request_params[key][]" placeholder="参数名" />
                                    </div>
                                </div>
                                <div class="mdui-col-xs-5">
                                    <div class="mdui-textfield">
                                        <input class="mdui-textfield-input" type="text" name="request_params[value][]" placeholder="参数值" />
                                    </div>
                                </div>
                                <div class="mdui-col-xs-2">
                                    <button type="button" class="mdui-btn mdui-btn-icon mdui-ripple remove-param-btn" disabled>
                                        <i class="mdui-icon material-icons">remove</i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="mdui-btn mdui-btn-raised mdui-ripple" id="add-param-btn">
                            <i class="mdui-icon material-icons">add</i> 添加参数
                        </button>
                    </div>
                </div>
            </div>

            <div class="mdui-row mdui-m-t-2">
                <div class="mdui-col-xs-12">
                    <label class="mdui-switch">
                        <input type="checkbox" name="use_token_in_header" checked />
                        <i class="mdui-switch-icon"></i>
                        使用Authorization头传递Token
                    </label>
                </div>
            </div>

            <div class="mdui-card-actions">
                <button type="submit" class="mdui-btn mdui-btn-raised mdui-ripple mdui-color-theme-accent">
                    <i class="mdui-icon material-icons">send</i> 发送请求
                    <div id="loading-spinner" class="mdui-spinner mdui-spinner-colorful"></div>
                </button>
                <button type="button" class="mdui-btn mdui-btn-raised mdui-ripple" id="reset-form-btn">
                    <i class="mdui-icon material-icons">refresh</i> 重置表单
                </button>
            </div>
        </form>
    </div>
</div>

<!-- API示例 -->
<div class="mdui-card mdui-m-t-4">
    <div class="mdui-card-header">
        <div class="mdui-card-primary-title">API示例</div>
        <div class="mdui-card-secondary-title">点击示例快速填充表单</div>
    </div>
    <div class="mdui-card-content">
        <ul class="mdui-list">
            <li class="mdui-list-item mdui-ripple api-example" data-method="GET" data-endpoint="/live/list">
                <i class="mdui-list-item-icon mdui-icon material-icons">live_tv</i>
                <div class="mdui-list-item-content">获取直播列表</div>
            </li>
            <li class="mdui-list-item mdui-ripple api-example" data-method="GET" data-endpoint="/live/get" data-params='{"live_id": 1}'>
                <i class="mdui-list-item-icon mdui-icon material-icons">info</i>
                <div class="mdui-list-item-content">获取指定直播间信息</div>
            </li>
            <li class="mdui-list-item mdui-ripple api-example" data-method="POST" data-endpoint="/live/create" data-params='{"name": "测试直播间", "description": "这是一个测试直播间", "videoSource": "https://example.com/stream.m3u8", "videoSourceType": "m3u8"}'>
                <i class="mdui-list-item-icon mdui-icon material-icons">add_circle</i>
                <div class="mdui-list-item-content">创建直播间</div>
            </li>
            <li class="mdui-list-item mdui-ripple api-example" data-method="GET" data-endpoint="/chat/get" data-params='{"room_id": 1}'>
                <i class="mdui-list-item-icon mdui-icon material-icons">chat</i>
                <div class="mdui-list-item-content">获取聊天消息</div>
            </li>
            <li class="mdui-list-item mdui-ripple api-example" data-method="POST" data-endpoint="/chat/send" data-params='{"room_id": 1, "message": "你好，花枫Live！"}'>
                <i class="mdui-list-item-icon mdui-icon material-icons">send</i>
                <div class="mdui-list-item-content">发送聊天消息</div>
            </li>
        </ul>
    </div>
</div>

<!-- API响应 -->
<div class="mdui-card mdui-m-t-4" id="api-response-container" style="display: none;">
    <div class="mdui-card-header">
        <div class="mdui-card-primary-title">API响应</div>
        <div class="mdui-card-secondary-title">HTTP状态码: <span id="response-status"></span></div>
    </div>
    <div class="mdui-card-content">
        <div class="mdui-tab mdui-tab-scrollable">
            <a href="#response-body-tab" class="mdui-ripple mdui-tab-active">响应体</a>
        </div>

        <div class="response-container">
            <div id="response-body-tab">
                <pre><code class="language-json" id="response-body"></code></pre>
            </div>
        </div>
    </div>
</div>
</main>
<div id="draggableIframeTemplate" class="draggable-iframe-container" style="display: none;">
    <div class="draggable-iframe-header">
        <span class="iframe-title">API DOCS</span>
        <span class="close-btn mdui-btn-icon">
            <i class="mdui-icon material-icons">close</i>
        </span>
    </div>
    <iframe class="draggable-iframe-content" src="" frameborder="0"></iframe>
</div>
<div id="docsContainer"></div>
<script>
    $(document).ready(function() {
        // 初始化代码高亮
        hljs.highlightAll();

        // 侧边栏切换
        var drawer = new mdui.Drawer('#main-drawer', {
            swipe: true
        });

        // 添加参数行
        $('#add-param-btn').click(function() {
            const newRow = `
                    <div class="mdui-row param-row">
                        <div class="mdui-col-xs-5">
                            <div class="mdui-textfield">
                                <input class="mdui-textfield-input" type="text" name="request_params[key][]" placeholder="参数名"/>
                            </div>
                        </div>
                        <div class="mdui-col-xs-5">
                            <div class="mdui-textfield">
                                <input class="mdui-textfield-input" type="text" name="request_params[value][]" placeholder="参数值"/>
                            </div>
                        </div>
                        <div class="mdui-col-xs-2">
                            <button type="button" class="mdui-btn mdui-btn-icon mdui-ripple remove-param-btn">
                                <i class="mdui-icon material-icons">remove</i>
                            </button>
                        </div>
                    </div>
                `;
            $('#request-params-container').append(newRow);
            updateRemoveButtons();
        });

        // 更新删除按钮状态
        function updateRemoveButtons() {
            const $rows = $('.param-row');
            $rows.each(function(index) {
                const $btn = $(this).find('.remove-param-btn');
                if ($rows.length === 1) {
                    $btn.prop('disabled', true);
                } else {
                    $btn.prop('disabled', false);
                }
            });
        }

        // 删除参数行
        $(document).on('click', '.remove-param-btn', function() {
            if ($('.param-row').length > 1) {
                $(this).closest('.param-row').remove();
                updateRemoveButtons();
            }
        });

        // 重置表单
        $('#reset-form-btn').click(function() {
            $('#api-test-form')[0].reset();
            $('#request-params-container').html(`
                    <div class="mdui-row param-row">
                        <div class="mdui-col-xs-5">
                            <div class="mdui-textfield">
                                <input class="mdui-textfield-input" type="text" name="request_params[key][]" placeholder="参数名"/>
                            </div>
                        </div>
                        <div class="mdui-col-xs-5">
                            <div class="mdui-textfield">
                                <input class="mdui-textfield-input" type="text" name="request_params[value][]" placeholder="参数值"/>
                            </div>
                        </div>
                        <div class="mdui-col-xs-2">
                            <button type="button" class="mdui-btn mdui-btn-icon mdui-ripple remove-param-btn" disabled>
                                <i class="mdui-icon material-icons">remove</i>
                            </button>
                        </div>
                    </div>
                `);
        });

        // API示例点击事件
        $('.api-example').click(function() {
            const method = $(this).data('method');
            const endpoint = $(this).data('endpoint');
            const params = $(this).data('params') || {};

            // 设置方法
            $('select[name="api_method"]').val(method).trigger('change');

            // 设置端点
            $('input[name="api_endpoint"]').val(endpoint);

            // 清空现有参数
            $('#request-params-container').empty();

            // 添加新参数
            Object.keys(params).forEach(key => {
                const value = params[key];
                $('#request-params-container').append(`
                        <div class="mdui-row param-row">
                            <div class="mdui-col-xs-5">
                                <div class="mdui-textfield">
                                    <input class="mdui-textfield-input" type="text" name="request_params[key][]" placeholder="参数名" value="${key}"/>
                                </div>
                            </div>
                            <div class="mdui-col-xs-5">
                                <div class="mdui-textfield">
                                    <input class="mdui-textfield-input" type="text" name="request_params[value][]" placeholder="参数值" value="${value}"/>
                                </div>
                            </div>
                            <div class="mdui-col-xs-2">
                                <button type="button" class="mdui-btn mdui-btn-icon mdui-ripple remove-param-btn">
                                    <i class="mdui-icon material-icons">remove</i>
                                </button>
                            </div>
                        </div>
                    `);
            });

            // 如果没有参数，添加一个空行
            if (Object.keys(params).length === 0) {
                $('#request-params-container').append(`
                        <div class="mdui-row param-row">
                            <div class="mdui-col-xs-5">
                                <div class="mdui-textfield">
                                    <input class="mdui-textfield-input" type="text" name="request_params[key][]" placeholder="参数名"/>
                                </div>
                            </div>
                            <div class="mdui-col-xs-5">
                                <div class="mdui-textfield">
                                    <input class="mdui-textfield-input" type="text" name="request_params[value][]" placeholder="参数值"/>
                                </div>
                            </div>
                            <div class="mdui-col-xs-2">
                                <button type="button" class="mdui-btn mdui-btn-icon mdui-ripple remove-param-btn" disabled>
                                    <i class="mdui-icon material-icons">remove</i>
                                </button>
                            </div>
                        </div>
                    `);
            }

            updateRemoveButtons();

            // 滚动到表单顶部
            $('html, body').animate({
                scrollTop: $('#api-test-form').offset().top - 20
            }, 300);
        });

        // 标签页切换
        $('.mdui-tab a').click(function(e) {
            e.preventDefault();
            const target = $(this).attr('href');

            $('.mdui-tab a').removeClass('mdui-tab-active');
            $(this).addClass('mdui-tab-active');

            $('.tab-content').removeClass('active');
            $(target).addClass('active');
        });

        // 处理表单提交
        $('#api-test-form').submit(function(e) {
            e.preventDefault();

            // 显示加载动画
            $('#loading-spinner').show();

            // 收集表单数据
            const formData = {
                csrf_token: $('input[name="csrf_token"]').val(),
                api_method: $('select[name="api_method"]').val(),
                api_endpoint: $('input[name="api_endpoint"]').val(),
                use_token_in_header: $('input[name="use_token_in_header"]').is(':checked'),
                request_params: {}
            };

            // 收集请求参数
            $('input[name="request_params[key][]"]').each(function(index) {
                const key = $(this).val();
                const value = $('input[name="request_params[value][]"]').eq(index).val();
                if (key) {
                    formData.request_params[key] = value;
                }
            });

            // 发送AJAX请求
            $.ajax({
                url: `/api/v1${$('input[name="api_endpoint"]').val()}`,
                type: formData.api_method,
                data: formData.api_method === 'GET' ? formData.request_params : JSON.stringify(formData.request_params),
                contentType: formData.api_method === 'GET' ? 'application/x-www-form-urlencoded' : 'application/json',
                headers: formData.use_token_in_header ? {
                    'Authorization': `Bearer ${'<?= $apiToken ?>'}`
                } : {},
                dataType: 'json',
                success: function(response, status, xhr) {
                    // 隐藏加载动画
                    $('#loading-spinner').hide();

                    // 显示响应容器
                    $('#api-response-container').show();

                    // 填充响应数据
                    console.table(response);
                    $('#response-status').text(xhr.status);
                    console.debug(xhr);
                    // 格式化JSON响应体
                    if (typeof response === 'object') {
                        $('#response-body').text(JSON.stringify(response, null, 2));
                    } else {
                        $('#response-body').text(response);
                    }

                    // 重新高亮代码
                    hljs.highlightAll();

                    // 滚动到响应区域
                    $('html, body').animate({
                        scrollTop: $('#api-response-container').offset().top - 20
                    }, 300);
                },
                error: function(xhr, status, error) {
                    // 隐藏加载动画
                    $('#loading-spinner').hide();

                    // 显示错误信息
                    mdui.snackbar({
                        message: '请求失败: ' + error,
                        position: 'right-top'
                    });
                }
            });
        });
    });
    $(document).ready(function() {
        // 打开开发文档
        $('#openDocsBtn').click(function() {
            $('#docsContainer').draggableIframe({
                url: '/dev/docs?iframe=true',
                title: '开发文档',
                mobileFullscreen: true, // 在移动设备上全屏显示
                mobileBreakpoint: 768, // 移动设备断点
                width: 350,
                height: 500,
                x: 50,
                y: 50
            }).open();
        });
    });
</script>
<?php
$this->loadView('/module/footer', [
    'title' => $title,
    'userHelpers' => $userHelpers,
    'tokenManager' => $tokenManager
]);
?>
</body>

</html>