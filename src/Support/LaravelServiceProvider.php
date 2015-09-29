<?php

namespace GenTux\Support;

use GenTux\Http\JwtMiddleware;

class LaravelServiceProvider extends ServiceProvider
{

    /**
     * Register middlewares for JWT that can be used in routes file
     */
    protected function registerMiddleware()
    {
        $router = $this->app['router'];
        $router->middleware('jwt', JwtMiddleware::class);
    }
}