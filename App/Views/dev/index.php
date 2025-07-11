<?php
// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $userHelpers->getUserInfoByEnv()['user_id'];

    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'generate':
                    $expires = isset($_POST['expires']) ? $_POST['expires'] : '+1 year';
                    $newToken = $tokenManager->generateToken(
                        $userId,
                        'api',
                        $expires,
                        null,
                        [
                            'CreateUA' => $_SERVER['HTTP_USER_AGENT'],
                            'CreateIP' => $userHelpers->getIp()
                        ]
                    );
                    // 存储生成的token在session中，用于一次性显示
                    $_SESSION['newly_generated_token'] = $newToken;
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;

                case 'delete_all':
                    $tokenManager->delete($userId, 'api', '+1 year', null);
                    $_SESSION['operation_message'] = '所有Token已删除';
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;

                default:
                    $_SESSION['operation_message'] = '无效操作';
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
            }
        } catch (Exception $e) {
            $_SESSION['operation_message'] = $e->getMessage();
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}
$this->loadView('/dev/head', [
    'title' => $title,
    'userHelpers' => $userHelpers,
    'tokenManager' => $tokenManager
]);
?>
<h1>开发者中心</h1>
<hr>

<?php if (isset($_SESSION['operation_message'])): ?>
    <div class="mdui-alert mdui-alert-<?= strpos($_SESSION['operation_message'], '失败') !== false ? 'error' : 'success' ?>">
        <?= htmlspecialchars($_SESSION['operation_message']) ?>
        <button class="mdui-btn mdui-ripple" onclick="this.parentNode.style.display='none'">关闭</button>
    </div>
    <?php unset($_SESSION['operation_message']); ?>
<?php endif; ?>

<div class="mdui-card">
    <div class="mdui-card-header mdui-color-grey-800">
        <div class="mdui-card-primary-title">开发者选项</div>
    </div>
    <div class="mdui-card-content mdui-typo">
        <p>API TOKENS</p>
        <hr>
        <p class="mdui-text-color-red-accent">
            警告：请务必妥善保管您的API密钥，拥有密钥等于拥有您的账号操作的一切权限，如自行泄露密钥导致损失花枫工作室将不承担任何责任。
        </p>
        <div id="token-list">
            <?php
            if ($userHelpers->checkUserLoginStatus()) {
                $userId = $userHelpers->getUserInfoByEnv()['user_id'];
                $tokens = $tokenManager->getTokens($userId);

                if (!empty($tokens)) {
                    foreach ($tokens as $token) {
                        $extraDisplay = '无';
                        if (!empty($token['extra'])) {
                            try {
                                $unserialized = @unserialize($token['extra']);
                                $extraDisplay = $unserialized !== false ?
                                    json_encode($unserialized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) :
                                    htmlspecialchars($token['extra']);
                            } catch (Exception $e) {
                                $extraDisplay = '数据解析错误';
                            }
                        }
            ?>
                        <li class="mdui-list-item mdui-ripple token-item">
                            <div class="mdui-list-item-content">
                                <div class="mdui-list-item-title mdui-text-color-blue">
                                    <i class="mdui-icon material-icons"><?= $tokenManager->getTokenIcon($token['type']) ?></i>
                                    <?= htmlspecialchars($token['created_at']) ?>
                                </div>
                                <div class="mdui-list-item-text ">
                                    类型: <?= $token['type'] ?>
                                </div>
                                <div class="mdui-list-item-text  extra-data" style="display:none">
                                    <i class="mdui-icon material-icons">info</i> 额外数据:
                                    <div class="mdui-typo">
                                        <pre class="mdui-m-t-1"><?= $extraDisplay ?></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="token-actions">
                                <button class="mdui-btn mdui-btn-icon mdui-ripple toggle-extra-btn" title="显示额外数据">
                                    <i class="mdui-icon material-icons">expand_more</i>
                                </button>
                            </div>
                        </li>
            <?php
                    }
                } else {
                    echo '<li class="mdui-list-item mdui-text-center">没有API密钥</li>';
                }
            }
            ?>
        </div>
    </div>
    <div class="mdui-card-actions">
        <form method="post" style="display: inline;">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="delete_all">
            <button type="submit" class="mdui-btn mdui-ripple mdui-float-left" onclick="return confirm('确定要删除所有Token吗？此操作不可逆！')">删除所有TOKEN</button>
        </form>

        <form method="post" style="display: inline;">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="generate">
            <div class="mdui-m-l-1" style="display: inline-block;">
                <select class="mdui-select" name="expires" mdui-select>
                    <option value="+1 hour">1小时</option>
                    <option value="+1 day">1天</option>
                    <option value="+1 week">1周</option>
                    <option value="+1 month">1个月</option>
                    <option value="+1 year" selected>1年</option>
                </select>
            </div>
            <button type="submit" class="mdui-btn mdui-ripple mdui-float-left">生成新API TOKEN</button>
        </form>
    </div>
</div>

<!-- 新Token生成的对话框 -->
<?php if (isset($_SESSION['newly_generated_token'])): ?>
    <div class="mdui-dialog mdui-dialog-open" id="new-token-dialog">
        <div class="mdui-dialog-title">新的API Token</div>
        <div class="mdui-dialog-content">
            <p>请妥善保存您的API Token，此Token只会显示一次：</p>
            <div class="mdui-textfield">
                <textarea id="new-token-text" class="mdui-textfield-input" readonly><?= htmlspecialchars($_SESSION['newly_generated_token']) ?></textarea>
            </div>
            <p class="mdui-text-color-red-accent">警告：请勿将此Token分享给他人！</p>
        </div>
        <div class="mdui-dialog-actions">
            <button class="mdui-btn mdui-ripple" id="copy-new-token-btn">复制Token</button>
            <button class="mdui-btn mdui-ripple" onclick="window.location.href='<?= $_SERVER['REQUEST_URI'] ?>'">关闭</button>
        </div>
    </div>
    <script>
        $(window).on('load', function() {
            new mdui.Dialog('#new-token-dialog').open()
        });
    </script>
<?php
    unset($_SESSION['newly_generated_token']);
endif; ?>
</main>

<script>
    $(window).on('load', function() {
        // 显示/隐藏额外数据
        $(document).on('click', '.toggle-extra-btn', function() {
            const extraData = $(this).closest('.token-item').find('.extra-data');
            const icon = $(this).find('.mdui-icon');

            extraData.toggle();
            if (extraData.is(':visible')) {
                icon.text('expand_less');
                $(this).attr('title', '隐藏额外数据');
            } else {
                icon.text('expand_more');
                $(this).attr('title', '显示额外数据');
            }
        });

        // 侧边栏切换
        var drawer = new mdui.Drawer('#main-drawer', {
            swipe: true // 启用滑动打开/关闭
        });

        // 复制新Token
        $('#copy-new-token-btn').click(function() {
            const token = $('#new-token-text').val();
            navigator.clipboard.writeText(token).then(function() {
                mdui.snackbar({
                    message: 'Token已复制到剪贴板',
                    position: 'right-bottom',
                    timeout: 2000
                });
            }).catch(function(err) {
                console.error('复制失败: ', err);
            });
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