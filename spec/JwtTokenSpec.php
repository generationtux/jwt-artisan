<?php

namespace spec\GenTux\Jwt;

use Prophecy\Argument;
use GenTux\Jwt\JwtToken;
use PhpSpec\ObjectBehavior;
use GenTux\Jwt\JwtPayloadInterface;
use GenTux\Jwt\Drivers\FirebaseDriver;
use GenTux\Jwt\Drivers\JwtDriverInterface;
use GenTux\Jwt\Exceptions\NoTokenException;
use PhpSpec\Exception\Example\FailureException;
use GenTux\Jwt\Exceptions\InvalidTokenException;

class JwtTokenSpec extends ObjectBehavior
{

    public function let(JwtDriverInterface $jwt)
    {
        $this->beConstructedWith($jwt);
        putenv('JWT_SECRET=secret_123');
    }

    public function it_gets_and_sets_the_current_jwt_token()
    {
        $this->shouldThrow(NoTokenException::class)->during('token');

        $this->setToken('foo_token')->shouldReturn($this);
        $this->token()->shouldReturn('foo_token');
    }

    public function it_gets_and_sets_the_jwt_secret()
    {
        $this->secret()->shouldReturn('secret_123'); # from env

        $this->setSecret('another_secret')->shouldReturn($this); # overwrites env
        $this->secret()->shouldReturn('another_secret');
    }

    public function it_gets_and_sets_the_jwt_algorithm_to_use()
    {
        $this->algorithm()->shouldReturn('HS256'); # default

        putenv('JWT_ALGO=foo');
        $this->algorithm()->shouldReturn('foo'); # from env

        $this->setAlgorithm('custom')->shouldReturn($this); # overwrites env
        $this->algorithm()->shouldReturn('custom');

        # clear env
        putenv('JWT_ALGO=');
    }

    public function it_returns_true_on_validation_if_the_token_is_valid(JwtDriverInterface $jwt)
    {
        $jwt->validateToken('token_123', 'secret_123', 'HS256')->willReturn(true);

        $this->setToken('token_123');
        $this->validate()->shouldReturn(true);
    }

    public function it_returns_false_on_validation_if_the_token_is_invalid(JwtDriverInterface $jwt)
    {
        $jwt->validateToken('invalid_token', 'secret_123', 'HS256')->willReturn(false);

        $this->setToken('invalid_token');
        $this->validate()->shouldReturn(false);
    }

    public function it_throws_an_exception_on_validate_or_fail_if_the_token_is_invalid(JwtDriverInterface $jwt)
    {
        $jwt->validateToken('invalid_token', 'secret_123', 'HS256')->willReturn(false);

        $this->setToken('invalid_token');
        $this->shouldThrow(InvalidTokenException::class)->during('validateOrFail');
    }

    public function it_creates_new_tokens_from_the_provided_payload(JwtDriverInterface $jwt)
    {
        $jwt->createToken(['exp' => '123'], 'secret_123', 'HS256')->willReturn('newtoken_123');

        $result = $this->createToken(['exp' => '123'])->shouldHaveType(JwtToken::class);
        if($result->token() !== 'newtoken_123') throw new \Exception('New token was not set correctly.');
    }

    public function it_creates_new_tokens_from_a_jwt_payload_interface_object(JwtPayloadInterface $payload, JwtDriverInterface $jwt)
    {
        $jwt->createToken(['foo' => 'bar'], 'secret_123', 'HS256')->willReturn('newtoken_123');
        $payload->getPayload()->willReturn(['foo' => 'bar']);

        $result = $this->createToken($payload)->shouldHaveType(JwtToken::class);
        if($result->token() !== 'newtoken_123') throw new \Exception('New token was not set correctly.');
    }

    public function it_gets_the_payload_from_the_current_token(JwtDriverInterface $jwt)
    {
        $jwt->decodeToken('token_123', 'secret_123', 'HS256')->willReturn(['foo' => ['baz' => 'bar']]);

        $this->setToken('token_123');
        $this->payload()->shouldReturn(['foo' => ['baz' => 'bar']]);
    }

    public function it_gets_the_payload_data_from_the_provided_dot_path(JwtDriverInterface $jwt)
    {
        $jwt->decodeToken('token_123', 'secret_123', 'HS256')->willReturn(['foo' => 'bar', 'context' => ['some' => 'data']]);

        $this->setToken('token_123');
        $this->payload('foo')->shouldReturn('bar');
        $this->payload('context')->shouldReturn(['some' => 'data']);
        $this->payload('context.some')->shouldReturn('data');
    }

    public function it_encodes_to_json_as_a_string_representation_of_the_token(JwtDriverInterface $jwt)
    {
        $driver = new FirebaseDriver();
        $jwt = new JwtToken($driver);
        $token = $jwt->createToken(['exp' => time() + 100], 'secret');

        $serialized = json_encode(['token' => $token]);
        $decoded = json_decode($serialized);

        if( ! is_string($decoded->token) || strlen($decoded->token) < 1) {
            throw new FailureException('Token was not json encoded.');
        }
    }
}
