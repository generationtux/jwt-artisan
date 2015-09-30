<?php

namespace GenTux\Jwt\Http;

use Closure;
use GenTux\Jwt\JwtToken;
use GenTux\Jwt\GetsJwtToken;
use GenTux\Jwt\Exceptions\NoTokenException;

class JwtMiddleware
{

    use GetsJwtToken;

    /** @var JwtToken */
    private $jwt;

    /**
     * @param JwtToken $jwt
     */
    public function __construct(JwtToken $jwt)
    {
        $this->jwt = $jwt;
    }

    /**
     * Validate JWT token before passing on to the next middleware
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure                  $next
     */
    public function handle($request, Closure $next)
    {
        $token = $this->getTokenFromRequest($request);
        $this->jwt->setToken($token)->validateOrFail();

        return $next($request);
    }

    /**
     * Get the token from the request
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return string
     *
     * @throws NoTokenException
     */
    private function getTokenFromRequest($request)
    {
        $token = $this->getToken($request);

        if( ! $token) {
            throw new NoTokenException('JWT token is required.');
        }

        return $token;
    }
}
