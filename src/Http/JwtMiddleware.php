<?php

namespace GenTux\Http;

use Closure;
use GenTux\JwtToken;
use GenTux\Exceptions\NoTokenException;

class JwtMiddleware
{

    use GetsJwtToken;

    /** @var JwtToken */
    private $token;

    /**
     * @param JwtToken $token
     */
    public function __construct(JwtToken $token)
    {
        $this->token = $token;
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
        $this->token->validateOrFail($token);

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
