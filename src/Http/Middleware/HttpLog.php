<?php

namespace Hnllyrp\LaravelSupport\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Class HttpLog
 * https://github.com/kbdxbt/kbframe-common/blob/main/Http/Middleware/LogHttp.php
 */
class HttpLog
{
    /**
     * 设置不记录日志的请求方法
     * @var string[]
     */
    protected $exceptMethods = [
        'OPTIONS',
    ];

    /**
     * 设置不记录日志的请求路径
     * @var string[]
     */
    protected $exceptPaths = [
        'admin/logs*', 'admin/logs/*',
    ];

    /**
     * 设置不记录日志的请求头
     *  大小写都可以，最终会统一为小写
     * @var string[]
     */
    protected $removedHeaders = [
        //官方
        'accept', 'accept-encoding', 'accept-language', 'Authorization',
        'cache-control', 'charset', 'connection', 'content-length', 'content-type_except', 'cookie',
        'host', 'origin', 'pragma', 'referer',
        'sec-ch-ua', 'sec-ch-ua-mobile', 'sec-ch-ua-platform', 'sec-fetch-dest', 'sec-fetch-mode', 'sec-fetch-site',
        'upgrade-insecure-requests', 'user-agent', 'x-forwarded-for', 'x-forwarded-host', 'x-forwarded-port', 'x-forwarded-proto', 'x-requested-with',

        //自定义
        'encrypteddata', 'ivstr',
    ];

    /**
     * 设置不记录的请求参数
     * @var string[]
     */
    protected $removedInputs = [
        'password',
        'password_confirmation',
        'new_password',
        'old_password',
        '_token'
    ];

    /**
     * 自定义黑名单
     * @var array
     */
    protected static $skipCallbacks = [];

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        //没有则生成唯一请求ID
        if (!$request->header('Request-Id')) {
            $requestId = (string)md5(uniqid() . time());
            $request->headers->set('Request-Id', $requestId);
        }

        return $next($request);
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function terminate(Request $request, Response $response)
    {
        $blacklist = $this->shouldntLog($request);
        if ($blacklist) {
            return false;
        }

        // 执行操作
        $is_format = false; // 是否格式化字符串
        if ($is_format == true) {

            $data = $this->requestDataFormat([
                'url' => $this->getFullUrl($request),
                'route' => $request->getRequestUri(),
                'method' => substr($request->method(), 0, 10),
                'client_ip' => substr((string)$request->getClientIp(), 0, 16),
                'request_id' => $request->header('Request-Id', ''),
                'request_header' => $this->getClearRequestHeader($request),
                'request_time' => (string)constant('LARAVEL_START'),
                'response' => $this->responseFormat($request, $response),
                'response_time' => (string)microtime(true),
                'duration_time' => substr($this->calculateDuration(), 0, 10),
                'usage_memory' => $this->getUsageMemory(),
                'user_agent' => $this->getUserAgent($request),
                'payload' => $this->getRequestPayload($request),
                'file' => $this->getClearRequestFile($request),
            ]);

            Log::info($data);
            // Log::channel('req')->info($data);

            return true;
        } else {
            $data = $this->collectData($request, $response);

            Log::info(__METHOD__, $data);
            // Log::channel('req')->info(__METHOD__, $data);
            return true;
        }
    }

    protected function shouldntLog(Request $request): bool
    {
        if (\in_array($request->method(), array_map('strtoupper', $this->exceptMethods), true)) {
            return true;
        }

        foreach ($this->exceptPaths as $exceptPath) {
            $exceptPath === '/' or $exceptPath = trim($exceptPath, '/');
            if ($request->fullUrlIs($exceptPath) || $request->is($exceptPath)) {
                return true;
            }
        }

        return (bool)($this->shouldSkip($request));
    }

    public static function skipWhen(Closure $callback)
    {
        static::$skipCallbacks[] = $callback;
    }

    protected function shouldSkip(Request $request): bool
    {
        foreach (static::$skipCallbacks as $callback) {
            if ($callback($request)) {
                return true;
            }
        }

        return false;
    }

    protected function collectData(Request $request, $response): array
    {
        // MySQL mediumtext 类型最大 16MB (15 * 1024 * 1024)
        $maxLengthOfMediumtext = 15 * 1024 * 1024;

        return [
            'url' => substr($request->url(), 0, 128),
            'route' => $request->getRequestUri(),
            'method' => substr($request->method(), 0, 10),
            'client_ip' => substr((string)$request->getClientIp(), 0, 16),
            'request_id' => $request->header('Request-Id', ''),
            'request_param' => substr($this->extractInput($request), 0, $maxLengthOfMediumtext),
            'request_header' => substr($this->extractHeader($request), 0, $maxLengthOfMediumtext),
            'request_time' => (string)constant('LARAVEL_START'),
            'response_code' => (string)$response->status(),
            'response_header' => substr($this->extractHeader($response), 0, $maxLengthOfMediumtext),
            'response_body' => substr((string)$response->getContent(), 0, $maxLengthOfMediumtext),
            'response_time' => (string)microtime(true),
            'duration_time' => substr($this->calculateDuration(), 0, 10),
            'usage_memory' => $this->getUsageMemory(),
            'user_agent' => $this->getUserAgent($request),
            'payload' => $this->getRequestPayload($request),
            'file' => $this->getClearRequestFile($request),
            'ext' => [],
        ];
    }

