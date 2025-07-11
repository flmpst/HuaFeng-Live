// 初始化代码高亮
$(document).ready(function () {
    $('pre code').each(function (i, el) {
        hljs.highlightElement(el);
    });

    // 生成目录
    generateTOC();

    // 初始化搜索功能
    initSearch();
});

/**
 * 自动生成带层级结构的目录
 * 支持 h1-h4 标题，自动创建嵌套列表结构
 */
function generateTOC() {
    const headings = $('h1, h2, h3, h4');
    const tocContainer = $('#toc-list');

    // 清除现有内容
    tocContainer.empty();

    // 初始化堆栈和当前层级
    let listStack = [tocContainer];
    let currentLevel = 1;

    headings.each(function (index, element) { 
        const heading = $(element);

        // 为标题添加ID（如果没有）
        if (!heading.attr('id')) {
            heading.attr('id', 'section-' + index);
        }

        const level = parseInt(heading.prop('tagName').substring(1));

        // 处理层级变化
        if (level > currentLevel) {
            // 向下层级移动 - 创建新的嵌套列表
            for (let i = currentLevel; i < level; i++) {
                const newList = $('<div/>');
                const newListInner = $('<div/>');
                newList.append(newListInner);
                listStack[listStack.length - 1].append(newList);
                listStack.push(newListInner);
            }
        } else if (level < currentLevel) {
            // 向上层级移动 - 弹出堆栈
            for (let i = level; i < currentLevel; i++) {
                listStack.pop();
            }
        }

        currentLevel = level;

        // 创建目录项
        const listItem = $('<a/>', {
            'class': 'mdui-list-item mdui-ripple toc-level-' + level,
            'href': '#' + heading.attr('id'),
            'html': `<div class="mdui-list-item-content">` + heading.text() + `</div>`
        });

        // 添加点击平滑滚动
        listItem.on('click', function (e) {
            e.preventDefault();
            const target = $('#' + heading.attr('id'));
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 20
                }, 300);
                // 更新URL哈希而不触发跳转
                history.pushState(null, null, '#' + heading.attr('id'));
            }
        });

        // 添加当前项
        listStack[listStack.length - 1].append(listItem);
    });
}

// 添加样式增强
function addTOCStyles() {
    $('<style>').text(`
        .toc-level-1 { padding-left: 0px; font-weight: bold; }
        .toc-level-2 { padding-left: 16px; }
        .toc-level-3 { padding-left: 32px; font-size: 0.9em; }
        .toc-level-4 { padding-left: 48px; font-size: 0.85em; }
        #toc-list .mdui-list-item { border-left: 3px solid transparent; }
        #toc-list .mdui-list-item:hover { border-left-color: #2196F3; }
    `).appendTo('head');
}

// 文档加载完成后执行
$(function () {
    addTOCStyles();
    generateTOC();

    // 高亮当前可见的目录项
    $(window).on('scroll', highlightVisibleTOCItem);
});

// 高亮当前可见部分的目录项
function highlightVisibleTOCItem() {
    const headings = $('h1, h2, h3, h4');
    const tocItems = $('#toc-list .mdui-list-item');

    let currentActive = null;

    headings.each(function (index, heading) {
        const rect = heading.getBoundingClientRect();
        if (rect.top <= window.innerHeight * 0.2 && rect.bottom >= 0) {
            currentActive = index;
        }
    });

    tocItems.each(function (index, item) {
        $(item).toggleClass('mdui-list-item-active', index === currentActive);
    });
}

// 处理Markdown中的HTTP方法标签
$('code').each(function () {
    const text = $(this).text().trim();
    if (text === 'GET' || text === 'POST' || text === 'PUT' || text === 'DELETE') {
        $(this).addClass('method-' + text.toLowerCase());
    }
});

// 初始化搜索功能 - 仅显示匹配的节
function initSearch() {
    const searchInput = $('#search-input');
    const contentContainer = $('#content-container');
    const resultsCount = $('#search-results-count');

    // 检查必要的元素是否存在
    if (!searchInput.length || !contentContainer.length) {
        console.error('搜索功能初始化失败：缺少必要的DOM元素');
        return;
    }

    // 存储原始内容以便重置搜索
    const originalContent = contentContainer.html();
    let originalDOM;

    try {
        originalDOM = $('<div>').append(originalContent);
    } catch (e) {
        console.error('解析原始内容失败:', e);
        return;
    }

    searchInput.on('input', function () {
        const searchTerm = $(this).val().trim().toLowerCase();

        if (searchTerm === '') {
            // 重置内容
            contentContainer.html(originalContent);

            // 重新高亮代码
            $('pre code').each(function (i, el) {
                hljs.highlightElement(el);
            });

            // 重新生成目录
            generateTOC();

            resultsCount.text('');
            return;
        }

        // 创建DOM副本用于搜索
        const searchDOM = $(originalDOM).clone();
        let matchCount = 0;

        try {
            // 1. 首先隐藏所有内容
            searchDOM.children().hide();

            // 2. 查找包含搜索词的文本节点
            searchDOM.find('*').contents().filter(function () {
                return this.nodeType === 3 && $(this).text().toLowerCase().includes(searchTerm);
            }).each(function () {
                const textNode = $(this);

                // 3. 显示包含匹配文本的最近节结构
                let section = textNode.closest('h1, h2, h3, h4, h5, h6').parent();
                if (section.length === 0) {
                    section = textNode.closest('div, p, li, pre, code, table');
                }

                // 显示匹配的节及其父级结构
                section.show();
                section.parents().show();

                // 4. 高亮匹配的文本
                const regex = new RegExp(searchTerm.replace(/[.*+?^{}()|[\]\\]/g, '\\&'), 'gi');
                textNode.replaceWith(textNode.text().replace(regex, match => `<span class="search-highlight">${match}</span>`));

                matchCount++;
            });

            // 5. 确保所有标题可见（即使它们本身不匹配，但包含匹配内容）
            searchDOM.find('h1, h2, h3, h4, h5, h6').each(function () {
                const heading = $(this);
                if (heading.nextUntil('h1, h2, h3, h4, h5, h6').filter(':visible').length > 0) {
                    heading.show();
                }
            });

            // 更新内容
            contentContainer.html(searchDOM.html());

            resultsCount.text(matchCount > 0 ? `找到 ${matchCount} 个匹配项` : '');

            // 重新高亮代码块
            $('pre code').each(function (i, el) {
                hljs.highlightElement(el);
            });

            // 如果没有结果，显示提示
            if (matchCount === 0) {
                contentContainer.html(`<div class="no-results">没有找到匹配 "${searchTerm}" 的内容 <br> <img src="/StaticResources/image/NOData.jpg"></div>`);
            }

        } catch (error) {
            console.error('搜索过程中出错:', error);
            contentContainer.html(originalContent);
            resultsCount.text('');
        }
    });

    // 添加搜索快捷键 (Ctrl+F / Cmd+F)
    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            searchInput.focus();
        }
    });
}
// 初始化MDUI组件
mdui.mutation();