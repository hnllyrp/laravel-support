<?php

namespace Hnllyrp\LaravelSupport\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;


/**
 * 一个自定义的服务提供者 参考 demo
 * Class DevelopServiceProvider
 */
class DevelopServiceProvider extends ServiceProvider
{
    /**
     * 先后顺序:
     * 1. 通过 Service Provider 的 register() 方法注册「绑定」
     * 2. 所有 Service Provider 的 register() 都执行完之后，再通过它们 boot() 方法，干一些别的事
     */

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // 自定义配置文件
        $this->mergeConfigFrom(__DIR__ . '/config/develop.php', 'develop'); // exp: config('develop.name');

        // 注册绑定类
        //$this->app->bind(MyInterface::class, MyClass::class);

        // 注册单例服务
        // $this->app->singleton('hash', function () {
        //     return new BcryptHasher;
        // });

        // 注册绑定其他服务提供者
        // $this->app->register('App\\OtherServiceProvider');

    }

    /**
     * Bootstrap services.
     *
     * @param Router $router
     * @return void
     */
    public function boot(Router $router)
    {
        /**
         * boot() 方法中可以使用依赖注入。
         * 这是因为在 Illuminate\Foundation\Application 类中，
         * 通过 bootProvider() 方法中的 $this->call([$provider, 'boot']) 来执行 Service Provider 的 boot() 方法
         */

        // 自定义视图
        $this->loadViewsFrom(__DIR__ . '/path/to/views', 'develop'); // exp: return view('develop::admin.index.index');
        // 自定义语言包
        $this->loadTranslationsFrom(__DIR__ . '/path/to/translations', 'develop');// exp:  trans('develop::common.test')
        // 自定义数据库迁移
        $this->loadMigrationsFrom(__DIR__ . '/path/to/migrations'); // exp: php artisan migrate

        // 自定义路由
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // 自定义注册路由
        $this->registerRoute($router);

        // 加载自定义辅助函数
        $this->loadHelperFrom(__DIR__ . '/Support');

        // 自定义命令
        if ($this->app->runningInConsole()) {
            // 命令行文件
            $this->commands([
                // FooCommand::class,
            ]);

            // 发布 视图、语言包、数据库迁移文件等
            // 发布配置文件
            $this->publishes([
                __DIR__ . '/path/to/config/develop.php' => config_path('develop.php'),
            ]);

            $this->publishes([
                __DIR__ . '/path/to/views' => resource_path('views/vendor/develop'),
            ]);

            $this->publishes([
                __DIR__ . '/path/to/translations' => resource_path('lang/vendor/develop'),
            ]);

            // 发布Seeder文件
            $this->publishes([
                __DIR__ . '/path/to/seeds' => $this->app->databasePath('seeds'),
            ], 'seeds');

            // 公用 Assets 资源文件JavaScript、CSS 和图片等文件
            $this->publishes([
                __DIR__ . '/path/to/assets' => public_path('vendor/develop'),
            ], 'public');
        }
    }

    /**
     * Register routes.
     *
     * @param Router $router
     */
    protected function registerRoute(Router $router)
    {
        if (!$this->app->routesAreCached()) {
            $router->middleware('web')->namespace(__NAMESPACE__ . '\Controllers')->group(__DIR__ . '/Routes/web.php');
            $router->middleware('api')->namespace(__NAMESPACE__ . '\Controllers')->group(__DIR__ . '/Routes/api.php');
        }
    }

    /**
     * load helpers file
     *
     * @param $paths
     */
    protected function loadHelperFrom($paths)
    {
        if (file_exists($constant = $paths . '/constant.php')) {
            require_once $constant;
        }
        if (file_exists($helper = $paths . '/helpers.php')) {
            require_once $helper;
        }
    }
}
