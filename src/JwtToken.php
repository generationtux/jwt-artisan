<?php

namespace GenTux;

use GenTux\Drivers\JwtDriverInterface;
use GenTux\Exceptions\NoTokenException;
use GenTux\Exceptions\NoSecretException;
use GenTux\Exceptions\InvalidTokenException;

class JwtToken
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
     * @param string|null $secret
     * @param string|null $algo
     *
     * @return Collection
     */
    public function payload($secret = null, $algo = null)
    {
        $token = $this->token();
        $secret = $secret ?: $this->secret();
        $algo = $algo ?: $this->algorithm();

        return $this->jwt->decodeToken($token, $secret, $algo);
    }

    /**
     * Create a new token with the provided payload
     *
     * The default algorithm used is HS256. To set a custom one, set
     * the env variable JWT_ALGO.
     *
     * @todo Support for enforcing required claims in payload
     *
     * @param array|object $payload
     * @param string|null  $secret
     * @param string|null  $algo
     *
     * @return JwtToken
     */
    public function createToken($payload, $secret = null, $algo = null)
    {
        $algo = $algo ?: $this->algorithm();
        $secret = $secret ?: $this->secret();
        $payload = (array) $payload;

        $newToken = $this->jwt->createToken($payload, $secret, $algo);

        $token = clone $this;
        $token->setToken($newToken);

        return $token;
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
