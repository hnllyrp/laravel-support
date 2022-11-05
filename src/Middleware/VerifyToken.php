<?php

namespace Hnllyrp\LaravelSupport\Middleware;

use Closure;
use Hnllyrp\LaravelSupport\Services\Common\JwtService;
use Hnllyrp\LaravelSupport\Traits\ApiResponse;

/**
 * 验证用户登录token  使用jwt
 */
class VerifyToken
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 验证登录
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setErrorCode(12)->failed(trans('user.not_login'));
        }

        // 已登录返回 user_id
        $request->offsetSet('user_id', $user_id);

        return $next($request);
    }

    /**
     * 返回用户数据的属性
     * @param null $token
     * @param string $item
     * @param string $header
     * @return mixed
     */
    protected function authorization($token = null, $item = 'user_id', $header = 'token')
    {
        if (request()->hasHeader($header)) {
            $token = request()->header($header);
        } elseif (request()->has($header)) {
            $token = request()->get($header);
        }

        if (is_null($token)) {
            return 0;
        }

        $payload = JwtService::decodeToken($token);

        return $payload ? collect($payload)->get($item) : 0;
    }
}
