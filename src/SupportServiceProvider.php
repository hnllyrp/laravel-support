<?php

namespace Hnllyrp\LaravelSupport;

use Hnllyrp\LaravelSupport\Console\Commands\AppCommand;
use Hnllyrp\LaravelSupport\Support\Macros\Builder\WhereHasIn;
use Illuminate\Database\Eloquent;
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
            // 重写命令行 php artisan code:models
            $this->app->register(\Hnllyrp\LaravelSupport\Providers\CodersServiceProvider::class);
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
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'support');// exp:  trans('develop::common.test')
        // 自定义视图
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'support'); // exp: return view('support::admin.index.index');

        $this->commands([
            AppCommand::class
        ]);
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
