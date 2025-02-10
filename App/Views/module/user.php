<?php
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
                                            location.href = '/';
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
            <p>个人设置面板(留空为不更改 | 更改密码尚未实现)</p> <span id="user-panel-msg"></span>
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
            <p>开发者选项</p>
        </div>
        <div class="mdui-dialog-content mdui-typo">
            <hr>
            <p class="mdui-text-color-white">API TOKEN</p>
            <div class="mdui-row">
                <div class="mdui-col mdui-col-xs-10">
                    <pre id="api-token-display" class="mdui-text-color-white"><?= htmlspecialchars($userHelpers->getUserInfoByEnv()['token']) ?></pre>
                </div>
                <div class="mdui-col mdui-col-xs-2">
                    <button class="mdui-btn mdui-btn-icon mdui-ripple" id="copy-token-btn" title="复制Token">
                        <i class="mdui-icon material-icons">content_copy</i>
                    </button>
                </div>
            </div>
            <p>警告：请务必妥善保管您的API密钥，拥有密钥等于拥有您的账号操作的一切权限， 如自行泄露密钥导致损失花枫工作室将不承担任何责任。</p>
            <button class="mdui-btn mdui-btn-raised mdui-ripple mdui-text-color-white" id="user-openapi-btn">重新生成TOKEN</button>
        </div>
        <div class="mdui-dialog-actions">
            <button class="mdui-btn mdui-ripple" mdui-dialog-close mdui-dialog="{target: '#user-panel'}">返回设置面板</button>
        </div>
    </div>

    <div class="mdui-dialog custom-dialog" id="add-live">
        <div class="mdui-dialog-title mdui-color-grey-800">
            创建直播 <span id="add-live-msg" class="mdui-color-red"></span>
        </div>
        <form class="mdui-dialog-content" id="add-live-form">
            <div class="mdui-textfield mdui-textfield-floating-label">
                <label class="mdui-textfield-label mdui-text-color-white">直播间名称</label>
                <input class="mdui-textfield-input mdui-text-color-white" name="name" type="text" />
            </div>
            <div class="mdui-textfield mdui-textfield-floating-label">
                <label class="mdui-textfield-label mdui-text-color-white">描述</label>
                <textarea class="mdui-textfield-input mdui-text-color-white" name="description" type="text"></textarea>
            </div>
            <div class="mdui-textfield mdui-textfield-floating-label">
                <label class="mdui-textfield-label mdui-text-color-white">*封面url（最大1280*720）</label>
                <input class="mdui-textfield-input mdui-text-color-white" name="pic" type="url" />
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