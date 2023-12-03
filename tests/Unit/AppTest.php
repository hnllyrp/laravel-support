<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Class AppTest
 * @example vendor/bin/phpunit tests/Unit/AppTest
 * @package Tests\Unit
 */
class AppTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBase()
    {
        /**
         * 测试服务容器
         * @see \Illuminate\Foundation\Application.php
         * 主要包含 基础目录、服务提供者、绑定的实例等
         */
        //app();

        /**
         * @see \Illuminate\Routing\Router.php
         * 主要包含 路由相关的所有信息，如 events 事件 ，container 服务容器， serviceProviders 服务提供者，middleware middlewareGroups middlewarePriority中间件
         */
        // app()->make('router');
        // app()->make('router')->getMiddleware(); // 获取路由中间件
        // app()->make('router')->getMiddlewareGroups(); // 获取中间件组

        // app('routes') 相当于 app()->make('router')->getRoutes()

        // dd(app()->make('router'));

        // 获取配置
        app('config');
        app('config')->get('key');


        // $this->assertTrue(true);
    }

    public function testConfig()
    {
        dump(config());

        // 配置
        // $disk = config('filesystems.default');
        // dd($disk);

        // \addons\shop\model\Config::get(['name' => 'user'])

        // $user = ShopConfig::where('name', 'user')->first();
        //
        // dump($user->value);

        // 网站配置
        // config('site');
        // 或
        // $config = ConfigService::getConfig(); // 所有
        // ConfigService::getConfig('version'); // 指定name

        // 商店配置
        // $shop_config = ConfigService::getShopConfig(); //所有
        //
        // $u = ConfigService::getShopConfig('user'); // 指定name
        // dump($u);
    }

    public function testLang()
    {
        dd(__('cms::common.No specified article found'));
    }

    public function testRequest()
    {
        dump(request());
        dump(request()->userAgent());
    }

}
