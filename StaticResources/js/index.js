$(document).ready(function () {
    const listContainer = $('#list');
    let dataCache = [];

    function renderList(data) {
        listContainer.empty();
        if (data.length === 0) {
            listContainer.html(`<p class="mdui-text-color-white-text">未找到相关内容，要不然创建一个？</p>`);
            return;
        }
        const cards = data.map(item => `
        <a href="${item.id}" class="mdui-col-md-3 mdui-m-b-3 mdui-hoverable item">
            <div class="mdui-card">
                <span class="people"><i class="mdui-icon material-icons">people_outline</i>${item.peoples}</span>
                <div class="mdui-card-media">
                    <img src="${item.pic || '/StaticResources/image/Image_330346604143.png'}">
                    <div class="mdui-card-media-covered">
                        <div class="mdui-card-primary">
                            <div class="mdui-card-primary-title">${item.name}</div>
                        </div>
                        <div class="mdui-card-content">${item.description} 主播: ${item.author}</div>
                    </div>
                </div>
            </div>
        </a>
        `).join('');
        listContainer.append(cards);
    }

    function resetButton(btn) {
        btn.prop('disabled', false).html('创建');
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

    // 鼠标悬停效果
    $('.avatar-container').hover(
        function () {
            $(this).find('.upload-overlay').show();
        },
        function () {
            $(this).find('.upload-overlay').hide();
        }
    );

    // 点击头像触发文件选择
    $('.avatar-container').on('click', function () {
        document.getElementById('avatar-upload').click();
    });

    // 文件选择后上传
    $('#avatar-upload').on('change', function (e) {
        var file = e.target.files[0];
        if (!file) return;

        // 验证文件类型
        if (!file.type.match('image.*')) {
            $('#user-panel-msg').html('<span class="mdui-color-red-a700">请选择图片文件</span>');
            return;
        }

        // 创建表单数据
        var formData = new FormData();
        formData.append('file', file);

        // 显示上传中状态
        $('#user-panel-msg').html('<span class="mdui-color-blue">正在上传头像...</span>');

        // 发送AJAX请求
        $.ajax({
            url: '/api/v1/files/upload',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                if (response.code === 200) {
                    response = response.data
                    // 更新隐藏字段
                    $('#avatar-path').val(response.path);

                    // 更新头像预览
                    var imgElement = $('.avatar-container img');
                    if (imgElement.length) {
                        imgElement.attr('src', response.path + '?t=' + Date.now());
                    }

                    $('#user-panel-msg').html('<span class="mdui-color-green">头像上传成功</span>');
                } else {
                    $('#user-panel-msg').html('<span class="mdui-color-red-a700">头像上传失败</span>');
                }
            },
            error: function () {
                $('#user-panel-msg').html('<span class="mdui-color-red-a700">网络错误，请重试</span>');
            }
        });
    });    

    // 表单提交功能
    $('#user-panel-btn').on('click', function () {
        // 验证表单
        var newPassword = $('input[name="newPassword"]').val();
        var confirmPassword = $('input[name="confirmPassword"]').val();

        if (newPassword || confirmPassword) {
            if (newPassword !== confirmPassword) {
                $('#user-panel-msg').html('<span class="mdui-color-red-a700">两次输入的新密码不一致</span>');
                return;
            }

            if (newPassword.length < 6) {
                $('#user-panel-msg').html('<span class="mdui-color-red-a700">新密码长度至少需要6个字符</span>');
                return;
            }
        }

        // 禁用按钮防止重复提交
        $(this).prop('disabled', true);

        // 发送表单数据
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

    $('#add-live-btn').on('click', async function () {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="mdui-icon material-icons">hourglass_empty</i> 创建中...');
        $('#add-live-msg').text('').removeClass('mdui-text-color-green');

        // 1. 获取表单数据
        const name = $("input[name='name']").val().trim();
        const videoSource = $("input[name='videoSource']").val().trim();
        const videoSourceType = $("input[name='videoSourceType']").val().trim();
        const coverImageInput = document.getElementById('coverImageInput');

        // 2. 验证输入
        if (!name) {
            $('#add-live-msg').text('直播间名称不能为空');
            return resetButton(btn);
        }

        if (videoSource) {
            try {
                const urlPattern = /^(https?:\/\/)?([^\s/?.#]+\.?)+(:\d+)?([/?][^\s]*)?$/i;
                if (!urlPattern.test(videoSource)) {
                    $('#add-live-msg').text('直播源URL无效');
                    return resetButton(btn);
                }
            } catch (error) {
                console.error("URL验证错误:", error);
            }
        }

        if (videoSource && !videoSourceType) {
            $('#add-live-msg').text('直播源类型不能为空');
            return resetButton(btn);
        }

        try {
            // 准备完整表单数据
            const formData = new FormData(document.getElementById('add-live-form'));

            if (coverImageInput.files.length) {
                const uploadFormData = new FormData();
                const progressContainer = $('<div class="mdui-progress"></div>');
                const progressBar = $('<div class="mdui-progress-determinate"></div>');
                progressContainer.append(progressBar);
                $('#livepic-upload-progress').html(progressContainer);
                uploadFormData.append('file', coverImageInput.files[0]);

                const uploadResponse = await $.ajax({
                    url: '/api/v1/files/upload',
                    type: 'POST',
                    data: uploadFormData,
                    processData: false,
                    contentType: false,
                    xhr: function () {
                        const xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function (evt) {
                            if (evt.lengthComputable) {
                                const percentComplete = Math.round((evt.loaded / evt.total) * 100);
                                progressBar.css('width', percentComplete + '%');
                                btn.html(`<i class="mdui-icon material-icons">cloud_upload</i> 上传中 ${percentComplete}%`);
                            }
                        }, false);
                        return xhr;
                    }
                });

                if (uploadResponse.code !== 200) {
                    throw new Error(uploadResponse.message || '封面图片上传失败');
                }
                formData.set('pic', `https://live.dfggmc.top/${uploadResponse.data.path}`); // 替换为服务器返回的路径
            }

            btn.html('<i class="mdui-icon material-icons">hourglass_empty</i> 创建中...');

            // 提交创建直播请求
            const createResponse = await $.ajax({
                url: '/api/v1/live/create',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            });

            if (createResponse.code === 200) {
                $('#add-live-msg').text('创建成功').removeClass('mdui-text-color-red').addClass('mdui-text-color-green');
                setTimeout(() => location.href = createResponse.data.id || '/', 1000);
            } else {
                throw new Error(createResponse.message || '创建失败');
            }
        } catch (error) {
            $('#add-live-msg').text(error.message || '请求失败');
            resetButton(btn);
            console.error(error);
        } finally {
            // 无论成功或失败都移除进度条
            $('.mdui-progress').remove();
        }
    });

    $(document).ready(function () {
        $('.copy-token-btn').on('click', function () {
            var token = $(this).data('token'); // 获取 data-token 属性
            var $textArea = $('<textarea>').val(token).appendTo('body'); // 创建一个 textarea 并将 token 设置为其值
            $textArea.select(); // 选择内容
            document.execCommand('copy'); // 执行复制
            $textArea.remove(); // 删除临时的 textarea
            alert('Token 已复制!'); // 弹出提示
        });

        document.querySelectorAll('.toggle-extra-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const extraData = this.closest('.token-item').querySelector('.extra-data');
                const icon = this.querySelector('.mdui-icon');

                if (extraData.style.display === 'none') {
                    extraData.style.display = 'block';
                    icon.textContent = 'expand_less';
                    this.setAttribute('title', '隐藏额外数据');
                } else {
                    extraData.style.display = 'none';
                    icon.textContent = 'expand_more';
                    this.setAttribute('title', '显示额外数据');
                }
            });
        });
    });

    $('#user-openapi-btn').on('click', function () {
        mdui.snackbar({
            message: '您确定要删除所有 Token 吗？',
            buttonText: '是的，我确定',
            onButtonClick: function () {
                $.ajax({
                    type: "POST",
                    url: "/api/v1/refresh",
                    dataType: "JSON",
                    success: function (response) {
                        if (response.code === 200) {
                            $('#api-token-display').text(response.data)
                            mdui.snackbar({ message: `成功！`, timeout: 2000 });
                            location.href = '/';
                        } else {
                            mdui.snackbar({ message: `失败！${response.message}`, timeout: 2000 });
                        }
                    },
                    error: function (xhr) {
                        mdui.snackbar({ message: `失败！${xhr.message}`, timeout: 2000 });
                    }
                });
            },
        });
    });

    $('#user-openapi-new-btn').click(function (e) {
        e.preventDefault();
        $.ajax({
            type: "POST",
            url: "/api/v1/refresh?method=refresh",
            dataType: "JSON",
            success: function (response) {
                if (response.code === 200) {
                    $('#api-token-display').text(response.data)
                    mdui.snackbar({ message: `成功！`, timeout: 2000 });
                    location.href = '/';
                } else {
                    mdui.snackbar({ message: `失败！${response.message}`, timeout: 2000 });
                }
            },
            error: function (xhr) {
                mdui.snackbar({ message: `失败！${xhr.message}`, timeout: 2000 });
            }
        });
    });
});