<?php

namespace spec\GenTux\Jwt;

use Prophecy\Argument;
use GenTux\Jwt\JwtToken;
use PhpSpec\ObjectBehavior;
use GenTux\Jwt\Drivers\JwtDriverInterface;
use GenTux\Jwt\Exceptions\NoTokenException;
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

    public function it_gets_the_payload_from_the_current_token(JwtDriverInterface $jwt)
    {
        $jwt->decodeToken('token_123', 'secret_123', 'HS256')->willReturn(['foo' => ['baz' => 'bar']]);

        $this->setToken('token_123');
        $this->payload()->shouldReturn(['foo' => ['baz' => 'bar']]);
    }
}
