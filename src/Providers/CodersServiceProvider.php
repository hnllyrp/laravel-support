<?php

namespace Hnllyrp\LaravelSupport\Providers;

use Hnllyrp\LaravelSupport\Console\Commands\Model\Factory as ModelFactory;
use Illuminate\Filesystem\Filesystem;
use Reliese\Coders\Model\Config;
use Reliese\Support\Classify;

class CodersServiceProvider extends \Reliese\Coders\CodersServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // 重写命令行 php artisan code:models
        $this->app->singleton(ModelFactory::class, function ($app) {
            return new ModelFactory(
                $app->make('db'),
                $app->make(Filesystem::class),
                new Classify(),
                new Config($app->make('config')->get('models'))
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            // 发布配置文件
            $this->publishes([
                __DIR__ . '/../config/models.php' => config_path('models.php'),
            ], 'reliese-models');
        }
    }
}
