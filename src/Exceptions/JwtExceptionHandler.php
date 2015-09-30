<?php

namespace GenTux\Jwt\Exceptions;

trait JwtExceptionHandler
{

    /**
     * Render JWT exception
     *
     * @param JwtException $e
     *
     * @return \Illuminate\Http\Response
     */
    public function handleJwtException(JwtException $e)
    {
        if($e instanceof InvalidTokenException) {
            return $this->handleJwtInvalidToken($e);
        } elseif ($e instanceof NoTokenException) {
            return $this->handleJwtNoToken($e);
        } elseif ($e instanceof NoSecretException) {
            return $this->handleJwtNoSecret($e);
        } else {
            $message = getenv('JWT_MESSAGE_ERROR') ?: 'Something went wrong while validating the token.';
            return response()->json([
                'error' => $message
            ], 500);
        }
    }

    /**
     * @param InvalidTokenException $e
     *
     * @return \Illuminate\Http\Response
     */
    protected function handleJwtInvalidToken(InvalidTokenException $e)
    {
        $message = getenv('JWT_MESSAGE_INVALID') ?: 'Token is not valid.';

        return response()->json(['error' => $message], 401);
    }

    /**
     * @param NoTokenException $e
     *
     * @return \Illuminate\Http\Response
     */
    protected function handleJwtNoToken(NoTokenException $e)
    {
        $message = getenv('JWT_MESSAGE_NOTOKEN') ?: 'Token is required.';

        return response()->json(['error' => $message], 422);
    }

    /**
     * @param NoSecretException $e
     *
     * @return \Illuminate\Http\Response
     */
    protected function handleJwtNoSecret(NoSecretException $e)
    {
        $message = getenv('JWT_MESSAGE_NOSECRET') ?: 'No JWT secret defined.';

        return response()->json(['error' => $message], 500);
    }
}