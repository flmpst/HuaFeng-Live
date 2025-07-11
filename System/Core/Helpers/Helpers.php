<?php

namespace XQPF\Core\Helpers;

/**
 * 未分类辅助
 */
class Helpers
{
    /**
     * 生成JSON响应(内置exit)
     *
     * @param int $statusCode HTTP状态码
     * @param $message 响应消息
     * @param array $data 返回数据数组
     * @return string JSON格式的响应
     */
    public function jsonResponse(int $statusCode, $message, array $data = []): string
    {
        header('Content-Type: application/json;charset=utf-8');
        // 构建JSON响应数据
        $response = [
            'APIVersion' => '1.3.0.0',
            'code' => $statusCode,
            'message' => $message,
            'data' => $data
        ];
        if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
            $response['data']['apiDebug'] = [
                'AppVersion' => FRAMEWORK_VERSION,
                'backtrace' => debug_backtrace(),
            ];
        }
        exit(json_encode($response, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 输出调试信息到页面
     *
     * 仅在开发模式下（FRAMEWORK_DEBUG为true）显示调试栏。
     *
     * @return void
     */
    public function debugBar()
    {
        if (!defined('FRAMEWORK_DEBUG') || !FRAMEWORK_DEBUG) {
            return;
        }

        $executionTime = microtime(true) - ($_SERVER["REQUEST_TIME_FLOAT"] ?? microtime(true));
        $memoryUsage = memory_get_usage();
        $memoryPeakUsage = memory_get_peak_usage();
        $includedFiles = get_included_files();
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        // 数据库查询日志
        $dbQueries = [];
        $totalQueryTime = 0;
        $queryLogFile = defined('FRAMEWORK_DIR') ? FRAMEWORK_DIR . '/Writable/logs/db_queries.log' : null;
        if ($queryLogFile && is_file($queryLogFile)) {
            foreach (array_reverse(file($queryLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) as $line) {
                $queryData = @unserialize($line);
                if (is_array($queryData)) {
                    $dbQueries[] = [
                        'sql' => $queryData['sql'] ?? '',
                        'params' => $queryData['params'] ?? [],
                        'time' => $queryData['time'] ?? 0,
                        'caller' => $queryData['caller'] ?? ''
                    ];
                    $totalQueryTime += $queryData['time'] ?? 0;
                }
            }
        }

        // 环境信息
        $phpVersion = PHP_VERSION;
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? php_sapi_name();
        $user = get_current_user();
        $date = date('Y-m-d H:i:s');
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $frameworkVersion = defined('FRAMEWORK_VERSION') ? FRAMEWORK_VERSION : 'N/A';

        // 输出样式和结构
        echo <<<HTML
<style>
#debug-bar{position:fixed;left:0;width:100%;background:#1a1a1a;color:#e0e0e0;padding:5px 10px;z-index:9999;display:flex;justify-content:space-between;align-items:center;font-family:'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;font-size:13px;box-shadow:0 -2px 10px rgba(0,0,0,0.3);border-top:1px solid #333;transition:all 0.3s ease}
#debug-bar.bottom{bottom:0}
#debug-bar.top{top:0;border-top:none;border-bottom:1px solid #333}
.debug-info{display:flex;gap:15px;overflow-x:auto;padding:3px 0;scrollbar-width:thin}
.debug-info::-webkit-scrollbar{height:3px}.debug-info::-webkit-scrollbar-thumb{background:#555}
.debug-info span{white-space:nowrap;display:flex;align-items:center;gap:4px}
.debug-info strong{color:#a0a0a0;font-weight:500}
.debug-actions{display:flex;gap:8px;margin-left:10px}
.debug-actions button{background:#333;color:#e0e0e0;border:none;padding:4px 10px;margin-right:20px;border-radius:3px;cursor:pointer;font-size:12px;transition:all 0.2s;display:flex;align-items:center;gap:4px}
.debug-actions button:hover{background:#444}
.debug-actions button i{font-size:12px}
#debug-tabs{display:none;position:absolute;left:0;width:100%;background:#252525;color:#e0e0e0;max-height:60vh;overflow-y:scroll;box-shadow:0 -2px 10px rgba(0,0,0,0.3)}
#debug-bar.bottom #debug-tabs{bottom:100%}
#debug-bar.top #debug-tabs{top:100%}
.tab-header{display:flex;background:#1e1e1e;border-bottom:1px solid #333;overflow-x:auto;position:sticky;top:0;z-index:10}
.tab-header::-webkit-scrollbar{height:3px}.tab-header::-webkit-scrollbar-thumb{background:#555}
.tab-header button{background:transparent;border:none;color:#a0a0a0;padding:8px 15px;cursor:pointer;white-space:nowrap;font-size:12px;transition:all 0.2s;border-right:1px solid #333;display:flex;align-items:center;gap:5px}
.tab-header button:last-child{border-right:none}
.tab-header button:hover{color:#e0e0e0;background:#333}
.tab-header button.active{color:#fff;background:#333;font-weight:500}
.tab-content{overflow-y:auto;max-height:calc(60vh - 40px)}
.tab-panel{display:none;padding:10px}
.tab-panel.active{display:block}
.backtrace-item{margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid #333}
.backtrace-item:last-child{border-bottom:none}
.query-info{margin-bottom:15px;padding-bottom:15px;border-bottom:1px solid #333}
.query-info:last-child{border-bottom:none}
.query-sql{color:#4CAF50;margin:5px 0}
.query-time{color:#FF9800;font-size:12px}
.query-params{color:#2196F3;margin:5px 0;font-size:12px}
.query-caller{color:#9C27B0;font-size:11px;margin-top:5px;opacity:0.8}
pre{margin:0;white-space:pre-wrap;word-break:break-word;font-family:'Consolas','Monaco','Courier New',monospace;font-size:12px;line-height:1.4}
.env-table{width:100%;border-collapse:collapse;margin-bottom:10px}
.env-table td,.env-table th{border:1px solid #333;padding:4px 8px;font-size:12px;color:#a0a0a0;}
.env-table th{background:#222;color:#a0a0a0;text-align:left}
.superglobal-table{width:100%;border-collapse:collapse;margin-bottom:10px}
.superglobal-table td,.superglobal-table th{border:1px solid #333;padding:4px 8px;font-size:12px;color:#a0a0a0;}
.superglobal-table th{background:#222;color:#a0a0a0;text-align:left}
@media (max-width:768px){.debug-info{gap:8px}.debug-info span{flex-direction:column;align-items:flex-start;gap:0}.debug-actions{gap:5px}}
</style>
<div id="debug-bar" class="bottom">
    <div class="debug-info">
        <span><strong>Time:</strong> {$this->formatNumber($executionTime, 4)}s</span>
        <span><strong>Memory:</strong> {$this->formatNumber($memoryUsage / 1024, 2)}KB</span>
        <span><strong>Peak:</strong> {$this->formatNumber($memoryPeakUsage / 1024, 2)}KB</span>
        <span><strong>PHP:</strong> {$phpVersion}</span>
        <span><strong>XQPF Framework Version:</strong> {$frameworkVersion}</span>
        <span><strong>Req:</strong> {$method} {$requestUri}</span>
        <span><strong>IP:</strong> {$ip}</span>
HTML;
        if ($dbQueries) {
            echo "<span><strong>DB:</strong> " . count($dbQueries) . "</span>
            <span><strong>DB Time:</strong> " . $this->formatNumber($totalQueryTime * 1000, 2) . "ms</span>";
        }
        echo <<<HTML
    </div>
    <div class="debug-actions">
        <button onclick="togglePosition()" title="切换位置">⇅</button>
        <button onclick="toggleTabs()" title="显示/隐藏调试详情">🐞 Debug</button>
        <button onclick="copyDebugInfo()" title="复制调试信息">📋 Copy</button>
    </div>
    <div id="debug-tabs">
        <div class="tab-header">
            <button class="active" onclick="switchTab(event, 'env-info')">环境</button>
            <button onclick="switchTab(event, 'backtrace')">调用栈</button>
            <button onclick="switchTab(event, 'included-files')">文件</button>
            <button onclick="switchTab(event, 'db-queries')">SQL</button>
            <button onclick="switchTab(event, 'superglobals')">全局变量</button>
        </div>
        <div id="env-info" class="tab-panel active">
            <table class="env-table">
                <tr><th>时间</th><td>{$date}</td></tr>
                <tr><th>PHP</th><td>{$phpVersion}</td></tr>
                <tr><th>框架</th><td>{$frameworkVersion}</td></tr>
                <tr><th>服务器</th><td>{$serverSoftware}</td></tr>
                <tr><th>用户</th><td>{$user}</td></tr>
                <tr><th>请求</th><td>{$method} {$requestUri}</td></tr>
                <tr><th>IP</th><td>{$ip}</td></tr>
                <tr><th>执行时间</th><td>{$this->formatNumber($executionTime, 4)}s</td></tr>
                <tr><th>内存</th><td>{$this->formatNumber($memoryUsage / 1024, 2)}KB (峰值: {$this->formatNumber($memoryPeakUsage / 1024, 2)}KB)</td></tr>
            </table>
        </div>
        <div id="backtrace" class="tab-panel">
            <pre>
HTML;
        foreach ($backtrace as $i => $trace) {
            echo "<div class='backtrace-item'>";
            echo "#{$i} ";
            echo "File: " . ($trace['file'] ?? '') . "\n";
            echo "Line: " . ($trace['line'] ?? '') . "\n";
            echo "Function: " . ($trace['function'] ?? '') . "\n";
            echo "Class: " . ($trace['class'] ?? '') . "\n";
            echo "Type: " . ($trace['type'] ?? '') . "\n";
            if (!empty($trace['args'])) {
                echo "Args: " . htmlspecialchars(print_r($trace['args'], true)) . "\n";
            }
            echo "</div>";
        }
        echo <<<HTML
            </pre>
        </div>
        <div id="included-files" class="tab-panel">
            <pre>
HTML;
        echo "Conut: " . count($includedFiles) . "\n";
        echo "Files:\n";
        foreach ($includedFiles as $file) {
            echo htmlspecialchars($file) . "\n";
        }
        echo <<<HTML
            </pre>
        </div>
        <div id="db-queries" class="tab-panel">
HTML;
        if ($dbQueries) {
            echo "<div style='padding-bottom: 10px;'>";
            echo "<div><strong>Total Queries:</strong> " . count($dbQueries) . "</div>";
            echo "<div><strong>Total Query Time:</strong> " . $this->formatNumber($totalQueryTime * 1000, 2) . "ms</div>";
            echo "</div>";
            foreach ($dbQueries as $index => $query) {
                echo "<div class='query-info'>";
                echo "<div><strong>Query #" . ($index + 1) . "</strong></div>";
                echo "<div class='query-time'>Time: " . $this->formatNumber($query['time'] * 1000, 2) . "ms</div>";
                echo "<div class='query-sql'><pre>" . htmlspecialchars($query['sql']) . "</pre></div>";
                if (!empty($query['params'])) {
                    echo "<div class='query-params'>Params: <pre>" . htmlspecialchars(print_r($query['params'], true)) . "</pre></div>";
                }
                echo "<div class='query-caller'>Called from: " . htmlspecialchars($query['caller']) . "</div>";
                echo "</div>";
            }
        } else {
            echo "<div style='padding:10px'>无数据库查询记录。</div>";
        }
        echo <<<HTML
        </div>
        <div id="superglobals" class="tab-panel">
            <table class="superglobal-table">
HTML;
        foreach (['_GET', '_POST', '_COOKIE', '_SESSION', '_FILES', '_SERVER'] as $sg) {
            $arr = $GLOBALS[$sg] ?? [];
            echo "<tr><th>\${$sg}</th><td><pre>" . htmlspecialchars(print_r($arr, true)) . "</pre></td></tr>";
        }
        echo <<<HTML
            </table>
        </div>
    </div>
</div>
<script>
function togglePosition() {
    const debugBar = document.getElementById('debug-bar');
    debugBar.classList.toggle('bottom');
    debugBar.classList.toggle('top');
}
function toggleTabs() {
    const debugTabs = document.getElementById('debug-tabs');
    debugTabs.style.display = (debugTabs.style.display === 'none' || !debugTabs.style.display) ? 'block' : 'none';
}
function switchTab(event, tabId) {
    document.querySelectorAll('#debug-tabs .tab-header button').forEach(btn => btn.classList.remove('active'));
    event.currentTarget.classList.add('active');
    document.querySelectorAll('#debug-tabs .tab-panel').forEach(panel => panel.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
}
function copyDebugInfo() {
    let text = '';
    text += '[环境信息]\\n';
    document.querySelectorAll('#env-info .env-table tr').forEach(tr => {
        text += tr.children[0].innerText + ': ' + tr.children[1].innerText + '\\n';
    });
    text += '\\n[调用栈]\\n';
    text += document.querySelector('#backtrace pre').innerText + '\\n';
    text += '\\n[SQL]\\n';
    if(document.querySelector('#db-queries')) {
        text += document.querySelector('#db-queries').innerText + '\\n';
    }
    text += '\\n[全局变量]\\n';
    document.querySelectorAll('#superglobals .superglobal-table tr').forEach(tr => {
        text += tr.children[0].innerText + ':\\n' + tr.children[1].innerText + '\\n';
    });
    navigator.clipboard.writeText(text).then(function() {
        alert('调试信息已复制到剪贴板');
    }, function() {
        alert('复制失败');
    });
}
</script>
HTML;
    }

    /**
     * 数字格式化（兼容性更好）
     */
    private function formatNumber($number, $decimals = 2)
    {
        return number_format((float)$number, $decimals, '.', '');
    }

    /**
     * 获取当前请求的完整 URL。
     *
     * @return string 返回当前请求的完整 URL（协议 + 主机名）
     */
    public function getCurrentUrl()
    {
        $protocol = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $protocol = 'https';
        }

        $host = $_SERVER['HTTP_HOST']; // 当前主机名（包括端口号）

        // 返回完整的 URL
        return $protocol . '://' . $host;
    }

    /**
     * 重定向当前页面，去除指定的查询参数。
     *
     * 该函数检查当前 URL 是否包含指定的查询参数。如果包含该参数，则会重定向到一个新的 URL，新的 URL 与原始 URL 相同，但该参数会被移除。如果有其他查询参数，则会保留。
     *
     * @param string $param 要移除的查询参数的名称。
     * 
     * @return void
     * 
     * @example
     * // 假设 URL 为 http://example.com?page=1&edit=true
     * redirectWithoutParam('edit');
     * // 执行后会将页面重定向到 http://example.com?page=1
     */
    function redirectWithoutParam($param)
    {
        if (isset($_GET[$param])) {
            $url = strtok($_SERVER['REQUEST_URI'], '?');
            $query = $_GET;
            unset($query[$param]);

            if (!empty($query)) {
                $url .= '?' . http_build_query($query);
            }

            header("Location: $url");
            exit();
        }
    }
}
