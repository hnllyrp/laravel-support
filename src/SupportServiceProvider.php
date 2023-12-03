<?php

namespace Hnllyrp\LaravelSupport;

use Hnllyrp\LaravelSupport\Console\Commands\AppCommand;
use Hnllyrp\LaravelSupport\Support\Macros\Builder\WhereHasIn;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

/**
 * Class SupportServiceProvider
 */
class SupportServiceProvider extends ServiceProvider
{
    /**
     * Register the provider.
     */
    public function register()
    {
        $this->macros();

        if ($this->app->environment() == 'local') {
         //
        }
    }

    /**
     * Boot the provider.
     */
    public function boot()
    {
        // 自定义配置文件
        $this->mergeConfigFrom(__DIR__ . '/config/shop.php', 'support');
        // 自定义语言包
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'support');// exp:  trans('support::common.test')
        // json translations
        $this->loadJsonTranslationsFrom(__DIR__ . '/resources/lang');
        // 自定义视图
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'support'); // exp: return view('support::admin.index.index');

        if ($this->app->runningInConsole()) {
            $this->commands([
                AppCommand::class
            ]);
        }

        /**
         * 添加全局中间件 （相当于 添加中间件至 App\Http\Kernel 的 protected $middleware 属性里）
         * 注意此处不能使用 App\Http\Kernel::class，否则全局中间件不生效
         * @var $kernel \Illuminate\Contracts\Http\Kernel
         */
        // $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);

        // $kernel->pushMiddleware(Cors::class);
        // $kernel->prependMiddleware(Cors::class);

        // $kernel->appendMiddlewareToGroup('web', Cors::class);
        // $kernel->appendMiddlewareToGroup('api', Cors::class);


        /**
         * 路由中间件，不能通过像全局中间件那样设置，需要使用 Illuminate\Routing\Router::class
         *
         * 1. 添加路由中间件，并且设置一个别名
         *  aliasMiddleware（相当于 添加中间件至 App\Http\Kernel 的 protected $routeMiddleware 属性里）
         * 2. 添加中间件组 （相当于 添加中间件至 App\Http\Kernel 的 protected $middlewareGroups 属性里）
         * prependMiddlewareToGroup 添加一个中间件至 中间组的开头
         * pushMiddlewareToGroup 添加一个中间件至 中间组的结束
         */
        // $router = $this->app->make(\Illuminate\Routing\Router::class);
        // $router->aliasMiddleware('api_token', \Hnllyrp\LaravelSupport\Middleware\ApiToken::class);
        // $router->pushMiddlewareToGroup('api', \Hnllyrp\LaravelSupport\Middleware\ApiToken::class);

    }


    /**
     * 扩展 macro
     */
    protected function macros()
    {
        // whereHasIn
        Eloquent\Builder::macro('whereHasIn', function ($relationName, $callable = null) {
            return (new WhereHasIn($this, $relationName, function ($nextRelation, $builder) use ($callable) {
                if ($nextRelation) {
                    return $builder->whereHasIn($nextRelation, $callable);
                }
                if ($callable) {
                    return $builder->callScope($callable);
                }
                return $builder;
            }))->execute();
        });
    }

}
