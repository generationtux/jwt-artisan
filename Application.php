<?php

/**
 * Created by PhpStorm.
 * User: Samir Sabri
 * Date: 3/30/2016
 * Time: 3:43 PM
 */
namespace App;

use Cwt137\PubnubDriver\PubnubBroadcaster;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Application as LumenApplication;
use Pubnub\Pubnub;

class Application extends LumenApplication {

    /**
     * Create a new Lumen application instance.
     *
     * @param  string|null  $basePath
     * @return void
     */
    public function __construct($basePath = null) {
        parent::__construct($basePath);

        $this->bindPathsInContainer();
    }

    /**
     * Bind all of the application paths in the container.
     *
     * @return void
     */
    protected function bindPathsInContainer() {

        $this->instance('path', $this->path());
        foreach (['base', 'database', 'storage', 'config'] as $path) {
            $this->instance('path.'.$path, $this->{$path.'Path'}());
        }
    }

    /**
     * Get the path to the application "app" directory.
     *
     * @return string
     */
    public function path() {
        return $this->basePath.DIRECTORY_SEPARATOR.'app';
    }

    public function configPath()
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'config';
    }

}
