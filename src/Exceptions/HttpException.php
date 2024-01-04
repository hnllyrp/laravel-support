<?php

namespace Hnllyrp\LaravelSupport\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class HttpException extends Exception
{
    /**
     * 报告异常至错误driver，如日志文件(storage/logs/laravel.log)
     *
     */
    public function report()
    {
        Log::info($this->getCode() . ':' . $this->getMessage());
    }

    /**
     * 转换异常为 HTTP 响应
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function render($request)
    {
        // 如果是 AJAX 请求则返回 JSON 格式的数据
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], $this->getCode());
        }
        return response()->view('errors.404', ['message' => $this->getMessage(), 'code' => $this->getCode()]);
    }
}
