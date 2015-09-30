<?php

namespace GenTux\Jwt;


interface JwtPayloadInterface
{

    /**
     * Get the payload for JWT token
     *
     * @return array
     */
    public function getPayload();
}