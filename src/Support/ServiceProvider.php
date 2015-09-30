<?php

namespace GenTux\Jwt\Support;

use GenTux\Jwt\Drivers\FirebaseDriver;
use GenTux\Jwt\Drivers\JwtDriverInterface;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

abstract class ServiceProvider extends BaseServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(JwtDriverInterface::class, function($app) {
            return $app->make(FirebaseDriver::class);
        });
    }

    /**
     * Boot services for JWT
     */
    public function boot()
    {
        $this->registerMiddleware();
    }

    /**
     * Register middlewares that can be used for routes
     */
    abstract protected function registerMiddleware();
}