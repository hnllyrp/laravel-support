<?php

namespace Hnllyrp\LaravelSupport\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * 检查网站关闭状态
 */
class CheckStatus
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (config('shop.shop_closed') == 1) {
            if ($request->is('api/*')) {
                return response()->json(['code' => 0, 'msg' => config('shop.close_reason')]);
            } else {
                return response()->view('support::closed', ['close_reason' => config('shop.close_reason')]);
            }
        }

        return $next($request);
    }
}
