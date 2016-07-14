<?php

namespace GenTux\Jwt;

use Illuminate\Http\Request;
use GenTux\Jwt\Drivers\JwtDriverInterface;
use GenTux\Jwt\Exceptions\NoTokenException;

/**
 * This trait can be used to retrieve JWT tokens
 * from the current request.
 */
trait GetsJwtToken
{

    /**
     * Get the JWT token from the request
     *
     * We'll check the Authorization header first, and if that's not set
     * then check the input to see if its provided there instead.
     *
     * @param Request|null $request
     *
     * @return string|null
     */
    public function getToken($request = null)
    {
        $request = $request ?: $this->makeRequest();

        list($token) = sscanf($request->header($this->getAuthHeaderKey()), 'Bearer %s');
        if( ! $token) {
            $name = $this->getInputName();
            $token = $request->input($name);
        }

        return $token;
    }

    /**
     * Create a new JWT token object from the token in the request
     *
     * @param Request|null $request
     *
     * @return JwtToken
     *
     * @throws NoTokenException
     */
    public function jwtToken($request = null)
    {
        $token = $this->getToken($request);
        if( ! $token) throw new NoTokenException('JWT token is required.');

        $driver = $this->makeDriver();
        $jwt = new JwtToken($driver);
        $jwt->setToken($token);

        return $jwt;
    }

    /**
     * Get payload from JWT token
     *
     * @param string|null  $path    to query payload
     * @param Request|null $request
     *
     * @return array
     */
    public function jwtPayload($path = null, $request = null)
    {
        $jwt = $this->jwtToken($request);

        return $jwt->payload($path);
    }

    /**
     * Get the input name to search for the token in the request
     *
     * This can be customized by setting the JWT_INPUT env variable.
     * It will default to using `token` if not defined.
     *
     * @return string
     */
    private function getInputName()
    {
        return getenv('JWT_INPUT') ?: 'token';
    }

    /**
     * Get the header key to search for the token
     *
     * This can be customized by setting the JWT_HEADER env variable.
     * It will default to using `Authorization` if not defined.
     *
     * @return string
     */
    private function getAuthHeaderKey()
    {
        return getenv('JWT_HEADER') ?: 'Authorization';
    }

    /**
     * Create a driver to use for the token from the IoC
     *
     * @return JwtDriverInterface
     */
    private function makeDriver()
    {
        return app(JwtDriverInterface::class);
    }

    /**
     * Resolve the current request from the IoC
     *
     * @return \Illuminate\Http\Request
     */
    private function makeRequest()
    {
        if($this instanceof Request) return $this;

        return app(Request::class);
    }
}