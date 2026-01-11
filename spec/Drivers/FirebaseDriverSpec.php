<?php

namespace spec\GenTux\Jwt\Drivers;

use Exception;
use Firebase\JWT\JWT;
use Prophecy\Argument;
use PhpSpec\ObjectBehavior;
use GenTux\Jwt\Drivers\FirebaseDriver;
use GenTux\Jwt\Drivers\JwtDriverInterface;

class FirebaseDriverSpec extends ObjectBehavior
{
    public function it_implements_the_driver_interface()
    {
        $this->shouldHaveType(JwtDriverInterface::class);
    }

    public function it_creates_new_tokens()
    {
        $payload = ['foo' => 'bar'];
        $secret = 'secret123';

        $driver = new FirebaseDriver();
        $result = $driver->createToken($payload, $secret);

        $expect = JWT::encode($payload, $secret, 'HS256');
        if($result !== $expect) {
            throw new Exception('Expected '.$expect.' to match '.$result);
        }
    }

    public function it_validates_tokens()
    {
        $token = JWT::encode([
            'exp' => time() + 30,
            'iat' => time(),
            'nbf' => time(),
        ], $secret = 'secret_123', 'HS256');

        $driver = new FirebaseDriver();
        $result = $driver->validateToken($token, $secret);

        if(! $result) throw new Exception('Unable to validate token '.$token);
    }

    public function it_decodes_tokens()
    {
        $token = JWT::encode(
            $payload = [
            'exp' => time() + 30,
            'iat' => time(),
            'nbf' => time(),
            'context' => ['foo' => 'bar'],
        ], $secret = 'secret_123', 'HS256');

        $driver = new FirebaseDriver();
        $result = $driver->decodeToken($token, $secret);

        if(
            $result['exp'] !== $payload['exp']
            || $result['iat'] !== $payload['iat']
            || $result['nbf'] !== $payload['nbf']
            || $result['context']['foo'] !== $payload['context']['foo']
        ) {
            throw new \Exception('Decoded payload did not match the encoded tokens payload. '.$token);
        }
    }

    public function it_returns_false_for_expired_tokens()
    {
        $token = JWT::encode([
            'exp' => time() - 100, // Expired 100 seconds ago
            'iat' => time() - 200,
            'nbf' => time() - 200,
        ], $secret = 'secret_123', 'HS256');

        $driver = new FirebaseDriver();
        $result = $driver->validateToken($token, $secret);

        if($result !== false) {
            throw new Exception('Expected expired token to return false');
        }
    }

    public function it_returns_false_for_invalid_signature()
    {
        $token = JWT::encode([
            'exp' => time() + 30,
            'iat' => time(),
            'nbf' => time(),
        ], 'secret_123', 'HS256');

        $driver = new FirebaseDriver();
        // Try to validate with wrong secret
        $result = $driver->validateToken($token, 'wrong_secret');

        if($result !== false) {
            throw new Exception('Expected invalid signature to return false');
        }
    }

    public function it_returns_false_for_malformed_tokens()
    {
        $driver = new FirebaseDriver();

        // Test with malformed token (not 3 parts)
        $result = $driver->validateToken('not.a.valid.token.format', 'secret');
        if($result !== false) {
            throw new Exception('Expected malformed token to return false');
        }

        // Test with completely invalid string
        $result2 = $driver->validateToken('completely_invalid', 'secret');
        if($result2 !== false) {
            throw new Exception('Expected invalid string to return false');
        }
    }

    public function it_handles_leeway_for_nearly_expired_tokens()
    {
        // Create a token that expired 2 seconds ago
        $token = JWT::encode([
            'exp' => time() - 2,
            'iat' => time() - 100,
            'nbf' => time() - 100,
        ], $secret = 'secret_123', 'HS256');

        // With 5 second leeway, should still be valid
        $driver = new FirebaseDriver(5);
        $result = $driver->validateToken($token, $secret);

        if($result !== true) {
            throw new Exception('Expected token with leeway to be valid');
        }
    }

    public function it_handles_unicode_in_payloads()
    {
        $payload = [
            'exp' => time() + 30,
            'name' => '日本語テスト',
            'emoji' => '🔐🎉',
            'context' => ['greeting' => 'Привет мир'],
        ];

        $driver = new FirebaseDriver();
        $token = $driver->createToken($payload, 'secret_123');
        $decoded = $driver->decodeToken($token, 'secret_123');

        if($decoded['name'] !== '日本語テスト') {
            throw new Exception('Japanese characters not preserved');
        }
        if($decoded['emoji'] !== '🔐🎉') {
            throw new Exception('Emoji not preserved');
        }
        if($decoded['context']['greeting'] !== 'Привет мир') {
            throw new Exception('Cyrillic characters not preserved');
        }
    }

    public function it_handles_deeply_nested_objects()
    {
        $payload = [
            'exp' => time() + 30,
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'value' => 'deep_value'
                        ]
                    ]
                ]
            ]
        ];

        $driver = new FirebaseDriver();
        $token = $driver->createToken($payload, 'secret_123');
        $decoded = $driver->decodeToken($token, 'secret_123');

        if($decoded['level1']['level2']['level3']['level4']['value'] !== 'deep_value') {
            throw new Exception('Deep nesting not preserved');
        }
    }
}
