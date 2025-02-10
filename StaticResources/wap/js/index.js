const listContainer = $('#list ul');
let dataCache = [];
function renderList(data) {
    listContainer.empty();
    if (data.length === 0) {
        listContainer.html(`oh NO！未找到相关内容……`);
        return;
    }
    const cards = data.map(item => `
                <li class="mui-table-view-cell mui-media">
                    <a href="live?id=${item.id}">
                        <span class="mui-badge ${item.status == '' ? 'live' : 'mui-badge-success'}">${item.status == '未在直播' ? 'live' : '直播中'}</span>
                        <img class="mui-media-object mui-pull-left"
                            src="${item.pic || '/StaticResources/wap/img/Image_330346604143.png'}">
                        <div class="mui-media-body">
                            <p>${item.name} <span class="mui-badge">${item.authr}</span></p>
                            <p class='mui-ellipsis'>${item.description}</p>
                        </div>
                    </a>
                </li>
            `).join('');
    listContainer.append(cards);
}
/**
 * 加载
 * 
 */
function load() {
    $.ajax({
        url: 'https://live.dfggmc.top/api/v1/live/list',
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
        },
        complete: function () {
            $('#progressbar').hide();
            // 查找所有图片并为每个图片添加 onerror 事件
            $("img").on("error", function () {
                // 图片加载失败时触发的回调
                $(this).attr("src", "/StaticResources/wap/img/Image_330346604143.png"); // 替换为默认图片路径
            });
        }
    });
}
load()