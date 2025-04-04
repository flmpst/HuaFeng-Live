// 创建一个 URL 对象并安全地获取 roomId
function getRoomIdFromUrl() {
    try {
        const url = new URL(window.location.href);
        const parts = url.pathname.split('/').filter(part => part.trim() !== '');
        return parts[parts.length - 1] || '';
    } catch (error) {
        console.error("解析URL时出错:", error);
        showError("无法解析当前页面URL");
        return '';
    }
}

const roomId = getRoomIdFromUrl();
if (!roomId) {
    showError("无法获取直播间ID，请检查URL是否正确");
}

let offset = 0;
let eventOffset = 0;
const messagesPerPage = 40;

// 显示错误信息的统一函数
function showError(message, elementId = 'error-msg') {
    console.error(message);
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
        errorElement.innerHTML = `<p class="error-msg mdui-color-red-a700">${message}</p>`;
    }
}

// 初始化视频播放器
function initializePlayer(liveData) {
    if (!liveData || !liveData.videoSourceType || !liveData.videoSource) {
        showError("无效的直播数据");
        return;
    }

    const videoElement = document.getElementById('videoElement');
    if (!videoElement) {
        showError("找不到视频元素");
        return;
    }

    try {
        // HLS 流处理
        if (liveData.videoSourceType === "m3u8") {
            if (!Hls || !Hls.isSupported()) {
                showError("您的浏览器不支持HLS视频流");
                return;
            }

            const hls = new Hls({
                maxMaxBufferLength: 600,
                maxBufferSize: 60 * 1000 * 1000,
                maxBufferLength: 30,
            });

            hls.loadSource(liveData.videoSource);
            hls.attachMedia(videoElement);

            hls.on(Hls.Events.MANIFEST_PARSED, function () {
                try {
                    new Plyr(videoElement, {
                        disableContextMenu: true,
                        i18n: {
                            speed: '速度',
                            normal: '正常',
                        }
                    });
                    hls.startLoad();
                } catch (error) {
                    showError("播放器初始化失败: " + error.message);
                }
            });

            hls.on(Hls.Events.ERROR, function (event, data) {
                let errorMessage = "视频流加载失败";
                if (data.fatal) {
                    switch (data.type) {
                        case Hls.ErrorTypes.NETWORK_ERROR:
                            errorMessage += " (网络错误)";
                            hls.startLoad();
                            break;
                        case Hls.ErrorTypes.MEDIA_ERROR:
                            errorMessage += " (媒体错误)";
                            hls.recoverMediaError();
                            break;
                        default:
                            errorMessage += " (无法恢复的错误)";
                            hls.destroy();
                            break;
                    }
                }
                showError(`${errorMessage}: ${data.details}`);
            });
        }
        // FLV 流处理
        else if (liveData.videoSourceType === "flv") {
            if (!flvjs || !flvjs.isSupported()) {
                showError("您的浏览器不支持FLV视频流");
                return;
            }

            try {
                const flvPlayer = flvjs.createPlayer({
                    type: "flv",
                    url: liveData.videoSource,
                    hasAudio: true,
                    hasVideo: true,
                    isLive: true
                }, {
                    enableWorker: true,
                    lazyLoadMaxDuration: 3 * 60,
                    seekType: 'range'
                });

                flvPlayer.attachMediaElement(videoElement);
                flvPlayer.load();

                new Plyr(videoElement, {
                    disableContextMenu: true,
                    i18n: {
                        speed: '速度',
                        normal: '正常',
                    }
                });

                flvPlayer.on(flvjs.Events.ERROR, function (errorType, errorDetails, errorObject) {
                    let errorMessage = "FLV播放错误";
                    if (errorDetails === flvjs.ErrorDetails.NETWORK_ERROR) {
                        errorMessage += " (网络错误)";
                    } else if (errorDetails === flvjs.ErrorDetails.MEDIA_ERROR) {
                        errorMessage += " (媒体错误)";
                    }
                    showError(`${errorMessage}: ${errorDetails} - ${errorObject.msg}`);
                });
            } catch (error) {
                showError("FLV播放器初始化失败: " + error.message);
            }
        }
        // MP4 流处理
        else if (liveData.videoSourceType === "mp4") {
            if (!videoElement.canPlayType('video/mp4')) {
                showError("您的浏览器不支持MP4视频格式");
                return;
            }

            try {
                videoElement.src = liveData.videoSource;
                videoElement.load();

                new Plyr(videoElement, {
                    disableContextMenu: true,
                    i18n: {
                        speed: '速度',
                        normal: '正常',
                    },
                    controls: ['play-large', 'play', 'current-time', 'mute', 'volume', 'captions', 'fullscreen']
                });

                videoElement.addEventListener('error', function () {
                    showError("视频加载失败，请检查网络连接或视频源");
                });
            } catch (error) {
                showError("MP4播放器初始化失败: " + error.message);
            }
        } else {
            showError("不支持的视频源类型: " + liveData.videoSourceType);
        }
    } catch (error) {
        showError("播放器初始化时发生未知错误: " + error.message);
    }
}

