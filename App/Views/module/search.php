<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>综合搜索</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/mdui/1.0.2/css/mdui.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
        }

        .mdui-dialog-content {
            position: fixed;
            height: 100%;
            width: 100%;
            background-color: #1e1e1e;
            color: #ffffff;
        }

        #options {
            position: fixed;
            top: 0;
            width: 95%;
            z-index: 99999;
            background-color: #1e1e1e;
        }

        #content {
            padding-top: 100px;
        }

        .mdui-textfield-input {
            background-color: #1e1e1e;
            color: #ffffff;
            border: 1px solid #333333;
        }

        .mdui-textfield-helper {
            color: #aaaaaa;
        }

        .mdui-btn {
            background-color: #333333;
            color: #ffffff;
        }

        .mdui-btn.mdui-color-theme-accent {
            background-color: #bb86fc;
            color: #ffffff;
        }

        .mdui-tab {
            background-color: #1e1e1e;
            color: #ffffff;
        }

        .mdui-tab a {
            color: #aaaaaa;
        }

        .mdui-tab a.mdui-tab-active {
            color: #bb86fc;
        }

        .result-card {
            background-color: #1e1e1e;
            color: #ffffff;
            border: 1px solid #333333;
            margin-bottom: 5px;
        }

        .pagination button {
            background-color: #333333;
            color: #ffffff;
        }

        .pagination button.mdui-color-theme {
            background-color: #bb86fc;
            color: #ffffff;
        }

        .empty-state {
            color: #aaaaaa;
        }

        .mdui-spinner-colorful {
            border-color: #bb86fc;
        }


        .mdui-dialog-content {
            padding: 16px;
        }

        #input {
            display: flex;
        }

        #input button {
            margin-left: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 16px;
        }

        .live-cover {
            height: 120px;
            object-fit: cover;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 24px;
        }

        .empty-state,
        #loading {
            text-align: center;
            padding: 40px 0;
        }

        .tab-content {
            padding: 16px 0;
        }

        .mdui-typo-headline {
            display: flex;
        }

        .user-card {
            display: flex;
            align-items: center;
            padding: 16px;
            margin-bottom: 16px;
        }

        .user-info {
            flex-grow: 1;
        }

        .user-meta {
            font-size: 0.875rem;
            color: var(--secondary-text);
            margin-top: 4px;
        }
    </style>
</head>

