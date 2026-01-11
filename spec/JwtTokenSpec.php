<?php

namespace spec\GenTux\Jwt;

use Exception;
use Prophecy\Argument;
use GenTux\Jwt\JwtToken;
use PhpSpec\ObjectBehavior;
use GenTux\Jwt\JwtPayloadInterface;
use GenTux\Jwt\Drivers\FirebaseDriver;
use GenTux\Jwt\Drivers\JwtDriverInterface;
use GenTux\Jwt\Exceptions\NoTokenException;
use GenTux\Jwt\Exceptions\NoSecretException;
use GenTux\Jwt\Exceptions\InvalidAlgorithmException;
use GenTux\Jwt\Exceptions\WeakSecretException;
use GenTux\Jwt\Exceptions\InvalidTokenException;
use PhpSpec\Exception\Example\FailureException;

class JwtTokenSpec extends ObjectBehavior
{
    /** @var string A secret that meets minimum length requirements */
    private $validSecret = 'this_is_a_secret_key_that_is_at_least_32_chars';

    public function let(JwtDriverInterface $jwt)
    {
        $this->beConstructedWith($jwt);
        putenv('JWT_SECRET=' . $this->validSecret);
        putenv('JWT_ALGO=');
        putenv('JWT_STRICT_MODE=');
        putenv('JWT_REQUIRE_EXP=');
    }

    public function letGo()
    {
        // Clean up env after each test
        putenv('JWT_SECRET=');
        putenv('JWT_ALGO=');
        putenv('JWT_STRICT_MODE=');
        putenv('JWT_REQUIRE_EXP=');
    }

    public function it_gets_and_sets_the_current_jwt_token()
    {
        $this->shouldThrow(NoTokenException::class)->during('token');

        $this->setToken('foo_token')->shouldReturn($this);
        $this->token()->shouldReturn('foo_token');
    }

    public function it_gets_and_sets_the_jwt_secret()
    {
        $this->secret()->shouldReturn($this->validSecret); # from env

        $newSecret = 'another_secret_that_is_long_enough_32_chars';
        $this->setSecret($newSecret)->shouldReturn($this); # overwrites env
        $this->secret()->shouldReturn($newSecret);
    }

    public function it_gets_and_sets_the_jwt_algorithm_to_use()
    {
        $this->algorithm()->shouldReturn('HS256'); # default

        putenv('JWT_ALGO=HS384');
        $this->algorithm()->shouldReturn('HS384'); # from env

        $this->setAlgorithm('HS512')->shouldReturn($this); # overwrites env
        $this->algorithm()->shouldReturn('HS512');

        # clear env
        putenv('JWT_ALGO=');
    }

    public function it_throws_exception_for_invalid_algorithm()
    {
        putenv('JWT_ALGO=invalid_algo');
        $this->shouldThrow(InvalidAlgorithmException::class)->during('algorithm');
        putenv('JWT_ALGO=');
    }

    public function it_returns_true_on_validation_if_the_token_is_valid(JwtDriverInterface $jwt)
    {
        $jwt->validateToken('token_123', $this->validSecret, 'HS256')->willReturn(true);

        $this->setToken('token_123');
        $this->validate()->shouldReturn(true);
    }

    public function it_returns_false_on_validation_if_the_token_is_invalid(JwtDriverInterface $jwt)
    {
        $jwt->validateToken('invalid_token', $this->validSecret, 'HS256')->willReturn(false);

        $this->setToken('invalid_token');
        $this->validate()->shouldReturn(false);
    }

    public function it_throws_an_exception_on_validate_or_fail_if_the_token_is_invalid(JwtDriverInterface $jwt)
    {
        $jwt->validateToken('invalid_token', $this->validSecret, 'HS256')->willReturn(false);

        $this->setToken('invalid_token');
        $this->shouldThrow(InvalidTokenException::class)->during('validateOrFail');
    }

    public function it_creates_new_tokens_from_the_provided_payload(JwtDriverInterface $jwt)
    {
        $payload = ['exp' => time() + 3600, 'data' => '123'];
        $jwt->createToken($payload, $this->validSecret, 'HS256')->willReturn('newtoken_123');

        $result = $this->createToken($payload);
        $result->shouldHaveType(JwtToken::class);
        if($result->getWrappedObject()->token() !== 'newtoken_123') throw new \Exception('New token was not set correctly.');
    }

