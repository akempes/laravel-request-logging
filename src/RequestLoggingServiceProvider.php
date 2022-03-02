<?php

namespace Akempes\RequestLogging;

use Illuminate\Support\ServiceProvider;

class RequestLoggingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/request-logging.php' => config_path('request-logging.php')
        ], 'config');
        
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations')
        ], 'migrations');
    }


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/request-logging.php', 'request-logging');
    }
}