// 编辑直播间处理
$('#edit-live-btn').on('click', function () {
    const $btn = $(this);
    $btn.prop('disabled', true).html('<i class="mdui-icon material-icons">hourglass_empty</i> 处理中...');

    const name = $("input[name='name']").val().trim();
    const pic = $("input[name='pic']").val().trim();
    const videoSource = $("input[name='videoSource']").val().trim();
    const videoSourceType = $("input[name='videoSourceType']").val().trim();

    // 验证输入
    if (!name) {
        $('#edit-live-msg').text('直播间名称不能为空').addClass('mdui-text-color-red');
        $btn.prop('disabled', false).html('保存更改');
        return;
    }

    if (videoSource) {
        try {
            const urlPattern = /^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([/\w .-]*)*\/?$/;
            if (!urlPattern.test(videoSource)) {
                $('#edit-live-msg').text('直播源URL无效').addClass('mdui-text-color-red');
                $btn.prop('disabled', false).html('保存更改');
                return;
            }
        } catch (error) {
            console.error("URL验证错误:", error);
        }
    }

    if (videoSource && !videoSourceType) {
        $('#edit-live-msg').text('直播源类型不能为空').addClass('mdui-text-color-red');
        $btn.prop('disabled', false).html('保存更改');
        return;
    }

    // 发送请求
    $.ajax({
        type: "POST",
        url: "/api/v1/live/update?liveId=" + roomId,
        data: $('#edit-live-form').serialize(),
        dataType: "JSON",
        success: function (response) {
            if (response.code === 200) {
                $('#edit-live-msg').text('更新成功').removeClass('mdui-text-color-red');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                $('#edit-live-msg').text(`更新失败：${response.message || '未知错误'}`);
                $btn.prop('disabled', false).html('保存更改');
            }
        },
        error: function (xhr) {
            let errorMsg = `更新失败：${xhr.status}`;
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg += ` - ${xhr.responseJSON.message}`;
            }
            $('#edit-live-msg').text(errorMsg);
            $btn.prop('disabled', false).html('保存更改');
        }
    });
});

// 删除直播间处理
$('#delet-live').click(function (e) {
    e.preventDefault();
    mdui.confirm('确定要删除这个直播间吗？此操作不可撤销！', '确认删除',
        function () {
            const $btn = $('#delet-live');
            $btn.prop('disabled', true).html('<i class="mdui-icon material-icons">hourglass_empty</i> 删除中...');

            $.ajax({
                type: "GET",
                url: "/api/v1/live/delet?liveId=" + roomId,
                dataType: "JSON",
                success: function (response) {
                    if (response.code === 200) {
                        mdui.snackbar({
                            message: '直播间已删除',
                            position: 'right-top',
                            onClose: () => location.href = '/'
                        });
                    } else {
                        mdui.snackbar({
                            message: `删除失败：${response.message || '未知错误'}`,
                            position: 'right-top'
                        });
                        $btn.prop('disabled', false).html('删除直播间');
                    }
                },
                error: function (xhr) {
                    let errorMsg = `删除失败：${xhr.status}`;
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += ` - ${xhr.responseJSON.message}`;
                    }
                    mdui.snackbar({
                        message: errorMsg,
                        position: 'right-top'
                    });
                    $btn.prop('disabled', false).html('删除直播间');
                }
            });
        },
        function () {
            return;
        },
        {
            confirmText: '确认删除',
            cancelText: '取消',
            history: false
        }
    );
});

