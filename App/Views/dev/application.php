<?php
// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 检查是创建应用还是重置密钥
        if (isset($_POST['reset_secret'])) {
            // 处理密钥重置
            $appId = trim($_POST['app_id'] ?? '');
            if (empty($appId)) {
                throw new Exception('应用ID不能为空');
            }

            $userId = $userHelpers->getUserInfoByEnv()['user_id'];
            $newSecret = XQPF\Core\Modules\AppAuth::resetAppSecret($appId, $userId);

            // 设置成功消息
            $successMessage = "应用密钥已重置！<br>
                            新的App Secret: <code>$newSecret</code><br>
                            (请妥善保存这些信息，它只会显示这一次)";
        } else {
            // 处理新应用创建
            $appName = trim($_POST['app_name'] ?? '');
            $appDescription = trim($_POST['app_description'] ?? '');
            $redirectUri = trim($_POST['redirect_uri'] ?? '');

            if (empty($appName)) {
                throw new Exception('应用名称不能为空');
            }

            if (!empty($redirectUri)) {
                $redirectUri = filter_var($redirectUri, FILTER_SANITIZE_URL);
                if (!filter_var($redirectUri, FILTER_VALIDATE_URL)) {
                    throw new Exception('无效的回调地址');
                }
            }

            if (XQPF\Core\Modules\Database\Base::select('third_party_apps', ['*'], ['app_name' => $appName])) {
                throw new Exception('应用名：' . $appName .  '已被注册/已被他人注册！');
            }

            // 生成随机的App ID和App Secret
            $appId = bin2hex(random_bytes(8));
            $appSecret = bin2hex(random_bytes(16));

            // 获取当前用户ID
            $userId = $userHelpers->getUserInfoByEnv()['user_id'];

            // 准备要插入的数据
            $appData = [
                'user_id' => $userId,
                'app_id' => $appId,
                'app_secret' => $appSecret,
                'app_name' => $appName,
                'app_description' => $appDescription,
                'redirect_uri' => $redirectUri,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // 使用数据库类插入数据
            XQPF\Core\Modules\Database\Base::insert('third_party_apps', $appData);

            // 设置成功消息
            $successMessage = "应用创建成功！<br>
                             App ID: <code>$appId</code><br>
                             App Secret: <code>$appSecret</code><br>
                             (请妥善保存这些信息，它只会显示这一次)";
        }
    } catch (Exception $e) {
        // 设置错误消息
        $errorMessage = $e->getMessage();
    }
}

// 获取当前用户的所有应用
try {
    $userId = $userHelpers->getUserInfoByEnv()['user_id'];
    $userApps = XQPF\Core\Modules\AppAuth::getUserApps($userId);
} catch (Exception $e) {
    $userApps = [];
    $appsError = $e->getMessage();
}

$this->loadView('/dev/head', [
    'title' => $title,
    'userHelpers' => $userHelpers,
    'tokenManager' => $tokenManager
]);
?>

<h1>第三方应用接入收集</h1>
<hr>
<p>仅收集一些您的第三方应用信息，之后就可以拿App ID去更方便的开发第三方应用。</p>

<?php if (!empty($successMessage)): ?>
    <div class="mdui-alert mdui-alert-success">
        <i class="mdui-icon material-icons">check_circle</i>
        <?= $successMessage ?>
    </div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
    <div class="mdui-alert mdui-alert-error">
        <i class="mdui-icon material-icons">error</i>
        <?= htmlspecialchars($errorMessage) ?>
    </div>
<?php endif; ?>

<div class="mdui-tab mdui-tab-full-width" mdui-tab>
    <a href="#create-app" class="mdui-ripple">创建新应用</a>
    <a href="#my-apps" class="mdui-ripple">我的应用</a>
</div>

<div id="create-app">
    <form method="post" id="application-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <div class="mdui-row">
            <div class="mdui-col-xs-12">
                <div class="mdui-textfield mdui-textfield-floating-label">
                    <label class="mdui-textfield-label">应用名称</label>
                    <input class="mdui-textfield-input" type="text" name="app_name" required
                        value="<?= isset($_POST['app_name']) ? htmlspecialchars($_POST['app_name']) : '' ?>" />
                </div>
            </div>
        </div>

        <div class="mdui-row">
            <div class="mdui-col-xs-12">
                <div class="mdui-textfield mdui-textfield-floating-label">
                    <label class="mdui-textfield-label">应用描述</label>
                    <textarea class="mdui-textfield-input" name="app_description"><?= isset($_POST['app_description']) ? htmlspecialchars($_POST['app_description']) : '' ?></textarea>
                </div>
            </div>
        </div>

        <div class="mdui-row">
            <div class="mdui-col-xs-12">
                <div class="mdui-textfield mdui-textfield-floating-label">
                    <label class="mdui-textfield-label">回调地址 (可选)</label>
                    <input class="mdui-textfield-input" type="url" name="redirect_uri"
                        value="<?= isset($_POST['redirect_uri']) ? htmlspecialchars($_POST['redirect_uri']) : '' ?>" />
                    <div class="mdui-textfield-helper">用于OAuth授权的回调地址</div>
                </div>
            </div>
        </div>

        <div class="mdui-card-actions">
            <button type="submit" class="mdui-btn mdui-btn-raised mdui-ripple mdui-color-theme-accent">
                <i class="mdui-icon material-icons">send</i> 提交信息
            </button>
        </div>
    </form>
