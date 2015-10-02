<?php

namespace GenTux\Jwt\Drivers;

use Firebase\JWT\JWT;

class FirebaseDriver implements JwtDriverInterface
{

    /**
     * @param int $leeway for checking timestamps
     */
    public function __construct($leeway = null)
    {
        $leeway = $leeway ?: getenv('JWT_LEEWAY');
        JWT::$leeway = $leeway ?: 0;
    }

    /**
     * Validate that the provided token
     *
     * @param string $token
     * @param string $secret
     * @param string $algorithm
     *
     * @return bool
     */
    public function validateToken($token, $secret, $algorithm = 'HS256')
    {
        try {
            JWT::decode($token, $secret, [$algorithm]);
        } catch(\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Create a new token with the provided payload
     *
     * @param array  $payload
     * @param string $secret
     * @param string $algorithm
     *
     * @return string
     */
    public function createToken($payload, $secret, $algorithm = 'HS256')
    {
        return JWT::encode($payload, $secret, $algorithm);
    }

    /**
     * Decode the provided token into an array
     *
     * @param string $token
     * @param string $secret
     * @param string $algorithm
     *
     * @return array
     */
    public function decodeToken($token, $secret, $algorithm = 'HS256')
    {
        $decoded = JWT::decode($token, $secret, [$algorithm]);

        return $this->convertObjectToArray($decoded);
    }

    /**
     * Recursively convert the provided object to an array
     *
     * @param mixed $data
     *
     * @return array
     */
    private function convertObjectToArray($data)
    {
        $converted = [];
        foreach($data as $key => $value) {
            if(is_object($value)) {
                $converted[$key] = $this->convertObjectToArray($value);
            } else {
                $converted[$key] = $value;
            }
        }

        return $converted;
    }
}
