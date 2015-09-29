<?php

namespace GenTux\Support;

use GenTux\Drivers\FirebaseDriver;
use GenTux\Drivers\JwtDriverInterface;
use Illuminate\Support\ServiceProvider;

class JwtServiceProvider extends ServiceProvider
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
}