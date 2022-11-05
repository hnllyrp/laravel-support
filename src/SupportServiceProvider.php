<?php

namespace Hnllyrp\LaravelSupport;

use Hnllyrp\LaravelSupport\Console\Commands\AppCommand;
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
    }

    /**
     * Boot the provider.
     */
    public function boot()
    {
        // 自定义配置文件
        $this->mergeConfigFrom(__DIR__.'/config/shop.php', 'support');
        // 自定义语言包
        $this->loadTranslationsFrom(__DIR__.'/resources/lang', 'support');// exp:  trans('develop::common.test')
        // 自定义视图
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'support'); // exp: return view('support::admin.index.index');

        $this->commands([
           AppCommand::class
        ]);
    }


}
