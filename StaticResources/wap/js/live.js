$(document).ready(function () {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');  // 获取名为 'id' 的参数
    const sendButton = $('#send-btn');
    const messageInput = $('#message-input');
    const chatContainer = $('#chat-container');
    let offset = 0;
    let eventOffset = 0;
    const messagesPerPage = 40;

    function displayMessage(msg) {
        // 自动滚动到底部
        $('#chat-container').animate({
            scrollTop: $('#chat-container')[0].scrollHeight
        }, 300);
        return `<div class="chat-message"><img class="mdui-img-rounded" src="${msg.avatar}"> ${msg.username}: ${msg.content}</div>`
    }
    $.ajax({
        type: "GET",
        url: "https://live.dfggmc.top/api/v1/live/get?live_id=" + id,
        dataType: "JSON",
        success: function (response) {
            if (response.data.status === 'on') {
                initializePlayer(response.data);
            } else {
                document.getElementById('error-msg').innerHTML = '<p class="error-msg mdui-color-red-a700">直播间不存在。</p>';
            }
        },
        complete: function () {
            $('#progressbar').hide();
        }
    });

    const loadMessages = () => {
        chatBox = $('#chat-container')
        // 发起加载请求
        $.ajax({
            url: `https://live.dfggmc.top/api/v1/chat/get?offset=${offset}&limit=${messagesPerPage}&room_id=${id}`,
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

    function initializePlayer(liveData) {
        $('.mui-title').text(liveData.name);
        document.title = liveData.name;
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

    // 发送消息功能
    sendButton.on('click', function () {
        const message = messageInput.val().trim();
        if (message) {
            const newMessage = ('<div class="chat-message"></div>').text(message);
            chatContainer.append(newMessage);
            messageInput.val(''); // 清空输入框
            chatContainer.scrollTop(chatContainer[0].scrollHeight); // 滚动到最新消息
        }
    });

    // 按下回车键也能发送消息
    messageInput.on('keydown', function (event) {
        if (event.key === 'Enter') {
            sendButton.click();
        }
    });
});