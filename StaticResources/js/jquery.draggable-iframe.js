/**
 * jQuery Draggable Iframe 插件 - 适配手机端
 * 用法: jQuery(selector).draggableIframe(options);
 */
; (function ($, window, document, undefined) {
    // 默认配置
    const defaults = {
        url: 'about:blank',      // iframe URL
        title: '可拖动窗口',     // 窗口标题
        width: 400,              // 初始宽度
        height: 300,             // 初始高度
        x: 100,                  // 初始X位置
        y: 100,                  // 初始Y位置
        minWidth: 300,           // 最小宽度
        minHeight: 200,          // 最小高度
        themeColor: '#6200ee',   // 标题栏颜色
        allowClose: true,        // 是否显示关闭按钮
        allowResize: true,       // 是否允许调整大小
        mobileFullscreen: true,  // 在移动设备上是否全屏显示
        mobileBreakpoint: 768    // 移动设备断点(px)
    };

    // 插件构造函数
    function DraggableIframe(element, options) {
        this.element = jQuery(element);
        this.settings = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = 'draggableIframe';
        this.iframeCounter = 0;
        this.isOpen = false;
        this.isMobile = window.innerWidth <= this.settings.mobileBreakpoint;

        this.init();
    }

    // 方法定义
    $.extend(DraggableIframe.prototype, {
        init: function () {
            this.setupStyles();
            // 添加窗口大小改变事件监听
            $(window).on('resize', () => {
                this.handleResize();
            });
        },

        handleResize: function () {
            const newIsMobile = window.innerWidth <= this.settings.mobileBreakpoint;
            if (newIsMobile !== this.isMobile && this.isOpen) {
                this.isMobile = newIsMobile;
                this.updateLayoutForMobile();
            }
        },

        updateLayoutForMobile: function () {
            if (!this.iframeContainer) return;

            if (this.isMobile && this.settings.mobileFullscreen) {
                this.iframeContainer.css({
                    'width': '100%',
                    'height': '100%',
                    'left': '0',
                    'top': '0',
                    'min-width': '0',
                    'min-height': '0',
                    'resize': 'none'
                });
            } else {
                this.iframeContainer.css({
                    'width': this.settings.width + 'px',
                    'height': this.settings.height + 'px',
                    'left': this.settings.x + 'px',
                    'top': this.settings.y + 'px',
                    'min-width': this.settings.minWidth + 'px',
                    'min-height': this.settings.minHeight + 'px',
                    'resize': this.settings.allowResize ? 'both' : 'none'
                });
            }
        },

        setupStyles: function () {
            if (jQuery('#draggable-iframe-styles').length === 0) {
                const styles = `
                    .draggable-iframe-container {
                        position: fixed;
                        overflow: hidden;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                        z-index: 90000;
                        resize: both;
                        transform: translateZ(0); /* 启用硬件加速 */
                    }

                    .draggable-iframe-header {
                        height: 48px;
                        color: white;
                        display: flex;
                        align-items: center;
                        padding: 0 16px;
                        cursor: move;
                        user-select: none;
                        touch-action: none;
                        position: relative;
                    }

                    .draggable-iframe-content {
                        width: 100%;
                        height: calc(100% - 48px);
                        border: none;
                        background: white;
                    }

                    .close-btn {
                        margin-left: auto;
                        cursor: pointer;
                        right: 0;
                        position: absolute;
                        z-index: 1;
                    }

                    /* 移动设备样式 */
                    @media (max-width: ${this.settings.mobileBreakpoint}px) {
                        .draggable-iframe-container {
                            transition: transform 0.3s ease;
                        }
                        
                        .draggable-iframe-container.mobile-fullscreen {
                            width: 100% !important;
                            height: 100% !important;
                            left: 0 !important;
                            top: 0 !important;
                            min-width: 0 !important;
                            min-height: 0 !important;
                            resize: none !important;
                            border-radius: 0;
                        }
                    }

                    /* 调整大小手柄 */
                    .resize-handle {
                        position: absolute;
                        width: 20px;
                        height: 20px;
                        right: 0;
                        bottom: 0;
                        background: transparent;
                        cursor: se-resize;
                        z-index: 10;
                    }
                `;
                jQuery('<style id="draggable-iframe-styles">').text(styles).appendTo('head');
            }
        },

        createIframe: function () {
            if (this.isOpen) return;

            this.iframeCounter++;
            const iframeId = 'draggable-iframe-' + this.iframeCounter;

            // 创建容器
            const iframeContainer = jQuery('<div>', {
                'id': iframeId,
                'class': 'draggable-iframe-container' +
                    (this.isMobile && this.settings.mobileFullscreen ? ' mobile-fullscreen' : ''),
                'css': {
                    'width': (this.isMobile && this.settings.mobileFullscreen) ? '100%' : this.settings.width + 'px',
                    'height': (this.isMobile && this.settings.mobileFullscreen) ? '100%' : this.settings.height + 'px',
                    'left': (this.isMobile && this.settings.mobileFullscreen) ? '0' : this.settings.x + 'px',
                    'top': (this.isMobile && this.settings.mobileFullscreen) ? '0' : this.settings.y + 'px',
                    'min-width': (this.isMobile && this.settings.mobileFullscreen) ? '0' : this.settings.minWidth + 'px',
                    'min-height': (this.isMobile && this.settings.mobileFullscreen) ? '0' : this.settings.minHeight + 'px',
                    'display': 'block',
                    'background-color': this.settings.themeColor,
                    'resize': (this.isMobile || !this.settings.allowResize) ? 'none' : 'both'
                }
            });

            // 创建标题栏
            const header = jQuery('<div>', {
                'class': 'draggable-iframe-header',
                'css': {
                    'background-color': this.settings.themeColor
                }
            }).append(
                jQuery('<span>', {
                    'class': 'iframe-title',
                    'text': this.settings.title
                })
            );

            // 添加关闭按钮
            if (this.settings.allowClose) {
                header.append(
                    jQuery('<span>', {
                        'class': 'close-btn mdui-btn-icon',
                        'html': '<i class="mdui-icon material-icons">close</i>'
                    }).click(() => {
                        this.close();
                    })
                );
            }

            // 创建 iframe
            const iframe = jQuery('<iframe>', {
                'class': 'draggable-iframe-content',
                'src': this.settings.url,
                'allow': 'fullscreen' // 允许全屏
            });

            // 添加调整大小手柄
            if (this.settings.allowResize && !this.isMobile) {
                iframeContainer.append(
                    jQuery('<div>', {
                        'class': 'resize-handle'
                    })
                );
            }

            // 组装元素
            iframeContainer.append(header).append(iframe);

            // 添加到DOM
            this.element.append(iframeContainer);

            // 初始化拖动功能
            this.initDraggable(iframeContainer);

            // 初始化调整大小功能
            if (this.settings.allowResize && !this.isMobile) {
                this.initResizable(iframeContainer);
            }

            // 存储引用
            this.iframeContainer = iframeContainer;
            this.iframe = iframe;
            this.isOpen = true;
        },

        initDraggable: function (element) {
            const header = element.find('.draggable-iframe-header');
            let isDragging = false;
            let offsetX, offsetY;

            // 鼠标/触摸开始事件
            const startDrag = (e) => {
                if (this.isMobile && this.settings.mobileFullscreen) return;

                isDragging = true;
                const clientX = e.clientX || e.touches[0].clientX;
                const clientY = e.clientY || e.touches[0].clientY;
                offsetX = clientX - element.offset().left;
                offsetY = clientY - element.offset().top;
                e.preventDefault();

                // 为移动设备添加活动状态样式
                if (e.touches) {
                    element.addClass('dragging');
                }
            };

            // 鼠标/触摸移动事件
            const drag = (e) => {
                if (!isDragging) return;

                const clientX = e.clientX || (e.touches && e.touches[0].clientX);
                const clientY = e.clientY || (e.touches && e.touches[0].clientY);

                if (clientX === undefined || clientY === undefined) return;

                element.css({
                    'left': (clientX - offsetX) + 'px',
                    'top': (clientY - offsetY) + 'px'
                });

                e.preventDefault();
            };

            // 鼠标/触摸结束事件
            const endDrag = () => {
                isDragging = false;
                element.removeClass('dragging');
            };

            // 绑定事件
            header.on('mousedown touchstart', startDrag);
            $(document).on('mousemove touchmove', drag);
            $(document).on('mouseup touchend', endDrag);

            // 防止拖动时选中文本
            header.on('selectstart', () => false);
        },

        initResizable: function (element) {
            const handle = element.find('.resize-handle');
            let isResizing = false;
            let startX, startY, startWidth, startHeight;

            // 鼠标/触摸开始事件
            const startResize = (e) => {
                isResizing = true;
                startX = e.clientX || e.touches[0].clientX;
                startY = e.clientY || e.touches[0].clientY;
                startWidth = parseInt(element.width(), 10);
                startHeight = parseInt(element.height(), 10);
                e.preventDefault();
                e.stopPropagation();
            };

            // 鼠标/触摸移动事件
            const resize = (e) => {
                if (!isResizing) return;

                const clientX = e.clientX || (e.touches && e.touches[0].clientX);
                const clientY = e.clientY || (e.touches && e.touches[0].clientY);

                if (clientX === undefined || clientY === undefined) return;

                const newWidth = startWidth + (clientX - startX);
                const newHeight = startHeight + (clientY - startY);

                element.css({
                    'width': Math.max(this.settings.minWidth, newWidth) + 'px',
                    'height': Math.max(this.settings.minHeight, newHeight) + 'px'
                });

                e.preventDefault();
            };

            // 鼠标/触摸结束事件
            const endResize = () => {
                isResizing = false;
            };

            // 绑定事件
            handle.on('mousedown touchstart', startResize.bind(this));
            $(document).on('mousemove touchmove', resize.bind(this));
            $(document).on('mouseup touchend', endResize);
        },

        // 公开方法：更新 iframe URL
        updateUrl: function (url) {
            if (this.isOpen) {
                this.iframe.attr('src', url);
            }
            return this;
        },

        // 公开方法：更新标题
        updateTitle: function (title) {
            if (this.isOpen) {
                this.iframeContainer.find('.iframe-title').text(title);
            }
            return this;
        },

        // 公开方法：关闭 iframe
        close: function () {
            if (this.isOpen && this.iframeContainer) {
                this.iframeContainer.remove();
                this.isOpen = false;
            }
            return this;
        },

        // 公开方法：打开 iframe
        open: function () {
            if (!this.isOpen) {
                this.createIframe();
            }
            return this;
        },

        // 公开方法：获取 iframe DOM 元素
        getIframe: function () {
            return this.isOpen ? this.iframe[0] : null;
        },

        // 公开方法：检查是否移动设备
        isMobileDevice: function () {
            return this.isMobile;
        }
    });

    // jQuery 插件注册
    $.fn.draggableIframe = function (options) {
        var instance = null;
        this.each(function () {
            if (!$.data(this, 'plugin_draggableIframe')) {
                instance = new DraggableIframe(this, options);
                $.data(this, 'plugin_draggableIframe', instance);
            } else {
                instance = $.data(this, 'plugin_draggableIframe');
            }
        });
        return instance || this;  // 返回实例以便链式调用
    };

})(jQuery, window, document);