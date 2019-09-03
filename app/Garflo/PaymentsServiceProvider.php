<?php

namespace App\Garflo;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class PaymentsServiceProvider extends ServiceProvider
{

    /**
     * Boot the configuration service for the application
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . 'config/payu.php' => config_path('payu.php'),
        ]);

        $this->mergeConfigFrom(
            __DIR__ . 'config/payu.php', 'payu'
        );
    }

    /**
     * Register the service provider
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