// 显示消息
function displayMessage(msg) {
    if (!msg || !msg.avatar || !msg.username || !msg.content) {
        console.warn("无效的消息格式:", msg);
        return '';
    }

    return `
        <div class="danmaku mdui-text-truncate">
            <img class="mdui-img-rounded" src="${msg.avatar}" onerror="this.src='/static/images/default-avatar.png'">
            <span class="username">${escapeHtml(msg.username)}</span>: 
            <span class="content">${escapeHtml(msg.content)}</span>
        </div>
    `;
}

// HTML转义函数
function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// 发送弹幕
$('#sendDanmakuBtn').on('click', function () {
    const $btn = $(this);
    const danmakuText = $('#danmaku-input').val().trim();

    if (!danmakuText) {
        mdui.snackbar({
            message: '弹幕内容不能为空',
            position: 'right-top'
        });
        return;
    }

    $btn.prop('disabled', true).html('发送中...');

    $.ajax({
        type: "POST",
        url: "/api/v1/chat/send?room_id=" + roomId,
        data: {
            message: danmakuText
        },
        dataType: "JSON",
        success: function (response) {
            if (response.code === 200) {
                $('#danmaku-input').val('');
                scrollToBottom();
            } else {
                mdui.snackbar({
                    message: `发送失败：${response.message || '未知错误'}`,
                    position: 'right-top'
                });
            }
            $btn.prop('disabled', false).html('发送');
        },
        error: function (xhr) {
            let errorMsg = `发送失败：${xhr.status}`;
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg += ` - ${xhr.responseJSON.message}`;
            }
            mdui.snackbar({
                message: errorMsg,
                position: 'right-top'
            });
            $btn.prop('disabled', false).html('发送');
        }
    });
});

// 滚动到底部
function scrollToBottom() {
    const container = $('#danmaku-container')[0];
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}

// 加载消息
const loadMessages = () => {
    if (!roomId) return;

    const chatBox = $('#danmaku-container');
    if (!chatBox.length) return;

    $.ajax({
        url: `/api/v1/chat/get?offset=${offset}&limit=${messagesPerPage}&room_id=${roomId}`,
        type: 'POST',
        dataType: 'json',
        timeout: 5000,
        success: (response) => {
            if (response.code === 200) {
                const data = response.data;

                // 加载消息内容
                if (Array.isArray(data.messages)) {
                    data.messages.forEach(msg => {
                        const messageHtml = displayMessage(msg);
                        if (messageHtml) {
                            chatBox.append(messageHtml);
                        }
                    });

                    if (data.messages.length > 0) {
                        lastFetched = data.messages[data.messages.length - 1]?.created_at;
                        offset += data.messages.length;

                        // 只有在新消息到达时才滚动
                        if (data.messages.length === messagesPerPage) {
                            scrollToBottom();
                        }
                    }
                }

                // 更新在线用户列表
                if (data.onlineUsers) {
                    updateOnlineUsers(data.onlineUsers);
                }
            } else {
                console.warn("获取消息失败:", response.message);
            }
        },
        error: (xhr, status, error) => {
            console.error("获取消息错误:", status, error);
            // 可以在这里添加重试逻辑
        }
    });
};

// 更新在线用户列表
function updateOnlineUsers(onlineUsers) {
    const onlineUsersList = $('#online-users-list');
    const onlineUsersCount = $('#online-users-list-count');

    onlineUsersList.empty();
    const currentTime = Math.floor(Date.now() / 1000);
    let count = 0;

    for (let userId in onlineUsers) {
        const user = onlineUsers[userId];
        if (currentTime - user.last_time < 10) { // 10秒内活跃的用户
            if (count < 5) { // 最多显示5个
                const userItem = $('<li>').text(`${user.user_name}|`);
                onlineUsersList.append(userItem);
            }
            count++;
        }
    }

    onlineUsersCount.text(count);
    if (count >= 6) {
        onlineUsersList.append($('<li>').text('...'));
    }
}

// 初始化消息加载
if (roomId) {
    loadMessages();
    setInterval(loadMessages, 1000);

    // 回车发送弹幕
    $('#danmaku-input').on('keypress', function (e) {
        if (e.which === 13) { // Enter键
            $('#sendDanmakuBtn').click();
            e.preventDefault();
        }
    });
} else {
    showError("无效的直播间ID");
}