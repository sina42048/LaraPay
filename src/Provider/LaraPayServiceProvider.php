<?php

namespace Sina42048\LaraPay\Provider;

use Illuminate\Support\ServiceProvider;
use Sina42048\LaraPay\LaraPay;

/**
 * class LaraPayServiceProvider
 * @author Sina Fathollahi
 * @package Sina42048\LaraPay\Provider
 */
class LaraPayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('larapay', function($app) {
            $config = config('larapay') ?? [];

            return new LaraPay($config);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__. '/../../config/larapay.php' => config_path("larapay.php")
        ]);
    }
}