    /**
     * @param $requestOrResponse
     * @return string
     */
    protected function extractHeader($requestOrResponse): string
    {
        $header = Arr::except(
            $requestOrResponse->headers->all(),
            array_map('strtolower', $this->removedHeaders)
        );

        return (string)json_encode($header);
    }

    protected function extractInput(Request $request): string
    {
        return (string)json_encode($request->except($this->removedInputs));
    }

    protected function calculateDuration(): string
    {
        return number_format(microtime(true) - (constant('LARAVEL_START') ?: microtime(true)), 3);
    }

    /**
     * 以下参考来源
     * https://www.cnblogs.com/phpphp/p/17783746.html
     */

    /**
     * @function 获取全路径
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    private function getFullUrl($request)
    {
        return urldecode($request->fullUrl());
    }

    /**
     * @function 获取用户代理
     * @param \Illuminate\Http\Request $request
     * @return   string
     */
    private function getUserAgent($request)
    {
        return $request->header('user-agent') ?? '""';
    }

    /**
     * @function 获取请求荷载，包含x-www-form-urlencoded、multipart/form-data、json、xml等纯文本荷载数据
     * @param \Illuminate\Http\Request $request
     * @return   array
     */
    private function getRequestPayload($request)
    {
        if (strtoupper($request->method()) === 'GET') {
            return [];
        }

        $except = collect($request->query())->keys()->merge($this->removedInputs)->filter();
        $input = collect($request->input())->except($except)->map(function ($val) {
            return is_null($val) ? "" : $val;
        })->toArray();

        if ($input) {
            return $input;
        }

        $raw = $request->getContent();
        if ($request->header('content-type') === 'application/xml') {
            if (!$raw) {
                return [];
            }
            if (!$this->isXml($raw)) {
                return [$raw];
            }
            return json_decode(json_encode(simplexml_load_string(str_replace(["\r", "\n"], '', $raw))), true);
        }

        return array_filter([$raw]);
    }

    /**
     * @function 获取简洁的文件上传数据
     * @param \Illuminate\Http\Request $request
     * @return   array
     */
    private function getClearRequestFile($request)
    {
        return collect($request->allFiles())->map(function ($val) {
            if (is_array($val)) {
                $res = collect($val)->map(function ($v) {
                    return $v->getClientOriginalName();
                });
            } else {
                $res = $val->getClientOriginalName();
            }
            return $res;
        })->toArray();
    }

    /**
     * @function 获取干净的请求头
     * @param \Illuminate\Http\Request $request
     * @return   array
     */
    private function getClearRequestHeader($request)
    {
        $except_header = array_filter(array_map('strtolower', $this->removedHeaders));
        return collect($request->header())->except($except_header)->toArray();
    }

    /**
     * @function 获取脚本使用的内存
     * @return   string
     * @other    void
     */
    private function getUsageMemory()
    {
        $bytes = memory_get_usage();
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes /= pow(1024, ($i = floor(log($bytes, 1024))));
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * @function 格式化响应数据
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\JsonResponse|\Illuminate\Http\Response $response
     * @return   string|array
     */
    private function responseFormat($request, $response)
    {
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            return collect($response->getData())->toArray();
        }

        $isRecordHttpResponseData = false; // 是否记录非json格式的响应的数据
        if (!$isRecordHttpResponseData) {
            return '""';
        }

        if ($response instanceof \Illuminate\Http\Response) {
            return $response->getContent();
        }

        return '""';
    }


    /**
     * @function 格式化数组并转换为字符串
     * @param    $request_data array
     * @return   string
     */
    private function requestDataFormat($request_data)
    {
        $str = "\n";
        foreach ($request_data as $k => $v) {
            //格式化请求头
            if (($k == 'header') && $v) {
                foreach ($v as $key => $val) {
                    if (count($val) == 1) {
                        $v[$key] = collect($val)->values()->first();
                    } else {
                        $v[$key] = $val;
                    }
                }
            }

            //格式化数据
            $v = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v;
            $k = str_pad($k, 9, ' ', STR_PAD_RIGHT);
            $str .= "{$k}: {$v}\n";
        }

        return $str;
    }

    /**
     * @function 判断是否是xml
     * @param    $str string 要判断的xml数据
     * @return   bool
     */
    private function isXml($str)
    {
        libxml_use_internal_errors(true);
        simplexml_load_string($str);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        return !$errors;
    }
}
