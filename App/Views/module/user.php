<?php
// 判断是否进行客户端登录 method=clientAuth&clientid=xxxxx

$method = 'default';
$clientid = '';

use ChatRoom\Core\Modules\TokenManager;

if (isset($_GET['method']) && $_GET['method'] === 'clientAuth' && isset($_GET['clientid'])) {
    $method = 'clientAuth';
    $clientid = $_GET['clientid'] ?? null;
}
if (!$userHelpers->checkUserLoginStatus()) {
?>
    <div class="mdui-dialog custom-dialog" id="user">
        <div class="mdui-dialog-title mdui-color-grey-800">
            登录/注册<span id="user-auth-msg"></span>
        </div>
        <form class="mdui-dialog-content" id="user-form">
            <div id="captcha" style="left: 60%;top: 10px;position: absolute;"></div>
            <div class="mdui-textfield mdui-textfield-floating-label mdui-text-color-white">
                <label class="mdui-textfield-label mdui-text-color-white">Email</label>
                <input class="mdui-textfield-input mdui-text-color-white" autocomplete="email" name="email" type="email" />
            </div>
            <div class="mdui-textfield mdui-textfield-floating-label">
                <label class="mdui-textfield-label mdui-text-color-white">密码</label>
                <input class="mdui-textfield-input mdui-text-color-white" autocomplete="current-password" name="password" type="password" />
            </div>
        </form>
        <div class="mdui-dialog-actions">
            <a href="https://live.dfggmc.top/disclaimer.html" target="_blank" rel="noopener noreferrer" class="mdui-btn mdui-ripple mdui-typo">继续填写则同意《免责声明》</a>
            <button class="mdui-btn mdui-ripple" mdui-dialog-close>关闭</button>
            <button class="mdui-btn mdui-btn-raised mdui-ripple" id="user-auth-btn" style="display: none;">登录/注册</button>
        </div>
    </div>
    <script src="https://static.geetest.com/v4/gt4.js"></script>
    <script>
        initGeetest4({
            product: 'popup',
            captchaId: '<?= $this->appConfig->geetest['captchaId'] ?>'
        }, function(captcha) {
            captcha.appendTo("#captcha");
            captcha.onSuccess(function() {
                const result = captcha.getValidate();
                $.ajax({
                    url: `/api/v1/user/captcha`,
                    data: result,
                    method: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.code === 200) {
                            const token = response.data.token;
                            $('#user-auth-btn').css('display', 'inline').off('click').on('click', function() {
                                $(this).prop('disabled', true);
                                $.ajax({
                                    type: "POST",
                                    url: "/api/v1/user/auth",
                                    data: $('#user-form').serialize() + "&captcha_token=" + token,
                                    dataType: "JSON",
                                    success: function(response) {
                                        $('#user-auth-msg').html(`<span class="mdui-color-red-a700">${response.message}</span>`);
                                        if (response.code === 200) {
                                            location.href = '/?method=<?= $method ?>&clientid=<?= $clientid ?>';
                                        } else if (response.code === 401) {
                                            captcha.reset();
                                        }
                                        $('#user-auth-btn').prop('disabled', false);
                                    },
                                    error: function(xhr) {
                                        $('#user-auth-msg').html(`<span class="mdui-color-red-a700">${xhr.status}</span>`);
                                        $('#user-auth-btn').prop('disabled', false);
                                    }
                                });
                            });
                        }
                    }
                });
            });
        });
    </script>