</div>

<div id="my-apps">
    <?php if (!empty($appsError)): ?>
        <div class="mdui-alert mdui-alert-error">
            <i class="mdui-icon material-icons">error</i>
            <?= htmlspecialchars($appsError) ?>
        </div>
    <?php elseif (empty($userApps)): ?>
        <div class="mdui-alert mdui-alert-info">
            <i class="mdui-icon material-icons">info</i>
            您还没有创建任何应用
        </div>
    <?php else: ?>
        <div class="mdui-table-fluid">
            <table class="mdui-table mdui-table-hoverable">
                <thead>
                    <tr>
                        <th>应用名称</th>
                        <th>App ID</th>
                        <th>描述</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userApps as $app): ?>
                        <tr>
                            <td><?= htmlspecialchars($app['app_name']) ?></td>
                            <td><code><?= htmlspecialchars($app['app_id']) ?></code></td>
                            <td><?= htmlspecialchars($app['app_description']) ?></td>
                            <td><?= htmlspecialchars($app['created_at']) ?></td>
                            <td>
                                <button class="mdui-btn mdui-btn-dense mdui-ripple mdui-color-theme-accent"
                                    onclick="confirmReset('<?= $app['app_id'] ?>', '<?= htmlspecialchars(addslashes($app['app_name'])) ?>')">
                                    重置密钥
                                </button>
                                <a href="javascript:void(0)" class="mdui-btn mdui-btn-dense mdui-ripple"
                                    onclick="showAppDetails('<?= htmlspecialchars(json_encode($app), ENT_QUOTES) ?>')">
                                    查看详情
                                </a>

                                <!-- 隐藏的表单，用于实际提交 -->
                                <form id="reset-form-<?= $app['app_id'] ?>" method="post" style="display: none;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="app_id" value="<?= htmlspecialchars($app['app_id']) ?>">
                                    <input type="hidden" name="reset_secret" value="1">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    function confirmReset(appId, appName) {
        mdui.confirm(
            `确定要重置应用 <strong>${appName}</strong> 的密钥吗？<br><br>
        旧密钥将立即失效，所有使用旧密钥的客户端将无法继续访问。`,
            '确认重置应用密钥',
            function() {
                // 用户确认后提交对应的表单
                document.getElementById(`reset-form-${appId}`).submit();
            },
            function() {
                // 用户取消，不做任何操作
            }, {
                confirmText: '确认重置',
                cancelText: '取消',
                history: false
            }
        );
    }

    function showAppDetails(appData) {
        appData = JSON.parse(appData);
        let content = `
        <div class="mdui-list">
            <div class="mdui-list-item">
                <div class="mdui-list-item-content">
                    <div class="mdui-list-item-title">应用名称</div>
                    <div class="mdui-list-item-text">${appData.app_name}</div>
                </div>
            </div>
            <div class="mdui-list-item">
                <div class="mdui-list-item-content">
                    <div class="mdui-list-item-title">App ID</div>
                    <div class="mdui-list-item-text"><code>${appData.app_id}</code></div>
                </div>
            </div>
            <div class="mdui-list-item">
                <div class="mdui-list-item-content">
                    <div class="mdui-list-item-title">创建时间</div>
                    <div class="mdui-list-item-text">${appData.created_at}</div>
                </div>
            </div>
            <div class="mdui-list-item">
                <div class="mdui-list-item-content">
                    <div class="mdui-list-item-title">回调地址</div>
                    <div class="mdui-list-item-text">${appData.redirect_uri || '未设置'}</div>
                </div>
            </div>
            <div class="mdui-list-item">
                <div class="mdui-list-item-content">
                    <div class="mdui-list-item-title">应用描述</div>
                    <div class="mdui-list-item-text">${appData.app_description || '无描述'}</div>
                </div>
            </div>
        </div>
    `;

        mdui.dialog({
            title: '应用详情',
            content: content,
            buttons: [{
                text: '关闭'
            }]
        });
    }
</script>

<?php
$this->loadView('/module/footer', [
    'title' => $title,
    'userHelpers' => $userHelpers,
    'tokenManager' => $tokenManager
]);
?>