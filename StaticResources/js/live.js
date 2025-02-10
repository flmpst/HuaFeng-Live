// 创建一个 URL 对象
const url = new URL(window.location.href);
const parts = url.pathname.split('/');
const roomId = parts[parts.length - 1];
let offset = 0;
let eventOffset = 0;
const messagesPerPage = 40;

function initializePlayer(liveData) {
    const videoElement = document.getElementById('videoElement');

    // 检查浏览器是否支持 HLS
    try {
        if (Hls.isSupported() && liveData.videoSourceType === "m3u8") {
            const hls = new Hls();
            hls.loadSource(liveData.videoSource);
            hls.attachMedia(videoElement);

            hls.on(Hls.Events.MANIFEST_PARSED, function () {
                new Plyr(videoElement, {
                    disableContextMenu: true,
                    i18n: {
                        speed: '速度',
                        normal: '正常',
                    },
                    controls: ['play-large', 'play', 'current-time', 'mute', 'volume', 'captions', 'fullscreen']
                });
                hls.startLoad();
            });

            hls.on(Hls.Events.ERROR, function (event, data) {
                console.error("HLS.js error:", data);
                document.getElementById('error-msg').innerHTML = '<p class="error-msg mdui-color-red-a700">视频流加载失败，无法播放该视频。<br>' + data['type'] + '</p>';
            });
        }
        // 检查是否支持 FLV 格式
        else if (flvjs.isSupported() && liveData.videoSourceType === "flv") {
            new Plyr(videoElement, {
                disableContextMenu: true,
                i18n: {
                    speed: '速度',
                    normal: '正常',
                },
                controls: ['play-large', 'play', 'current-time', 'mute', 'volume', 'captions', 'fullscreen']
            });

            const flvPlayer = flvjs.createPlayer({
                type: "flv",
                url: liveData.videoSource
            });

            flvPlayer.attachMediaElement(videoElement);
            flvPlayer.load();

            flvPlayer.on(flvjs.Events.ERROR, function (errorType, errorDetails, errorObject) {
                console.error("FLV.js error:", errorDetails, errorObject);
                document.getElementById('error-msg').innerHTML = '<p class="error-msg mdui-color-red-a700">视频流加载失败，无法播放该视频。<br>' + errorDetails + ': ' + errorObject['msg'] + '</p>';
            });
        }
        // 检查是否支持 MP4 格式
        else if (videoElement.canPlayType('video/mp4') && liveData.videoSourceType === "mp4") {
            new Plyr(videoElement, {
                disableContextMenu: true,
                i18n: {
                    speed: '速度',
                    normal: '正常',
                },
                controls: ['play-large', 'play', 'current-time', 'mute', 'volume', 'captions', 'fullscreen']
            });

            videoElement.src = liveData.videoSource;
            videoElement.load();
        } else {
            document.getElementById('error-msg').innerHTML = '<p class="error-msg mdui-color-red-a700">您的浏览器环境不支持该视频格式！请下载最新的浏览器！</p>';
        }
    } catch (error) {
        console.error("未知错误:", error);
        document.getElementById('error-msg').innerHTML = '<p class="error-msg mdui-color-red-a700">加载视频时发生未知错误，请稍后重试。</p>';
    }
}

