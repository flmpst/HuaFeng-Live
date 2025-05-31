const url = new URL(window.location.href);
const parts = url.pathname.split('/');
const roomId = parts[parts.length - 1];
let currentOffset = 0;      // 当前加载的偏移量
let totalMessages = 0;      // 消息总数
let lastMessageId = 0;      // 最后一条消息ID
let isLoading = false;      // 是否正在加载
let hasMoreMessages = true; // 是否还有更多消息

// 初始化播放器
function initializePlayer(liveData) {
    new DPlayer({
        container: document.getElementById('videoElement'),
        live: true,
        autoplay: true,
        preventClickToggle: true,
        danmaku: true,
        video: {
            url: liveData.videoSource,
            pic: liveData.pic || '/StaticResources/image/Image_330346604143.png',
            type: liveData.videoSourceType === "m3u8" ? 'hls' :
                liveData.videoSourceType === "flv" ? 'flv' :
                    'normal'
        },
        apiBackend: {
            read: function (options) {
                setInterval(() => {
                    checkNewMessages();
                }, 1000);
                options.success();
            },
            send: function (options) {
                console.debug(options)
                const message = options.data.text;
                if (message.trim() === '') {
                    options.error('消息不能为空');
                    return;
                }
                $.ajax({
                    url: '/api/v1/chat/send?room_id=' + roomId,
                    type: 'POST',
                    data: {
                        message: message,
                        color: options.data.color || '#FFFFFF',
                        type: options.data.type || '0'
                    },
                    success: function (response) {
                        if (response.code !== 200) {
                            options.error(`发送失败：${response.message}`);
                        } else {
                            options.success();
                        }
                    }
                });
            }
        }
    });
}

// 获取消息总数
async function getTotalMessages() {
    try {
        const response = await $.get(`/api/v1/chat/count?room_id=${roomId}`);
        if (response.code === 200) {
            return response.data.count || 0;
        }
    } catch (error) {
        console.error('获取消息总数失败:', error);
    }
    return 0;
}

// 加载最新消息（初始加载）
async function loadLatestMessages() {
    isLoading = true;
    try {
        totalMessages = await getTotalMessages();

        const response = await $.get(
            `/api/v1/chat/get?room_id=${roomId}&limit=40&offset=${totalMessages}`
        );

        if (response.code === 200) {
            const messages = response.data.messages || [];
            messages.forEach(msg => displayMessage(msg));

            if (messages.length > 0) {
                lastMessageId = messages[messages.length - 1].id;
                hasMoreMessages = currentOffset > 0;
            }
        }
    } catch (error) {
        console.error('加载最新消息失败:', error);
    } finally {
        isLoading = false;
    }
}

// 检查并加载新消息
async function checkNewMessages() {
    try {
        const newTotal = await getTotalMessages();
        const newMessagesCount = newTotal - totalMessages;

        if (newMessagesCount > 0) {
            const response = await $.get(
                `/api/v1/chat/get?room_id=${roomId}&limit=${newMessagesCount}&offset=${totalMessages}`
            );

            if (response.code === 200) {
                const messages = response.data.messages || [];
                messages.forEach(msg => displayMessage(msg));

                if (messages.length > 0) {
                    lastMessageId = messages[messages.length - 1].id;
                    totalMessages = newTotal;
                }
            }
        }
    } catch (error) {
        console.error('检查新消息失败:', error);
    }
}

/**
 * 显示消息（弹幕+聊天）
 * 支持顶部弹幕和底部弹幕可叠加（多条同时显示，自动分配不重叠的行）
 */
