<?php

namespace GenTux\Jwt\Drivers;

interface JwtDriverInterface
{

    /**
     * Validate that the provided token
     *
     * @param string $token
     * @param string $secret
     * @param string $algorithm
     *
     * @return bool
     */
    public function validateToken($token, $secret, $algorithm = 'HS256');

    /**
     * Create a new token with the provided payload
     *
     * @param array  $payload
     * @param string $secret
     * @param string $algorithm
     *
     * @return string
     */
    public function createToken($payload, $secret, $algorithm = 'HS256');

    /**
     * Decode the provided token into an array
     *
     * @param string $token
     * @param string $secret
     * @param string $algorithm
     *
     * @return array
     */
    public function decodeToken($token, $secret, $algorithm = 'HS256');
}