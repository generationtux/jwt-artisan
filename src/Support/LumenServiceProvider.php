<?php

namespace GenTux\Jwt\Support;

use GenTux\Jwt\Http\JwtMiddleware;

class LumenServiceProvider extends ServiceProvider
{

    /**
     * Register middlewares for JWT that can be used in routes file
     */
    protected function registerMiddleware()
    {
        $this->app->routeMiddleware(['jwt' => JwtMiddleware::class]);
    }
}