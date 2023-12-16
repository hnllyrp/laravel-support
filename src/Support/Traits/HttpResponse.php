<?php

namespace Hnllyrp\LaravelSupport\Support\Traits;

use App\Support\Arr;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Str;

/**
 * Trait HttpResponse
 *  api端 或 web端 可用
 * @package Hnllyrp\LaravelSupport\Support\Traits
 */
trait HttpResponse
{
    /**
     * 自定义业务错误码
     * @var int
     */
    protected $errorCode = 1;

    /**
     * http状态码
     * @var int
     */
    protected $httpCode = 0;

    /**
     * meta 信息、额外信息等
     * @var array
     */
    protected $meta;

    /**
     * 模板输出变量
     * @var array
     */
    protected $viewData = [];

    /**
     * success 消息
     * @param mixed $data
     * @param string $message
     * @param array $header
     * @param int $options
     * @return JsonResponse
     */
    public function success($data = null, string $message = 'success', array $header = [], int $options = 0)
    {
        if ($data instanceof ResourceCollection) {
            $resource = $data;
            $data = $resource->resource;
        }

        if ($data instanceof JsonResource) {
            $resource = $data;
            $data = array_merge_recursive($resource->resolve(request()), $resource->with(request()), $resource->additional);
        }

        if ($data instanceof AbstractPaginator) {
            $data = $this->formatPaginatedData($data);
        }

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }

        $data = Arr::toArray($data);

        $total = !empty($data['meta']) ? ($data['meta']['total'] ?? 0) : 0;
        $responseData = [
            'code' => 0,
            'status' => 'success',
            'msg' => $message ?? '',
            'data' => $data ?: [],
            // 兼容 layui 前端分页 返回总数
            'count' => $total
        ];

        // 嵌套 data 则合并一个 data 至最外层
        if (isset($data['data'])) {
            $responseData = array_merge($responseData, $data);
        }

        if (!empty($this->meta)) {
            $responseData['meta'] = $this->meta;
        }

