<?php

namespace GenTux\Jwt;

use JsonSerializable;
use Illuminate\Support\Arr;
use GenTux\Jwt\Drivers\JwtDriverInterface;
use GenTux\Jwt\Exceptions\NoTokenException;
use GenTux\Jwt\Exceptions\NoSecretException;
use GenTux\Jwt\Exceptions\InvalidTokenException;

class JwtToken implements JsonSerializable
{

    /** @var JwtDriverInterface */
    private $jwt;

    /** @var string|null */
    private $secret;

    /** @var string|null */
    private $algorithm;

    /** @var string|null current token */
    private $token;

    /**
     * @param JwtDriverInterface $jwt
     * @param string|null        $secret
     * @param string|null        $algorithm
     */
    public function __construct(JwtDriverInterface $jwt, $secret = null, $algorithm = null)
    {
        $this->jwt = $jwt;
        $this->secret = $secret;
        $this->algorithm = $algorithm;
    }

    /**
     * Get the current JWT token
     *
     * @return string
     *
     * @throws NoTokenException
     */
    public function token()
    {
        if( ! $this->token) {
            throw new NoTokenException('No token has been set.');
        }

        return $this->token;
    }

    /**
     * Set the current JWT token
     *
     * @param string $token
     *
     * @return self
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get the secret to use for token operations
     *
     * @return string
     *
     * @throws NoSecretException
     */
    public function secret()
    {
        $secret = $this->secret ?: getenv('JWT_SECRET');

        if(! $secret) {
            throw new NoSecretException('Unable to find secret. Set using env variable JWT_SECRET');
        }

        return $secret;
    }

    /**
     * Set the secret to use for token operations
     *
     * @param string $secret
     *
     * @return self
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Get the algorithm to use
     *
     * This can be customized by setting the env variable JWT_ALGO
     *
     * @return string
     */
    public function algorithm()
    {
        $algorithm = $this->algorithm ?: getenv('JWT_ALGO');

        return $algorithm ?: 'HS256';
    }

    /**
     * Set the algorithm to use
     *
     * @param string $algo
     *
     * @return self
     */
    public function setAlgorithm($algo)
    {
        $this->algorithm = $algo;

        return $this;
    }

    /**
     * Validate a token
     *
     * @param string|null $secret
     * @param string|null $algo
     *
     * @return bool
     */
    public function validate($secret = null, $algo = null)
    {
        $token = $this->token();
        $secret = $secret ?: $this->secret();
        $algo = $algo ?: $this->algorithm();

        return $this->jwt->validateToken($token, $secret, $algo);
    }

    /**
     * Validate the token or throw an exception
     *
     * @param string|null $secret
     * @param string|null $algo
     *
     * @return bool
     *
     * @throws InvalidTokenException
     */
    public function validateOrFail($secret = null, $algo = null)
    {
        if( ! $this->validate($secret, $algo)) {
            throw new InvalidTokenException('Token is not valid.');
        }

        return true;
    }

    /**
     * Get the payload from the current token
     *
     * @param string|null $path    dot syntax to query for specific data
     * @param string|null $secret
     * @param string|null $algo
     *
     * @return array
     */
    public function payload($path = null, $secret = null, $algo = null)
    {
        $token = $this->token();
        $secret = $secret ?: $this->secret();
        $algo = $algo ?: $this->algorithm();

        $payload = $this->jwt->decodeToken($token, $secret, $algo);

        return $this->queryPayload($payload, $path);
    }

    /**
     * Query the payload using dot syntax to find specific data
     *
     * @param array       $payload
     * @param string|null $path
     *
     * @return mixed
     */
    private function queryPayload($payload, $path = null)
    {
        if(is_null($path)) return $payload;

        if(array_key_exists($path, $payload)) {
            return $payload[$path];
        }

        $dotData = Arr::dot($payload);
        if(array_key_exists($path, $dotData)) {
            return $dotData[$path];
        }

        return null;
    }

    /**
     * Create a new token with the provided payload
     *
     * The default algorithm used is HS256. To set a custom one, set
     * the env variable JWT_ALGO.
     *
     * @todo Support for enforcing required claims in payload as well as defaults
     *
     * @param JwtPayloadInterface|array $payload
     * @param string|null               $secret
     * @param string|null               $algo
     *
     * @return JwtToken
     */
    public function createToken($payload, $secret = null, $algo = null)
    {
        $algo = $algo ?: $this->algorithm();
        $secret = $secret ?: $this->secret();

        if($payload instanceof JwtPayloadInterface) {
            $payload = $payload->getPayload();
        }

        $newToken = $this->jwt->createToken($payload, $secret, $algo);

        $token = clone $this;
        $token->setToken($newToken);

        return $token;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->token();
    }


    /**
     * Convert into string
     *
     * @return string
     *
     * @throws NoTokenException
     */
    public function __toString()
    {
        return $this->token();
    }
}