$('#edit-live-btn').on('click', function () {
    $(this).prop('disabled', true);

    const name = $("input[name='name']").val();
    const pic = $("input[name='pic']").val();
    const videoSource = $("input[name='videoSource']").val();
    const videoSourceType = $("input[name='videoSourceType']").val();

    if (!name) {
        $('#edit-live-msg').text('直播间名称不能为空');
        $(this).prop('disabled', false);
        return;
    }

    const urlPattern = /\b(?:https?|http):\/\/(?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,6}|\b(?:https?|http):\/\/(?:\d{1,3}\.){3}\d{1,3}|\b(?:https?|http):\/\/localhost(?::\d+)?(?:\/[^\s]*)?\b/;
    if (pic && !urlPattern.test(pic)) {
        $('#edit-live-msg').text('封面URL无效');
        $(this).prop('disabled', false);
        return;
    }
    if (videoSource && !urlPattern.test(videoSource)) {
        $('#edit-live-msg').text('直播源URL无效');
        $(this).prop('disabled', false);
        return;
    }
    if (!videoSourceType) {
        $('#edit-live-msg').text('直播源类型不能为空');
        $(this).prop('disabled', false);
        return;
    }

    $.ajax({
        type: "POST",
        url: "/api/v1/live/update?liveId=" + roomId,
        data: $('#edit-live-form').serialize(),
        dataType: "JSON",
        success: function (response) {
            if (response.code === 200) {
                window.location.reload();
            } else {
                $('#edit-live-msg').text(`更新失败：${response.message}`);
                $('#edit-live-btn').prop('disabled', false);
            }
        },
        error: function (xhr) {
            $('#edit-live-msg').text(`更新失败：${xhr.status}`);
            $('#edit-live-btn').prop('disabled', false);
        }
    });
});

$('#delet-live').click(function (e) {
    // 显示确认弹窗
    const isConfirmed = confirm("确定要删除这个直播间吗？");
    if (isConfirmed) {
        $.ajax({
            type: "GET",
            url: "/api/v1/live/delet?liveId=" + roomId,
            dataType: "JSON",
            success: function (response) {
                if (response.code === 200) {
                    location.href = '/';
                } else {
                    $('#edit-live-msg').text(`删除失败：${response.message}`);
                    $('#edit-live-btn').prop('disabled', false);
                }
            },
            error: function (xhr) {
                $('#edit-live-msg').text(`删除失败：${xhr.status}`);
                $('#edit-live-btn').prop('disabled', false);
            }
        });
    }
});

function displayMessage(msg) {
    // 自动滚动到底部
    $('#danmaku-container').animate({
        scrollTop: $('#danmaku-container')[0].scrollHeight
    }, 300);
    return `<p class="danmaku mdui-text-truncate"><img class="mdui-img-rounded" src="${msg.avatar}"> ${msg.username}: ${msg.content}</p>`
}
$('#sendDanmakuBtn').on('click', function () {
    const danmakuText = $('#danmaku-input').val().trim();
    $('#danmaku-input').val('');
    if (danmakuText) {
        $.ajax({
            type: "POST",
            url: "/api/v1/chat/send?room_id=" + roomId,
            data: {
                message: danmakuText
            },
            dataType: "JSON",
            success: function (response) {
                if (response.cod === 200) {
                    // 自动滚动到底部
                    $('#danmaku-container').animate({
                        scrollTop: $('#danmaku-container')[0].scrollHeight
                    }, 300);
                }
            },
            error: (xhr) => {
                console.error(xhr);
            }
        });
    }
});

const loadMessages = () => {
    chatBox = $('#danmaku-container')
    // 发起加载请求
    $.ajax({
        url: `/api/v1/chat/get?offset=${offset}&limit=${messagesPerPage}&room_id=${roomId}`,
        type: 'POST',
        dataType: 'json',
        success: (response) => {
            if (response.code === 200) {
                const data = response.data;
                // 加载消息内容
                if (Array.isArray(data.messages)) {
                    data.messages.forEach(msg => chatBox.append(displayMessage(msg)));
                    lastFetched = data.messages[data.messages.length - 1]?.created_at;
                    // 更新偏移量
                    offset += data.messages.length;
                }
                if (data.onlineUsers) {
                    const onlineUsersList = $('#online-users-list');
                    onlineUsersList.empty();
                    const currentTime = Math.floor(Date.now() / 1000);
                    let count = 0;
                    for (let userId in data.onlineUsers) {
                        count++
                        // 最多显示5个
                        if (count >= 6) {
                            break;
                        }
                        const user = data.onlineUsers[userId];
                        if (currentTime - user.last_time < 10) {
                            const userItem = $('<li>').text(`${user.user_name}|`);
                            $('#online-users-list-count').text(count)
                            onlineUsersList.append(userItem);
                        } else {
                            count--
                        }
                    }
                }
            }
        },
        error: (xhr) => {
            console.error(xhr);
        }
    });
};
setInterval(() => {
    loadMessages();
}, 1000);