    public function it_creates_new_tokens_from_a_jwt_payload_interface_object(JwtPayloadInterface $payload, JwtDriverInterface $jwt)
    {
        $payloadData = ['exp' => time() + 3600, 'foo' => 'bar'];
        $jwt->createToken($payloadData, $this->validSecret, 'HS256')->willReturn('newtoken_123');
        $payload->getPayload()->willReturn($payloadData);
        $result = $this->createToken($payload);
        $result->shouldHaveType(JwtToken::class);
        if($result->getWrappedObject()->token() !== 'newtoken_123') throw new \Exception('New token was not set correctly.');
    }

    public function it_gets_the_payload_from_the_current_token(JwtDriverInterface $jwt)
    {
        $jwt->decodeToken('token_123', $this->validSecret, 'HS256')->willReturn(['foo' => ['baz' => 'bar']]);

        $this->setToken('token_123');
        $this->payload()->shouldReturn(['foo' => ['baz' => 'bar']]);
    }

    public function it_gets_the_payload_data_from_the_provided_dot_path(JwtDriverInterface $jwt)
    {
        $jwt->decodeToken('token_123', $this->validSecret, 'HS256')->willReturn(['foo' => 'bar', 'context' => ['some' => 'data']]);

        $this->setToken('token_123');
        $this->payload('foo')->shouldReturn('bar');
        $this->payload('context')->shouldReturn(['some' => 'data']);
        $this->payload('context.some')->shouldReturn('data');
    }

    public function it_encodes_to_json_as_a_string_representation_of_the_token(JwtDriverInterface $jwt)
    {
        $driver = new FirebaseDriver();
        $jwt = new JwtToken($driver);
        $token = $jwt->createToken(['exp' => time() + 100], $this->validSecret);

        $serialized = json_encode(['token' => $token]);
        $decoded = json_decode($serialized);

        if( ! is_string($decoded->token) || strlen($decoded->token) < 1) {
            throw new FailureException('Token was not json encoded.');
        }
    }

    public function it_throws_no_secret_exception_when_secret_not_set(JwtDriverInterface $jwt)
    {
        putenv('JWT_SECRET=');
        $this->setSecret(null);
        $this->shouldThrow(NoSecretException::class)->during('secret');
    }

    public function it_warns_for_short_secrets_in_normal_mode()
    {
        // In normal mode, short secrets trigger a warning but don't throw
        putenv('JWT_STRICT_MODE=false');
        $this->setSecret('short');
        // Should not throw, just warn
        $this->secret()->shouldReturn('short');
    }

    public function it_throws_for_short_secrets_in_strict_mode()
    {
        putenv('JWT_STRICT_MODE=true');
        $this->setSecret('short');
        $this->shouldThrow(WeakSecretException::class)->during('secret');
        putenv('JWT_STRICT_MODE=');
    }

    public function it_returns_null_for_nonexistent_payload_path(JwtDriverInterface $jwt)
    {
        $jwt->decodeToken('token_123', $this->validSecret, 'HS256')->willReturn(['foo' => 'bar']);

        $this->setToken('token_123');
        $this->payload('nonexistent')->shouldReturn(null);
        $this->payload('foo.nonexistent')->shouldReturn(null);
    }

    public function it_clones_correctly_when_creating_token(JwtDriverInterface $jwt)
    {
        $payload = ['exp' => time() + 3600];
        $jwt->createToken($payload, $this->validSecret, 'HS256')->willReturn('newtoken_123');

        $this->setToken('original_token');
        $newToken = $this->createToken($payload);

        // Original should still have old token
        $this->token()->shouldReturn('original_token');
        // New token should have new value
        $newToken->token()->shouldReturn('newtoken_123');
    }

    public function it_warns_when_creating_token_without_exp_in_normal_mode(JwtDriverInterface $jwt)
    {
        putenv('JWT_REQUIRE_EXP=false');
        $payload = ['foo' => 'bar']; // No exp claim
        $jwt->createToken($payload, $this->validSecret, 'HS256')->willReturn('newtoken_123');

        // Should succeed but log warning
        $result = $this->createToken($payload);
        $result->shouldHaveType(JwtToken::class);
    }

    public function it_throws_when_creating_token_without_exp_in_strict_mode(JwtDriverInterface $jwt)
    {
        putenv('JWT_STRICT_MODE=true');
        $payload = ['foo' => 'bar']; // No exp claim

        $this->shouldThrow(InvalidTokenException::class)->during('createToken', [$payload]);
        putenv('JWT_STRICT_MODE=');
    }
}