<?php
}
?>
<?php
if ($userHelpers->checkUserLoginStatus()) {
?>
    <div class="mdui-dialog custom-dialog" id="user-panel">
        <div class="mdui-dialog-title mdui-color-grey-800" style="justify-items: center;">
            <?= $userHelpers->getAvatar($userHelpers->getUserInfoByEnv()['email'], 80, 'mp', 'g', true) ?>
            <p>
                个人设置面板(留空为不更改 | 更改密码尚未实现)
            </p>
            <span id="user-panel-msg"></span>
        </div>
        <form class="mdui-dialog-content" id="user-form">
            <div class="mdui-textfield mdui-textfield-floating-label mdui-text-color-white">
                <label class="mdui-textfield-label mdui-text-color-white">用户名</label>
                <input class="mdui-textfield-input mdui-text-color-white" autocomplete="username" name="username" type="text" value="<?= $userHelpers->getUserInfoByEnv()['username'] ?>" />
            </div>
            <div class="mdui-textfield mdui-textfield-floating-label">
                <label class="mdui-textfield-label mdui-text-color-white">旧密码</label>
                <input class="mdui-textfield-input mdui-text-color-white" autocomplete="current-password" name="password" type="password" />
            </div>
            <div class="mdui-textfield mdui-textfield-floating-label">
                <label class="mdui-textfield-label mdui-text-color-white">新密码</label>
                <input class="mdui-textfield-input mdui-text-color-white" autocomplete="new-password" name="newPassword" type="password" />
            </div>
        </form>
        <div class="mdui-dialog-actions">
            <button class="mdui-btn mdui-ripple mdui-float-left" mdui-dialog-close>关闭</button>
            <button class="mdui-btn mdui-ripple mdui-float-left" id="user-panel-logout-btn">退出登录</button>
            <button class="mdui-btn mdui-btn-raised mdui-ripple" mdui-dialog-close mdui-dialog="{target: '#user-openapi'}">开发者选项</button>
            <button class="mdui-btn mdui-btn-raised mdui-ripple" id="user-panel-btn">确定</button>
        </div>
    </div>
    <div class="mdui-dialog custom-dialog" id="user-openapi">
        <div class="mdui-dialog-title mdui-color-grey-800">
            <p>
                开发者选项
            </p>
        </div>
        <div class="mdui-dialog-content mdui-typo">
            <p class="mdui-text-color-white">
                API TOKENS
            </p>
            <hr>
            <p class="mdui-text-color-red-accent">
                警告：请务必妥善保管您的API密钥，拥有密钥等于拥有您的账号操作的一切权限， 如自行泄露密钥导致损失花枫工作室将不承担任何责任。
            </p>
            <div class="" id="api-token-list">
                <?php
                $tokenManager = new TokenManager;
                $userId = $userHelpers->getUserInfoByEnv()['user_id'];
                $tokens = $tokenManager->getTokens($userId);
                if (!empty($tokens)) {
                    foreach ($tokens as $token) {
                        // 处理extra数据
                        $extraDisplay = '无';
                        if (!empty($token['extra'])) {
                            try {
                                $unserialized = @unserialize($token['extra']);
                                if ($unserialized !== false) {
                                    $extraDisplay = json_encode($unserialized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                } else {
                                    $extraDisplay = htmlspecialchars($token['extra']);
                                }
                            } catch (Exception $e) {
                                $extraDisplay = '数据解析错误';
                            }
                        }
                        echo sprintf(
                            '<li class="mdui-list-item mdui-ripple token-item">
							            <div class="mdui-list-item-content">
							                <div class="mdui-list-item-title mdui-text-color-blue">
							                    <i class="mdui-icon material-icons">%s</i> %s
							                </div>
							               <div class="mdui-list-item-text mdui-text-color-white-secondary">
									      		类型: %s
									       </div>
									       <div class="mdui-list-item-text mdui-text-color-white-secondary extra-data" style="display:none">
							                    <i class="mdui-icon material-icons">info</i> 额外数据:
							                    <div class="mdui-typo">
							                    	<pre class="mdui-m-t-1">%s</pre>
							                    </div>
							                </div>
							            </div>
							            <div class="token-actions">
							                <button class="mdui-btn mdui-btn-icon mdui-ripple copy-token-btn" title="复制Token" data-token="%s">
							                    <i class="mdui-icon material-icons">content_copy</i>
							                </button>
							                <button class="mdui-btn mdui-btn-icon mdui-ripple toggle-extra-btn" title="显示额外数据">
							                    <i class="mdui-icon material-icons">expand_more</i>
							                </button>
							            </div>
							        </li>',
                            $tokenManager->getTokenIcon($token['type']), // 根据类型返回不同图标
                            htmlspecialchars($token['created_at'], ENT_QUOTES, 'UTF-8'),
                            $token['type'],
                            $extraDisplay,
                            htmlspecialchars($token['token'], ENT_QUOTES, 'UTF-8')
                        );
                    }
                } else {
                    echo '<li class="mdui-list-item mdui-text-center">没有API密钥</li>';
                }
                ?>
            </div>
        </div>
        <div class="mdui-dialog-actions">
            <button class="mdui-btn mdui-ripple mdui-float-left" id="user-openapi-btn">删除所有TOKEN</button>
            <button class="mdui-btn mdui-ripple mdui-float-left" id="user-openapi-new-btn">生成新的API TOKEN</button>
            <button class="mdui-btn mdui-btn-raised mdui-ripple" mdui-dialog-close mdui-dialog="{target: '#user-panel'}">返回设置面板</button>
        </div>
    </div>
    <?php
    if ($method === 'clientAuth') {
    ?>
        <div class="mdui-dialog custom-dialog" id="client-auth">
            <div class="mdui-dialog-title mdui-color-grey-800">
                是否授权客户端？<span id="client-auth-msg"></span>
            </div>
            <form class="mdui-dialog-content mdui-typo" id="client-auth-form">
                <div class="mdui-textfield mdui-textfield-floating-label mdui-text-color-white">
                    <div class="mdui-col mdui-col-xs-2">
                        <p>
                            客户端ID：
                        </p>
                    </div>
                    <div class="mdui-col mdui-col-xs-10">
                        <pre id="api-token-display" class="mdui-text-color-white"><?= $clientid ?></pre>
                    </div>
                </div>
            </form>
            <div class="mdui-dialog-actions">
                <button class="mdui-btn mdui-ripple" mdui-dialog-close id="client-auth-close">关闭</button>
                <button class="mdui-btn mdui-btn-raised mdui-ripple" id="client-auth-btn">授权</button>
            </div>
        </div>
        <script>
            new mdui.Dialog('#client-auth').open();
            $('#client-auth-btn').on('click', function() {
                $.ajax({
                    type: "GET",
                    url: "/api/v1/user/clientAuth?method=webAuth&clientid=<?= $clientid ?>",
                    dataType: "JSON",
                    success: function(response) {
                        alert('成功授权，请在客户端查看');
                        location.href = '/';
                    },
                    error: function(xhr) {
                        $('#client-auth-msg').text(`网站服务端出错！${xhr.state} ${xhr.code}`);
                    }
                });
            });
            $('#client-auth-close').click(function(e) {
                location.href = '/';
            });
        </script>
    <?php
    }
    ?>
    <div class="mdui-dialog custom-dialog" id="add-live">
        <div class="mdui-dialog-title mdui-color-grey-800">
            创建直播 <span id="add-live-msg" class="mdui-color-red"></span>
        </div>
        <form class="mdui-dialog-content" id="add-live-form" enctype="multipart/form-data">
            <div class="mdui-textfield mdui-textfield-floating-label">
                <label class="mdui-textfield-label mdui-text-color-white">直播间名称</label>
                <input class="mdui-textfield-input mdui-text-color-white" name="name" type="text" />
            </div>
            <div class="mdui-textfield mdui-textfield-floating-label">
                <label class="mdui-textfield-label mdui-text-color-white">描述</label>
                <textarea class="mdui-textfield-input mdui-text-color-white" name="description" type="text"></textarea>
            </div>
            <div class="mdui-textfield">
                <label class="mdui-textfield-label mdui-text-color-white">*封面图片上传（建议1280*720）</label>
                <input class="mdui-textfield-input mdui-text-color-white" name="pic" type="file" accept="image/*" />
            </div>
            <div class="mdui-textfield mdui-textfield-floating-label">
                <label class="mdui-textfield-label mdui-text-color-white">直播源（推荐ipv4地址）</label>
                <input class="mdui-textfield-input mdui-text-color-white" name="videoSource" type="url" />
            </div>
            <div class="mdui-textfield mdui-textfield-floating-label">
                <label class="mdui-textfield-label mdui-text-color-white">直播源类型</label>
                <input class="mdui-textfield-input mdui-text-color-white" name="videoSourceType" type="text" />
            </div>
        </form>
        <div class="mdui-dialog-actions">
            <button class="mdui-btn mdui-ripple mdui-float-left" mdui-dialog-close>关闭</button>
            <button class="mdui-btn mdui-btn-raised mdui-ripple" id="add-live-btn">创建</button>
        </div>
    </div>
<?php
}
?>