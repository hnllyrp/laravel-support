<?php

namespace Hnllyrp\LaravelSupport\Support\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait ApiResponse
 */
trait ApiResponse
{
    /**
     * @var int
     */
    protected $errorCode = 0;
    protected $httpCode = 0;

    protected $message = '';

    /**
     * @return int
     */
    protected function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param $errorCode
     * @return $this
     */
    protected function setErrorCode($errorCode)
    {
        $this->httpCode = $errorCode;
        $this->errorCode = $errorCode;
        return $this;
    }

    /**
     * @return int
     */
    protected function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * @param $httpCode
     * @return $this
     */
    protected function setHttpCode($httpCode)
    {
        $this->httpCode = $httpCode;
        $this->errorCode = $httpCode;
        return $this;
    }

    /**
     * @return string
     */
    protected function getMessage()
    {
        return $this->message;
    }

    /**
     * @param $message
     * @return $this
     */
    protected function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * 返回封装后的API数据到客户端
     * @param mixed $data 要返回的数据
     * @param array $header 发送的Header信息
     * @return JsonResponse
     */
    public function succeed($data = [], array $header = [])
    {
        return $this->response([
            'code' => 1,
            'status' => 'success',
            'msg' => $this->getMessage(),
            'data' => $data,
            'time' => request()->server('REQUEST_TIME'),
            'http_code' => 200,
        ])->withHeaders($header);
    }

    /**
     * 返回异常数据到客户端
     * @param string $msg
     * @param mixed $data 要返回的数据
     * @param array $header 发送的Header信息
     * @return JsonResponse
     */
    public function failed(string $msg = '', $data = [], array $header = [])
    {
        return $this->response([
            'code' => 0,
            'status' => 'fail',
            'msg' => $this->getMessage() ?: $msg,
            'data' => $data,
            'time' => request()->server('REQUEST_TIME'),
            'http_code' => $this->getErrorCode() ?: 400,
        ])->withHeaders($header);
    }

    /**
     * 返回消息
     * @param string $msg
     * @param array $header
     */
    public function message(string $msg = '', array $header = [])
    {
        $this->response([
            'code' => 1,
            'status' => 'success',
            'msg' => $this->getMessage() ?: $msg,
            'time' => request()->server('REQUEST_TIME'),
            'http_code' => $this->getHttpCode(),
        ])->withHeaders($header);
    }

    /**
     * 返回 Not Found 异常
     * @param string $message
     * @return JsonResponse
     */
    protected function responseNotFound($message = 'Not Found')
    {
        return $this->setErrorCode(404)->failed($message);
    }

    /**
     * 返回 Json 数据格式
     * @param string|array|object $data
     * @return JsonResponse
     */
    protected function response($data = [])
    {
        // 客户端设备唯一ID
        $client_hash = request()->header('X-Client-Hash');

        if (is_null($client_hash) || empty($client_hash)) {
            $client_hash = session()->getId();
        }

        return response()->json($data)->withHeaders([
            'X-Client-Hash' => $client_hash
        ]);
    }

}
