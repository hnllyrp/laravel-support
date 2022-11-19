<?php

namespace Hnllyrp\LaravelSupport\Providers;

use Hnllyrp\LaravelSupport\Support\Commands\Model\CodeModelsCommand;
use Hnllyrp\LaravelSupport\Support\Commands\Model\Factory as ModelFactory;
use Illuminate\Filesystem\Filesystem;
use Reliese\Coders\CodersServiceProvider as ServiceProvider;
use Reliese\Coders\Model\Config;
use Reliese\Support\Classify;

class CodersServiceProvider extends ServiceProvider
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
        // 本地开发
        if ($this->app->environment() == 'local' && config('app.debug')) {
            $this->commands([
                CodeModelsCommand::class
            ]);
        }


        if ($this->app->runningInConsole()) {
            // 发布配置文件
            $this->publishes([
                __DIR__ . '/../config/models.php' => config_path('models.php'),
            ], 'reliese-models');
        }
    }
}
