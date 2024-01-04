<?php

namespace Hnllyrp\LaravelSupport\Http\Middleware;

use Closure;
use Hnllyrp\LaravelSupport\Exceptions\HttpException;
use Hnllyrp\LaravelSupport\Services\Common\JwtService;
use Hnllyrp\LaravelSupport\Support\Traits\HttpResponse;

/**
 * 验证用户登录token  使用jwt
 */
class ApiToken
{
    use HttpResponse;

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
            if ($request->expectsJson()) {
                return $this->setErrorCode(12)->fail(trans('user.not_login'));
            }
            throw new HttpException(trans('user.not_login'), 12);
            // return $this->setErrorCode(12)->fail(trans('user.not_login'));
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
        return JwtService::getUserToken($token, $item, $header);
    }
}
