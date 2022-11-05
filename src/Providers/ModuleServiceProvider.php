<?php

namespace Hnllyrp\LaravelSupport\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * 注册模块提供者（简单版本 laravel-modules）
 * Class ModuleServiceProvider
 */
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $providers = glob($this->app->path('Module/*/*ServiceProvider.php'));

        $namespace = $this->app->getNamespace(); // APP

        foreach ($providers as $provider) {
            $providerClass = $namespace . str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($provider, realpath(app_path()) . DIRECTORY_SEPARATOR)
                );

            $this->app->register($providerClass);
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

}