function displayMessage(msg) {
    $('#danmaku-container').append(`
        <p class="danmaku">
            <img class="avatar" src="${msg.avatar || 'default-avatar.png'}">${msg.username || '匿名用户'}: ${msg.content}
        </p>`);
    // 读取本地弹幕显示和透明度设置
    const danmakuShow = localStorage.getItem('dplayer-danmaku-show');
    if (danmakuShow === '0') {
        // 不显示弹幕
        return;
    }
    let danmakuOpacity = parseFloat(localStorage.getItem('dplayer-danmaku-opacity'));
    if (isNaN(danmakuOpacity) || danmakuOpacity < 0 || danmakuOpacity > 1) {
        danmakuOpacity = 1;
    }

    const videoContainer = $('#video-container');
    if (!videoContainer.length) {
        console.error('视频容器未找到');
        return;
    }

    // 弹幕行数配置
    const TOP_ROWS = 4;
    const BOTTOM_ROWS = 4;
    const DANMAKU_MARGIN = 4;

    // 用于记录每行弹幕的占用状态
    window._danmakuTopRows = window._danmakuTopRows || Array(TOP_ROWS).fill(0);
    window._danmakuBottomRows = window._danmakuBottomRows || Array(BOTTOM_ROWS).fill(0);

    const danmakuElement = $('<div>', {
        class: 'danmaku-item',
        css: {
            position: 'absolute',
            whiteSpace: 'nowrap',
            fontSize: '20px',
            color: msg.color || '#FFFFFF',
            pointerEvents: 'none',
            textShadow: '1px 1px 2px #000',
            opacity: danmakuOpacity
        }
    });

    const avatar = msg.avatar ? `<img class="danmaku-avatar" src="${msg.avatar}" alt="头像">` : '';
    danmakuElement.html(`${avatar}<span class="danmaku-text">${msg.username || '匿名用户'}: ${msg.content}</span>`);

    const containerWidth = videoContainer.width();
    const containerHeight = videoContainer.height();
    const danmakuType = msg.danmaku?.type || 0;

    videoContainer.append(danmakuElement);
    const danmakuWidth = danmakuElement.outerWidth();
    const danmakuHeight = danmakuElement.outerHeight();

    // 查找可用的弹幕行
    function findAvailableRow(rowsArr) {
        for (let i = 0; i < rowsArr.length; i++) {
            if (!rowsArr[i]) return i;
        }
        // 如果都被占用，随机找一行
        return Math.floor(Math.random() * rowsArr.length);
    }

    switch (danmakuType) {
        case '0': { // 滚动弹幕
            const topPosition = Math.floor(Math.random() * (containerHeight - danmakuHeight - 40)) + 20;
            danmakuElement.css({
                top: `${topPosition}px`,
                right: `-${danmakuWidth}px`
            });
            // 让弹幕速度更慢：将 duration 乘以 1.5
            danmakuElement.animate({
                right: `${containerWidth}px`
            }, {
                duration: Math.max(7500, Math.min(22500, msg.content.length * 300)),
                easing: 'linear',
                complete: function () {
                    $(this).remove();
                }
            });
            break;
        }
        case '1': { // 顶部弹幕可叠加
            const row = findAvailableRow(window._danmakuTopRows);
            window._danmakuTopRows[row] = 1;
            danmakuElement.css({
                top: `${10 + row * (danmakuHeight + DANMAKU_MARGIN)}px`,
                left: '50%',
                transform: 'translateX(-50%)'
            });
            setTimeout(() => {
                danmakuElement.fadeOut(500, function () {
                    window._danmakuTopRows[row] = 0;
                    $(this).remove();
                });
            }, 3000);
            break;
        }
        case '2': { // 底部弹幕可叠加
            const row = findAvailableRow(window._danmakuBottomRows);
            window._danmakuBottomRows[row] = 1;
            danmakuElement.css({
                bottom: `${10 + row * (danmakuHeight + DANMAKU_MARGIN)}px`,
                left: '50%',
                transform: 'translateX(-50%)'
            });
            setTimeout(() => {
                danmakuElement.fadeOut(500, function () {
                    window._danmakuBottomRows[row] = 0;
                    $(this).remove();
                });
            }, 3000);
            break;
        }
        default:
            console.warn('未知弹幕类型:', danmakuType);
            danmakuElement.remove();
    }
}


document.addEventListener('DOMContentLoaded', function () {
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
    // 获取直播数据并初始化播放器
    $.get(`/api/v1/live/get?live_id=${roomId}`, function (response) {
        if (response.code === 200) {
            initializePlayer(response.data);
            loadLatestMessages();
            $('.dplayer-danmaku').remove(); // 移除DPlayer默认的弹幕容器
        } else {
            $('#error-msg').html('<p class="error-msg">加载直播数据失败</p>');
        }
    }).fail(function () {
        $('#error-msg').html('<p class="error-msg">无法连接到服务器</p>');
    });
});