        return self::json($this->formatDataFields($responseData, config('response.format.layui', [])), $this->httpCode ?: 200, $header, $options);
    }

    /**
     * fail 消息
     * @param string $message
     * @param int $status http状态码
     * @param array $header
     * @param int $options
     * @return JsonResponse
     */
    public function fail(string $message = '', int $status = 200, array $header = [], int $options = 0)
    {
        return self::json($this->formatDataFields([
            'code' => $this->errorCode ?: 1,
            'status' => 'fail',
            'msg' => $message ?? '',
            'data' => [],
        ]), $this->httpCode ?: $status, $header, $options);
    }

    /**
     * 跳转并消息提示 主要用于web端
     * @param string $message
     * @param string $type success,info,error,warning
     * @param null $redirect
     * @param int $timer 跳转倒计时
     * @param array $header
     * @return Response
     */
    public function message($message = '', string $type = 'success', $redirect = null, int $timer = 3, array $header = [])
    {
        // TODO 将 redirect 优化进 meta
        return self::response_view('support::message', $this->formatDataFields([
            'code' => 0,
            'status' => $type,
            'msg' => $message ?? '',
            'redirect' => is_null($redirect) ? request()->server('HTTP_REFERER') : $redirect,
            'count_down' => $timer,
        ]), $this->httpCode ?: 200, $header);
    }

    /**
     * 设置字段的别名,显示
     * @param $responseData
     * @param array $dataFieldsConfig
     * @return mixed
     */
    protected function formatDataFields($responseData, array $dataFieldsConfig = [])
    {
        if (empty($dataFieldsConfig)) {
            return $responseData;
        }

        return tap($responseData, function (&$item) use ($dataFieldsConfig) {
            foreach ($dataFieldsConfig as $key => $config) {
                if (!Arr::has($item, $key)) {
                    continue;
                }

                $show = $config['show'] ?? true;
                $alias = $config['alias'] ?? '';

                if ($alias && $alias !== $key) {
                    Arr::set($item, $alias, Arr::get($item, $key));
                    $item = Arr::except($item, $key);
                    $key = $alias;
                }

                if (!$show) {
                    $item = Arr::except($item, $key);
                }
            }
        });
    }

    /**
     * @param $data
     * @return mixed
     */
    protected static function formatData($data)
    {
        // 过滤数组null值
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_null($value)) {
                    unset($data[$key]);
                }
            }
        }

        return $data;
    }

    /**
     * @param $headers
     * @return mixed
     */
    protected static function formatHeaders($headers)
    {
        // 设置客户端设备唯一ID标识
        if (!request()->hasHeader('X-Client-Hash')) {
            $client_hash = session()->getId();
        } else {
            $client_hash = request()->header('X-Client-Hash');
        }

        $headers['X-Client-Hash'] = $client_hash;
        return $headers;
    }

    /**
     * 格式化分页数据
     * @param AbstractPaginator $resource
     * @return array
     */
    protected function formatPaginatedData(AbstractPaginator $resource)
    {
        if ($resource instanceof Paginator) {
            // 简单分页
            $data = [
                // 'data' => $resource->toArray()['data'],
                'links' => [
                    'first' => $resource->url(1),
                    'next' => $resource->nextPageUrl(),
                    'prev' => $resource->previousPageUrl(),
                ],
                'meta' => [
                    'current_page' => $resource->currentPage(),
                    'from' => $resource->firstItem(),
                    'path' => $resource->path(),
                    'per_page' => $resource->perPage(),
                    'to' => $resource->lastItem(),
                ],
            ];
        }

        if ($resource instanceof LengthAwarePaginator) {
            // 普通分页
            $data = [
                // 'data' => $resource->items(),
                'data' => $resource->toArray()['data'],
                'links' => array_filter([
                    'first' => $resource->url(1),
                    'last' => $resource->url($resource->lastPage()),
                    'next' => $resource->nextPageUrl(),
                    'prev' => $resource->previousPageUrl(),
                ]),
                'meta' => [
                    'current_page' => $resource->currentPage(),
                    'from' => $resource->firstItem(),
                    'last_page' => $resource->lastPage(),
                    'path' => $resource->path(),
                    'per_page' => $resource->perPage(),
                    'to' => $resource->lastItem(),
                    'total' => $resource->total(),
                ],
            ];
        }

        return $data ?? [];
    }

    /**
     * 给模板视图赋值
     * Add a piece of data to the view.
     *
     * @param string|array $key
     * @param mixed $value
     */
    public function assign($key, $value = null)
    {
        if (is_array($key)) {
            $this->viewData = array_merge($this->viewData, $key);
        } else {
            $this->viewData[$key] = $value;
        }
    }

    /**
     * 显示模板视图
     * @param null $view
     * @param array $data
     * @param array $mergeData
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function display($view = null, $data = [], array $mergeData = [])
    {
        return $this->view($view, $data, $mergeData);
    }

    /**
     * @param null $view
     * @param array $data
     * @param array $mergeData
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function view($view = null, $data = [], array $mergeData = [])
    {
        if (!empty($view)) {
            return view($view, $data, $mergeData)->with($this->viewData);
        }

        $view = $this->getCurrentActionView();

        return view($view, $data, $mergeData)->with($this->viewData);
    }

    /**
     * 异步加载 blade 模板 用于 ajax 请求, 返回纯数据的场景
     *
     * @param null $view
     * @param array $data
     * @param array $mergeData
     * @return array|string
     */
    public function fetch($view = null, $data = [], array $mergeData = [])
    {
        try {
            return $this->view($view, $data, $mergeData)->render();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * 获取当前请求对应的默认模板路径
     *
     * @return string
     */
    protected function getCurrentActionView()
    {
        $action = request()->route()->getAction();

        $namespaces = explode('\\', $action['namespace']);
        $controllers = str_replace($action['namespace'] . '\\', '', $action['controller']);
        list($controller, $method) = explode('@', $controllers);
        $controller_name = Str::snake(str_replace('Controller', '', $controller));
        $method = Str::snake($method);

        $base = $namespaces[0]; // App 或 Modules

        $view = '';
        $array = collect($namespaces)->map(function ($value) {
            return Str::snake($value);
        })->filter(function ($value)  use ($base) {
            if ($base == 'App') {
                // 忽略目录 App, Http, Controllers
                return !in_array($value, ['app', 'http', 'controllers']);
            }
            if ($base == 'Modules') {
                // 忽略目录 Modules, Http, Controllers
                return !in_array($value, ['modules', 'http', 'controllers']);
            }
            return true;
        })->values()->all();

        foreach ($array as $key => $item) {
            if ($key == 0) {
                $view .= $item . '::'; // 模块
            } else {
                $view .= $item . '.';
            }
        }
        $view .= $controller_name . '.' . $method;

        return $view;
    }

    /**
     * no Content 消息
     * @param array $headers
     * @return Response
     */
    public function noContent(array $headers = [])
    {
        $headers = self::formatHeaders($headers);

        return response()->noContent(204, $headers);
    }

    /**
     * @param $view
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @return Response
     */
    protected static function response_view($view, $data = null, int $status = 200, array $headers = [])
    {
        $data = self::formatData($data);
        $headers = self::formatHeaders($headers);

        return response()->view($view, $data, $status, $headers);
    }

    /**
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @param int $options
     * @return JsonResponse
     */
    protected static function json($data = null, int $status = 200, array $headers = [], $options = 0)
    {
        $data = self::formatData($data);
        $headers = self::formatHeaders($headers);

        return response()->json($data, $status, $headers, $options);
    }

    /**
     * 设置 meta 数据
     * @param mixed $meta
     * @return $this
     */
    protected function withMeta($meta)
    {
        $this->meta[] = $meta;
        return $this;
    }

    /**
     * @param $errorCode
     * @return $this
     */
    protected function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;
        return $this;
    }

    /**
     * @param $httpCode
     * @return $this
     */
    protected function setHttpCode($httpCode)
    {
        $this->httpCode = $httpCode;
        return $this;
    }
}
