<?php

namespace spec\GenTux\Jwt\Http;

use Prophecy\Argument;
use GenTux\Jwt\JwtToken;
use PhpSpec\ObjectBehavior;
use Illuminate\Http\Request;
use GenTux\Jwt\Exceptions\NoTokenException;
use GenTux\Jwt\Exceptions\InvalidTokenException;

class JwtMiddlewareSpec extends ObjectBehavior
{

    public function let(JwtToken $token)
    {
        $this->beConstructedWith($token);
    }

    public function it_validates_the_token_and_passes_onto_the_next_middleware(JwtToken $token, Request $request)
    {
        $request->header('Authorization')->willReturn('Bearer foo_token');

        $token->setToken('foo_token')->willReturn($token);
        $token->validateOrFail()->shouldBeCalled()->willReturn(true);

        $next = function() { return 'hello world'; };
        $this->handle($request, $next)->shouldReturn('hello world');
    }

    public function it_validates_the_token_with_custom_header_key_and_passes_onto_the_next_middleware(JwtToken $token, Request $request)
    {
        $customHeader = 'X-Test-AuthHeader';
        putenv("JWT_HEADER=$customHeader");

        $request->header($customHeader)->willReturn('Bearer foo_token');

        $token->setToken('foo_token')->willReturn($token);
        $token->validateOrFail()->shouldBeCalled()->willReturn(true);

        $next = function() { return 'hello world'; };
        $this->handle($request, $next)->shouldReturn('hello world');

        putenv('JWT_HEADER=');
    }

    public function it_throws_an_exception_if_the_token_is_invalid(JwtToken $token, Request $request)
    {
        $request->header('Authorization')->willReturn(null);
        $request->input('token')->willReturn('invalid_token');

        $token->setToken('invalid_token')->willReturn($token);
        $token->validateOrFail()->willThrow(InvalidTokenException::class);

        $next = function() {};
        $this->shouldThrow(InvalidTokenException::class)->during('handle', [$request, $next]);
    }

    public function it_throws_an_exception_if_no_token_is_provided_with_the_request(Request $request)
    {
        $request->header('Authorization')->willReturn(null);
        $request->input('token')->willReturn(null);

        $next = function() {};
        $this->shouldThrow(NoTokenException::class)->during('handle', [$request, $next]);
    }
}