<body class="mdui-theme-primary-indigo mdui-theme-accent-pink">
    <div class="mdui-dialog-content">
        <div id="options">
            <div class="mdui-textfield" id="input">
                <input class="mdui-textfield-input" type="text" id="keyword" />
                <div class="mdui-textfield-helper">
                    输入用户名、邮箱或直播标题进行搜索
                </div>
                <button class="mdui-btn mdui-btn-raised mdui-ripple mdui-color-theme-accent" id="search-btn">
                    <i class="mdui-icon material-icons">search</i> 搜索
                </button>
            </div>
            <div class="mdui-tab mdui-tab-scrollable" id="result-tabs">
                <a href="#lives-tab" class="mdui-ripple mdui-tab-active">直播</a>
                <a href="#users-tab" class="mdui-ripple">用户</a>
            </div>
        </div>

        <div id="content">
            <div id="loading" class="mdui-center mdui-hidden">
                <div class="mdui-spinner mdui-spinner-colorful"></div>
                <div>
                    正在搜索中...
                </div>
            </div>
            <div class="tab-content">
                <div id="users-tab">
                    <div id="users-result"></div>
                </div>
                <div id="lives-tab">
                    <div id="lives-result"></div>
                </div>
            </div>
            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <script src="https://lf3-cdn-tos.bytecdntp.com/cdn/expire-1-M/mdui/1.0.2/js/mdui.min.js"></script>
    <script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-1-M/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function () {
            // 当前页码
            let currentPage = 1;
            const perPage = 20;
            let currentKeyword = '';
            let searchTimer = null;

            // 初始化标签页
            const tabs = new mdui.Tab('#result-tabs');

            // 搜索按钮点击事件
            $('#search-btn').on('click', function () {
                performSearch();
            });
            // 回车键搜索
            $('#keyword').on('keypress', function (e) {
                if (e.which === 13) {
                    performSearch();
                }
            });
            // 执行搜索
            function performSearch() {
                const keyword = $('#keyword').val().trim();
                if (!keyword) {
                    mdui.snackbar({
                        message: '请输入搜索关键词',
                        position: 'right-top'
                    });
                    return;
                }
                // 防抖处理
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    currentKeyword = keyword;
                    currentPage = 1;
                    search(keyword, currentPage);
                }, 300);
            }

            // 搜索函数
            function search(keyword, page) {
                $('#loading').removeClass('mdui-hidden');
                $('#users-result').empty();
                $('#lives-result').empty();
                $('#pagination').empty();

                $.ajax({
                    url: '/api/v1/search/global',
                    method: 'GET',
                    data: {
                        keyword: keyword,
                        page: page,
                        per_page: perPage
                    },
                    dataType: 'json',
                    success: function (response) {
                        $('#loading').addClass('mdui-hidden');
                        const data = response.data;
                        // 显示用户结果
                        displayUsers(data.users || {
                            data: [], total: 0
                        });
                        // 显示直播结果
                        displayLives(data.live_list || {
                            data: [], total: 0
                        });
                        // 显示分页
                        displayPagination(data.page || 1, data.per_page || perPage,
                            (data.users?.total || 0) + (data.live_list?.total || 0));
                        mdui.mutation();
                    },
                    error: function (xhr, status, error) {
                        $('#loading').addClass('mdui-hidden');
                        let errorMsg = '搜索失败: ';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg += xhr.responseJSON.message;
                        } else {
                            errorMsg += error || status;
                        }

                        mdui.snackbar({
                            message: errorMsg,
                            position: 'right-top'
                        });
                        console.error('搜索错误:', error);
                    }
                });
            }

            // 显示用户搜索结果
            function displayUsers(usersData) {
                const container = $('#users-result');
                const users = usersData.data || [];
                const total = usersData.total || 0;
                if (users.length === 0) {
                    container.html(`
						<div class="empty-state">
                            <i class="mdui-icon material-icons">search</i>
                            <div>没有找到相关用户</div>
						</div>
						`);
                    return;
                }
                $.each(users, function (index, user) {
                    const createdAt = user.created_at ? new Date(user.created_at).toLocaleString() : '未知时间';
                    const card = $(`
                        <div class="result-card user-card">
                            <img src="${user.avatar}" alt="用户头像" class="user-avatar" onerror="this.src='/StaticResources/image/default-avatar.png'">
                            <div class="user-info">
                                <div class="mdui-typo-headline">${user.username}</div>
                                <div class="user-meta">
                                    <div>ID: ${user.user_id} • ${getGroupName(user.group_id)}</div>
                                    <div>注册时间: ${createdAt}</div>
                                </div>
                            </div>
                        </div>`);
                    container.append(card);
                });
            }

            // 显示直播搜索结果
            function displayLives(livesData) {
                const container = $('#lives-result');
                const lives = livesData.data || [];
                const total = livesData.total || 0;
                if (lives.length === 0) {
                    container.html(`
                    <div class="empty-state">
                        <i class="mdui-icon material-icons">search</i>
                        <div>没有找到相关直播</div>
                    </div>`);
                    return;
                }
                // 创建网格布局
                const grid = $('<div class="mdui-grid-list"></div>');
                $.each(lives, function (index, live) {
                    const cell = $(`
                    <div class="mdui-col-xs-12 mdui-col-sm-6 mdui-col-md-4">
                        <div class="mdui-card result-card">
                            <div class="mdui-card-media">
                                <img class="live-cover" src="${live.pic || '/StaticResources/image/Image_330346604143.png'}" onerror="this.src='/StaticResources/image/Image_330346604143.png>
                                <div class="mdui-card-media-covered">
                                    <div class="mdui-card-primary">
                                        <div class="mdui-card-primary-title">${live.name}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mdui-card-content">
                                ${live.description || '暂无描述'}
                            </div>
                            <div class="mdui-card-actions">
                                <button class="mdui-btn mdui-ripple" onclick="window.open('/${live.id}', '_blank')">查看直播</button>
                            </div>
                        </div>
                    </div>`);
                    grid.append(cell);
                });
                container.append(grid);
            }

            // 显示分页
            function displayPagination(page, perPage, total) {
                const totalPages = Math.ceil(total / perPage);
                if (totalPages <= 1) return;

                const pagination = $('#pagination');

                // 上一页按钮
                const prevBtn = $('<button class="mdui-btn mdui-ripple"></button>')
                    .html('<i class="mdui-icon material-icons">chevron_left</i>')
                    .prop('disabled', page === 1)
                    .on('click', function () {
                        if (page > 1) {
                            currentPage--;
                            search(currentKeyword, currentPage);
                        }
                    });
                pagination.append(prevBtn);

                // 页码按钮
                for (let i = 1; i <= totalPages; i++) {
                    const pageBtn = $(`<button class="mdui-btn mdui-ripple ${i === page ? 'mdui-color-theme' : ''}"></button>`)
                        .text(i)
                        .on('click', function () {
                            currentPage = i;
                            search(currentKeyword, currentPage);
                        });
                    pagination.append(pageBtn);
                }

                // 下一页按钮
                const nextBtn = $('<button class="mdui-btn mdui-ripple"></button>')
                    .html('<i class="mdui-icon material-icons">chevron_right</i>')
                    .prop('disabled', page === totalPages)
                    .on('click', function () {
                        if (page < totalPages) {
                            currentPage++;
                            search(currentKeyword, currentPage);
                        }
                    });
                pagination.append(nextBtn);
            }

            // 用户组名称映射
            function getGroupName(groupId) {
                const groups = {
                    1: '管理员',
                    2: '普通用户',
                };
                return groups[groupId] || `组别${groupId}`;
            }
        });
    </script>
</body>

</html>