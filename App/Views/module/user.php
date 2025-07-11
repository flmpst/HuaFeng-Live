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
            <a href="https://live.dfggmc.top/disclaimer.html" target="_blank" rel="noopener noreferrer" class="mdui-btn mdui-ripple mdui-typo">继续填写则同意《免责声明》</a>
            <button class="mdui-btn mdui-ripple" mdui-dialog-close>关闭</button>
            <button class="mdui-btn mdui-btn-raised mdui-ripple" id="user-auth-btn" style="display: none;">登录/注册</button>
        </div>
    </div>
    <script src="https://static.geetest.com/v4/gt4.js"></script>
    <script>
        initGeetest4({
            product: 'popup',
            captchaId: '<?= $appConfig->geetest['captchaId'] ?>'
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
        <!-- 对话框标题区域 -->
        <div class="mdui-dialog-title mdui-color-grey-800 mdui-text-center">
            <!-- 头像上传区域 -->
            <div class="avatar-container mdui-center" style="width: 100px; height: 100px; position: relative; cursor: pointer;">
                <?= $userHelpers->getAvatar($userHelpers->getUserInfoByEnv()['email'], 100, 'mp', 'g', true, [
                    'style' => 'width: 100%; height: 100%; object-fit: cover; border-radius: 50%;',
                    'id' => 'user-avatar'
                ]) ?>
                <input type="file" id="avatar-upload" accept="image/*" style="display: none;">
                <div class="upload-overlay mdui-valign"
                    style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
                        background: rgba(0,0,0,0.5); color: white; border-radius: 50%;">
                    <div class="mdui-text-center" style="width: 100%;">
                        <i class="mdui-icon material-icons">photo_camera</i>
                        <div>更换头像</div>
                    </div>
                </div>
            </div>

            <h3 class="mdui-m-t-2"><?= $userHelpers->getUserInfoByEnv()['username'] ?>的个人设置面板 | 留空则不更改</h3>
            <div id="user-panel-msg" class="mdui-m-t-1"></div>
        </div>
        <form class="mdui-dialog-content" id="user-form">
            <input type="hidden" id="avatar-path" name="avatar_path">
            <div class="mdui-textfield mdui-textfield-floating-label mdui-text-color-white">
                <label class="mdui-textfield-label mdui-text-color-white">用户名</label>
                <input class="mdui-textfield-input mdui-text-color-white" autocomplete="username" name="username" type="text" value="<?= $userHelpers->getUserInfoByEnv()['username'] ?>" />
            </div>
            <div class="mdui-textfield mdui-textfield-floating-label">
                <label class="mdui-textfield-label mdui-text-color-white">当前密码</label>
                <input class="mdui-textfield-input mdui-text-color-white" autocomplete="current-password" name="password" type="password" />
            </div>
            <div class="mdui-textfield mdui-textfield-floating-label">
                <label class="mdui-textfield-label mdui-text-color-white">新密码</label>
                <input class="mdui-textfield-input mdui-text-color-white" autocomplete="new-password" name="newPassword" type="password" />
            </div>
            <div class="mdui-textfield mdui-textfield-floating-label">
                <label class="mdui-textfield-label mdui-text-color-white">确认新密码</label>
                <input class="mdui-textfield-input mdui-text-color-white" autocomplete="new-password" name="confirmPassword" type="password" />
            </div>
        </form>
        <div class="mdui-dialog-actions">
            <button class="mdui-btn mdui-ripple mdui-float-left" mdui-dialog-close>关闭</button>
            <button class="mdui-btn mdui-ripple mdui-float-left" id="user-panel-logout-btn">退出登录</button>
            <button class="mdui-btn mdui-btn-raised mdui-ripple" onclick="window.location.href='/dev'">开发者中心</button>
            <button class="mdui-btn mdui-btn-raised mdui-ripple" id="user-panel-btn">确定</button>
        </div>
    </div>

    <div class="mdui-dialog custom-dialog" id="add-live">
        <div class="mdui-dialog-title mdui-color-grey-800">
            创建直播 <span id="add-live-msg" class="mdui-color-red"></span>
        </div>
        <div id="livepic-upload-progress"></div>
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
                <input class="mdui-textfield-input mdui-text-color-white"
                    name="pic"
                    type="file"
                    accept="image/*"
                    id="coverImageInput" />
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