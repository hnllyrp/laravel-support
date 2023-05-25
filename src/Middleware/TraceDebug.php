<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 记录运行时间日志 - 仿tp6 TraceDebug
 * Class TraceDebug
 * @package App\Http\Middleware
 */
class TraceDebug
{
    /**
     * 应用开始时间
     * @var float
     */
    protected $beginTime;

    /**
     * 应用内存初始占用
     * @var integer
     */
    protected $beginMem;


    protected $config = [
        'tabs' => ['base' => '基本', 'file' => '文件', 'info' => '流程', 'notice|error' => '错误', 'sql' => 'SQL', 'debug|log' => '调试'],
    ];

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 前置行为
        $debug = config('app.debug');

        if ($debug) {
            $this->beginTime = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
            $this->beginMem = defined('LARAVEL_START_MEM') ? LARAVEL_START_MEM : memory_get_usage();
        }

        $response = $next($request);

        // 后置行为 - Trace调试注入
        if ($debug) {
            $data = $response->getContent();
            $this->traceDebug($request, $response, $data);
            $response->setContent($data);
        }
        return $response;
    }

    /**
     * 获取应用开启时间
     * @access public
     * @return float
     */
    public function getBeginTime(): float
    {
        return $this->beginTime;
    }

    /**
     * 获取应用初始内存占用
     * @access public
     * @return integer
     */
    public function getBeginMem(): int
    {
        return $this->beginMem;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $response
     * @param $content
     */
    public function traceDebug($request, $response, &$content)
    {
        $output = $this->output($request, $response);
        if (is_string($output)) {
            // trace调试信息注入
            $pos = strripos($content, '</body>');
            if (false !== $pos) {
                $content = substr($content, 0, $pos) . $output . substr($content, $pos);
            } else {
                $content = $content . $output;
            }
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $response
     * @param $content
     */
    public function output($request, $response)
    {
        // 获取基本信息
        $runtime = number_format(microtime(true) - $this->getBeginTime(), 10, '.', '');
        $reqs = $runtime > 0 ? number_format(1 / $runtime, 2) : '∞';
        $mem = number_format((memory_get_usage() - $this->getBeginMem()) / 1024, 2);

        // 页面Trace信息
        if ($request->root()) {
            $uri = $request->getProtocolVersion() . ' ' . $request->method() . ' : ' . $request->url();
        } else {
            $uri = 'cmd:' . implode(' ', $request->server()['argv']);
        }


        $base = [
            '请求信息' => date('Y-m-d H:i:s', $request->server('REQUEST_TIME') ?: time()) . ' ' . $uri,
            '运行时间' => number_format((float)$runtime, 6) . 's [ 吞吐率：' . $reqs . 'req/s ] 内存消耗：' . $mem . 'kb 文件加载：' . count(get_included_files()),
            // '查询信息' => $app->db->getQueryTimes() . ' queries',
            // '缓存信息' => $app->cache->getReadTimes() . ' reads,' . $app->cache->getWriteTimes() . ' writes',
        ];

        $info = $this->getFile(true);

        // 页面Trace信息
        $trace = [];
        foreach ($this->config['tabs'] as $name => $title) {
            $name = strtolower($name);
            switch ($name) {
                case 'base': // 基本信息
                    $trace[$title] = $base;
                    break;
                case 'file': // 文件信息
                    $trace[$title] = $info;
                    break;
                default: // 调试信息
                    if (strpos($name, '|')) {
                        // 多组信息
                        $names = explode('|', $name);
                        $result = [];
                        foreach ($names as $item) {
                            $result = array_merge($result, $log[$item] ?? []);
                        }
                        $trace[$title] = $result;
                    } else {
                        $trace[$title] = $log[$name] ?? '';
                    }
            }
        }

        // 记录日志
        // Log::debug('trace_debug', $trace);

        //输出到控制台
        $lines = '';
        foreach ($trace as $type => $msg) {
            $lines .= $this->console($type, empty($msg) ? [] : $msg);
        }
        $js = <<<JS

<script type='text/javascript'>
{$lines}
</script>
JS;
        return $js;
    }

    /**
     * 获取文件加载信息
     * @access public
     * @param bool $detail 是否显示详细
     * @return integer|array
     */
    public static function getFile($detail = false)
    {
        $files = get_included_files();

        if ($detail) {
            $info = [];

            foreach ($files as $file) {
                $info[] = $file . ' ( ' . number_format(filesize($file) / 1024, 2) . ' KB )';
            }

            return $info;
        }

        return count($files);
    }

    protected function console($type, $msg)
    {
        $type = strtolower($type);
        $trace_tabs = array_values($this->config['tabs']);
        $line[] = ($type == $trace_tabs[0] || '调试' == $type || '错误' == $type)
            ? "console.group('{$type}');"
            : "console.groupCollapsed('{$type}');";

        foreach ((array)$msg as $key => $m) {
            switch ($type) {
                case '调试':
                    $var_type = gettype($m);
                    if (in_array($var_type, ['array', 'string'])) {
                        $line[] = "console.log(" . json_encode($m) . ");";
                    } else {
                        $line[] = "console.log(" . json_encode(var_export($m, 1)) . ");";
                    }
                    break;
                case '错误':
                    $msg = str_replace("\n", '\n', json_encode($m));
                    $style = 'color:#F4006B;font-size:14px;';
                    $line[] = "console.error(\"%c{$msg}\", \"{$style}\");";
                    break;
                case 'sql':
                    $msg = str_replace("\n", '\n', $m);
                    $style = "color:#009bb4;";
                    $line[] = "console.log(\"%c{$msg}\", \"{$style}\");";
                    break;
                default:
                    $m = is_string($key) ? $key . ' ' . $m : $key + 1 . ' ' . $m;
                    $msg = json_encode($m);
                    $line[] = "console.log({$msg});";
                    break;
            }
        }
        $line[] = "console.groupEnd();";
        return implode(PHP_EOL, $line);
    }

}
