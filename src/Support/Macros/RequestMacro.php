<?php


namespace Hnllyrp\LaravelSupport\Support\Macros;

/**
 * Class RequestMacro
 * @mixin \Illuminate\Http\Request
 */
class RequestMacro
{
    public function userId(): callable
    {
        return function () {
            return optional($this->user())->id;
        };
    }

    /**
     * headers转数组
     * @return callable
     */
    public function headers(): callable
    {
        return function ($key = null, $default = null) {
            return $key === null ?
                collect($this->header())->map(function ($header) {
                    return $header[0];
                })->toArray()
                : $this->header($key, $default);
        };
    }

    /**
     * 获取服务端ip
     * @return callable
     */
    public function getServerIp(): callable
    {
        return function () {
            $server = request()->server();
            if (isset($server)) {
                if (request()->server('SERVER_ADDR')) {
                    // HTTP请求可以获取, 但CLI不行
                    $server_ip = request()->server('SERVER_ADDR');
                } elseif (request()->server('SERVER_NAME')) {
                    $server_ip = gethostbyname(request()->server('SERVER_NAME'));
                } else {
                    $server_ip = request()->server('LOCAL_ADDR');
                }
            } else {
                // HTTP请求可以获取, 但CLI不行
                $server_ip = getenv('SERVER_ADDR');
            }

            if (!$server_ip) {
                // 兼容获取CLI方式下服务器IP, 注意云主机有可能只能获取到内网IP
                return getHostByName(getHostName());
            }
            return filter_var($server_ip, FILTER_VALIDATE_IP) ?: '127.0.0.1';
        };
    }

    /**
     * 获取服务器唯一标识
     * @return callable
     */
    public function getServerMd5(): callable
    {
        return function () {
            // 取当前电脑SERVER与当前域名信息 排除请求时间变化因子，得到一个暂时唯一的标识
            $server = request()->server();
            $server = collect($server)->except(['REQUEST_TIME_FLOAT', 'REQUEST_TIME'])->toArray();
            return md5(serialize($server));
        };
    }
}
