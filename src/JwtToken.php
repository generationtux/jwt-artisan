<?php

namespace GenTux\Jwt;

use JsonSerializable;
use Illuminate\Support\Arr;
use GenTux\Jwt\Drivers\JwtDriverInterface;
use GenTux\Jwt\Exceptions\NoTokenException;
use GenTux\Jwt\Exceptions\NoSecretException;
use GenTux\Jwt\Exceptions\InvalidTokenException;
use GenTux\Jwt\Exceptions\InvalidAlgorithmException;
use GenTux\Jwt\Exceptions\WeakSecretException;
use GenTux\Jwt\Exceptions\TokenExpiredException;

class JwtToken implements JsonSerializable
{
    /** @var array Allowed algorithms for JWT signing */
    private const ALLOWED_ALGORITHMS = [
        'HS256', 'HS384', 'HS512',
        'RS256', 'RS384', 'RS512',
        'ES256', 'ES384', 'ES512',
        'EdDSA',
    ];

    /** @var int Default minimum secret length */
    private const DEFAULT_MIN_SECRET_LENGTH = 32;

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
        if (!$this->token) {
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
     * @throws WeakSecretException
     */
    public function secret()
    {
        $secret = $this->secret ?: getenv('JWT_SECRET');

        if (!$secret) {
            throw new NoSecretException('Unable to find secret. Set using env variable JWT_SECRET');
        }

        $this->validateSecretStrength($secret);

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
     *
     * @throws InvalidAlgorithmException
     */
    public function algorithm()
    {
        $algorithm = $this->algorithm ?: getenv('JWT_ALGO');
        $algorithm = $algorithm ?: 'HS256';

        $this->validateAlgorithm($algorithm);

        return $algorithm;
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
        if (!$this->validate($secret, $algo)) {
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
        if (is_null($path)) return $payload;

        if (array_key_exists($path, $payload)) {
            return $payload[$path];
        }

        $dotData = Arr::dot($payload);
        if (array_key_exists($path, $dotData)) {
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
     * @param JwtPayloadInterface|array $payload
     * @param string|null               $secret
     * @param string|null               $algo
     *
     * @return JwtToken
     *
     * @throws InvalidTokenException
     * @throws WeakSecretException
     * @throws InvalidAlgorithmException
     */
    public function createToken($payload, $secret = null, $algo = null)
    {
        $algo = $algo ?: $this->algorithm();
        $secret = $secret ?: $this->secret();

        if ($payload instanceof JwtPayloadInterface) {
            $payload = $payload->getPayload();
        }

        // Validate payload expiration
        $this->validatePayloadExpiration($payload);

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
    #[\ReturnTypeWillChange]
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

    /**
     * Check if strict mode is enabled
     *
     * @return bool
     */
    public static function isStrictMode()
    {
        $strict = getenv('JWT_STRICT_MODE');
        return $strict === 'true' || $strict === '1';
    }

    /**
     * Get the minimum secret length
     *
     * @return int
     */
    public static function getMinSecretLength()
    {
        $length = getenv('JWT_MIN_SECRET_LENGTH');
        return $length ? (int) $length : self::DEFAULT_MIN_SECRET_LENGTH;
    }

    /**
     * Validate secret strength
     *
     * In strict mode, throws an exception for weak secrets.
     * In normal mode, triggers a warning log.
     *
     * @param string $secret
     *
     * @return void
     *
     * @throws WeakSecretException
     */
    public function validateSecretStrength($secret)
    {
        $minLength = self::getMinSecretLength();

        if (strlen($secret) < $minLength) {
            $message = "JWT secret is shorter than recommended minimum of {$minLength} characters.";

            if (self::isStrictMode()) {
                throw new WeakSecretException($message);
            }

            // Log warning in non-strict mode (if logger available)
            if (function_exists('app') && app()->bound('log')) {
                app('log')->warning($message);
            } else {
                error_log("[JWT Warning] " . $message);
            }
        }
    }

    /**
     * Validate algorithm is in whitelist
     *
     * In strict mode, throws an exception for non-whitelisted algorithms.
     * In normal mode, logs a warning.
     *
     * @param string $algorithm
     *
     * @return void
     *
     * @throws InvalidAlgorithmException
     */
    public function validateAlgorithm($algorithm)
    {
        if (!in_array($algorithm, self::ALLOWED_ALGORITHMS, true)) {
            $message = "Algorithm '{$algorithm}' is not in the recommended whitelist. Allowed algorithms: " . implode(', ', self::ALLOWED_ALGORITHMS);

            if (self::isStrictMode()) {
                throw new InvalidAlgorithmException($message);
            }

            // Log warning in non-strict mode (if logger available)
            if (function_exists('app') && app()->bound('log')) {
                app('log')->warning($message);
            } else {
                error_log("[JWT Warning] " . $message);
            }
        }
    }

    /**
     * Check if expiration is required
     *
     * @return bool
     */
    public static function isExpirationRequired()
    {
        $required = getenv('JWT_REQUIRE_EXP');
        return $required === 'true' || $required === '1' || self::isStrictMode();
    }

    /**
     * Validate payload has expiration if required
     *
     * @param array $payload
     *
     * @return void
     *
     * @throws InvalidTokenException
     */
    public function validatePayloadExpiration($payload)
    {
        if (!isset($payload['exp'])) {
            $message = "JWT token should include an 'exp' (expiration) claim for security.";

            if (self::isExpirationRequired()) {
                throw new InvalidTokenException($message . " Set JWT_REQUIRE_EXP=false to disable this check.");
            }

            // Log warning in non-strict mode
            if (function_exists('app') && app()->bound('log')) {
                app('log')->warning($message);
            } else {
                error_log("[JWT Warning] " . $message);
            }
        }
    }

    /**
     * Check if header-only mode is enabled
     *
     * @return bool
     */
    public static function isHeaderOnly()
    {
        $headerOnly = getenv('JWT_HEADER_ONLY');
        return $headerOnly === 'true' || $headerOnly === '1';
    }

    /**
     * Get the list of allowed algorithms
     *
     * @return array
     */
    public static function getAllowedAlgorithms()
    {
        return self::ALLOWED_ALGORITHMS;
    }
}
