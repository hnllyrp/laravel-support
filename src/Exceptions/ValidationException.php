<?php

namespace Hnllyrp\LaravelSupport\Exceptions;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException as Exception;

class ValidationException extends Exception
{
    /**
     * 报告异常至错误driver，如日志文件(storage/logs/laravel.log)
     *
     */
    public function report()
    {
        Log::info($this->status . ':' . $this->getMessage());
    }

    /**
     * 转换异常为 HTTP 响应
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function render($request)
    {
        // 如果是 AJAX 请求则返回 JSON 格式的数据
        if ($request->expectsJson()) {
            return response()->json(['code' => $this->status, 'message' => $this->error(), 'url' => $this->redirectTo]);
        }

        return redirect()->to($this->redirectTo)->with('message', $this->error())->with('type', $this->status);
    }

    /**
     * 获取第一条错误信息
     *
     * @return string
     */
    public function error()
    {
        return $this->validator->errors()->first();
    }
}
