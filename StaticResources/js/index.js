$(document).ready(function () {
    const listContainer = $('#list');
    let dataCache = [];

    function renderList(data) {
        listContainer.empty();
        if (data.length === 0) {
            listContainer.html(`未找到相关内容，要不然<button class="mdui-btn mdui-ripple mdui-btn-raised" mdui-dialog="{target: '#add-live'}">创建</button>一个？`);
            return;
        }
        const cards = data.map(item => `
        <a href="${item.id}" class="mdui-col-md-3 mdui-m-b-3 mdui-hoverable item">
            <div class="mdui-card">
                <span class="status">${item.status == '未在直播' ? 'live' : '直播中'}</span>
                <span class="people"><i class="mdui-icon material-icons">people_outline</i>${item.peoples}</span>
                <div class="mdui-card-media">
                    <img src="${item.pic || '/StaticResources/image/Image_330346604143.png'}">
                    <div class="mdui-card-media-covered">
                        <div class="mdui-card-primary">
                            <div class="mdui-card-primary-title">${item.name}</div>
                        </div>
                        <div class="mdui-card-content">${item.description} 主播: ${item.authr}</div>
                    </div>
                </div>
            </div>
        </a>
        `).join('');
        listContainer.append(cards);
    }

    $.ajax({
        url: '/api/v1/live/list',
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            if (data.code === 200) {
                dataCache = data.data.list;
                renderList(dataCache);
                $('#search').on('input', function () {
                    const query = $(this).val().toLowerCase();
                    const filteredData = dataCache.filter(item =>
                        (item.status && item.status.toLowerCase().includes(query)) ||
                        (item.name && item.name.toLowerCase().includes(query)) ||
                        (item.authr && item.authr.toLowerCase().includes(query))
                    );
                    filteredData.sort((a, b) => a.name.localeCompare(b.name));
                    renderList(filteredData);
                });
            } else {
                listContainer.html(`加载列表出错！ ${data.message}`);
            }
        },
        error: function (xhr) {
            listContainer.html('加载列表时出错！' + xhr.status);
        }
    });

    $('#user-panel-btn').on('click', function () {
        $(this).prop('disabled', true);
        $.ajax({
            type: "POST",
            url: "/api/v1/user/update",
            data: $('#user-form').serialize(),
            dataType: "JSON",
            success: function (response) {
                $('#user-panel-msg').html(`<span class="mdui-color-red-a700">${response.message}</span>`);
                if (response.code === 200) {
                    location.href = '/';
                }
                $('#user-panel-btn').prop('disabled', false);
            },
            error: function (xhr) {
                $('#user-panel-msg').html(`<span class="mdui-color-red-a700">${xhr.status}</span>`);
                $('#user-panel-btn').prop('disabled', false);
            }
        });
    });

    $('#user-panel-logout-btn').on('click', function () {
        $(this).prop('disabled', true);
        $.ajax({
            type: "POST",
            url: "/api/v1/user/logout",
            data: $('#user-form').serialize(),
            dataType: "JSON",
            success: function (response) {
                $('#user-panel-msg').html(`<span class="mdui-color-red-a700">${response.message}</span>`);
                if (response.code === 200) {
                    location.href = '/';
                }
                $('#user-panel-logout-btn').prop('disabled', false);
            },
            error: function (xhr) {
                $('#user-panel-msg').html(`<span class="mdui-color-red-a700">${xhr.status}</span>`);
                $('#user-panel-logout-btn').prop('disabled', false);
            }
        });
    });

    $('#add-live-btn').on('click', function () {
        $(this).prop('disabled', true);

        const name = $("input[name='name']").val();
        const pic = $("input[name='pic']").val();
        const videoSource = $("input[name='videoSource']").val();
        const videoSourceType = $("input[name='videoSourceType']").val();

        if (!name) {
            $('#add-live-msg').text('直播间名称不能为空');
            $(this).prop('disabled', false);
            return;
        }

        const urlPattern = /\b(?:https?|http):\/\/(?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,6}|\b(?:https?|http):\/\/(?:\d{1,3}\.){3}\d{1,3}|\b(?:https?|http):\/\/localhost(?::\d+)?(?:\/[^\s]*)?\b/;
        if (pic && !urlPattern.test(pic)) {
            $('#add-live-msg').text('封面URL无效');
            $(this).prop('disabled', false);
            return;
        }
        if (videoSource && !urlPattern.test(videoSource)) {
            $('#add-live-msg').text('直播源URL无效');
            $(this).prop('disabled', false);
            return;
        }
        if (!videoSourceType) {
            $('#add-live-msg').text('直播源类型不能为空');
            $(this).prop('disabled', false);
            return;
        }

        $.ajax({
            type: "POST",
            url: "/api/v1/live/create",
            data: $('#add-live-form').serialize(),
            dataType: "JSON",
            success: function (response) {
                if (response.code === 200) {
                    location.href = response.data.id;
                } else {
                    $('#add-live-msg').text(`创建失败：${response.message}`);
                    $('#add-live-btn').prop('disabled', false);
                }
            },
            error: function (xhr) {
                $('#add-live-msg').text(`创建失败：${xhr.status}`);
                $('#add-live-btn').prop('disabled', false);
            }
        });
    });

    $('#copy-token-btn').on('click', function () {
        const tokenText = $('#api-token-display').text();
        navigator.clipboard.writeText(tokenText).then(function () {
            mdui.snackbar({ message: 'Token 已复制到剪贴板！', timeout: 2000 });
        }, function (err) {
            mdui.snackbar({ message: '复制失败，请重试！', timeout: 2000 });
        });
    });

    $('#user-openapi-btn').on('click', function () {
        mdui.snackbar({
            message: '您确定要重新生成 Token 吗？此操作将使当前 Token 无效。',
            buttonText: '是的，我确定',
            onButtonClick: function () {
                $.ajax({
                    type: "POST",
                    url: "/api/v1/refresh",
                    dataType: "JSON",
                    success: function (response) {
                        if (response.code === 200) {
                            $('#api-token-display').text(response.data)
                            mdui.snackbar({ message: `生成成功！`, timeout: 2000 });
                        } else {
                            mdui.snackbar({ message: `生成失败！${response.message}`, timeout: 2000 });
                        }
                    },
                    error: function (xhr) {
                        mdui.snackbar({ message: `生成失败！${xhr.message}`, timeout: 2000 });
                    }
                });
            },
        });
    });
});