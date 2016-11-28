<?php
/**
 * Created by PhpStorm.
 * User: samir
 * Date: 4/27/2016
 * Time: 4:32 PM
 */
namespace app\Providers;
use App\Http\Guard\JwtAuthGuard;
use Illuminate\Support\ServiceProvider;
class JwtAuthGuardServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['auth']->extend('jwt-auth-trackware', function ($app, $name, array $config) {
            $guard = new JwtAuthGuard(
                $app['auth']->createUserProvider($config['provider']),
                $app['request']
            );
            $app->refresh('request', $guard, 'setRequest');
            return $guard;
        });
    }
}
