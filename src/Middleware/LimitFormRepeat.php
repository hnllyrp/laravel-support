<?php

namespace Hnllyrp\LaravelSupport\Middleware;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * 防止表单重复提交
 */
class LimitFormRepeat
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param null $cache_name
     * @return mixed
     */
    public function handle($request, Closure $next, $cache_name = null)
    {
        if ($request->isMethod('POST')) {

            if (is_null($cache_name)) {
                $cache_name = md5(Route::currentRouteAction() . $request->getClientIp());
            }

            if (Cache::has($cache_name)) {
                // 重复请求
                if ($request->expectsJson()) {
                    return response()->json(['error' => 1, 'msg' => 'form repeat submit, please wait a moment']);
                } else {
                    return back()->with('error', 1)->with('msg', 'form repeat submit, please wait a moment');
                }
            }

            // 正常请求一次 记录缓存 3s后过期
            $value = Carbon::now();
            Cache::put($cache_name, $value, Carbon::now()->addSeconds(3));
        }

        return $next($request);
    }
}
