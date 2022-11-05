<?php

namespace Hnllyrp\LaravelSupport\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * 插件服务提供者
 * Class AddonServiceProvider
 */
class AddonServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $services = glob(app_path('Addons/*/*ServiceProvider.php'));

        foreach ($services as $service) {
            $slice = explode('/', $service);

            $module = $slice[count($slice) - 2];

            $this->app->register('App\Addons\\' . $module . '\\' . basename($service, '.php'));
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
