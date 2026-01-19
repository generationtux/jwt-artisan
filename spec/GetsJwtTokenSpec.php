<?php

namespace spec\GenTux\Jwt;

use Prophecy\Argument;
use PhpSpec\ObjectBehavior;
use Illuminate\Http\Request;
use GenTux\Jwt\JwtToken;
use GenTux\Jwt\Drivers\JwtDriverInterface;
use GenTux\Jwt\Exceptions\NoTokenException;
use GenTux\Jwt\Tests\Support\GetsJwtTokenTestClass;

class GetsJwtTokenSpec extends ObjectBehavior
{
    private $validSecret = 'this_is_a_secret_key_that_is_at_least_32_chars';

    public function let(Request $request, JwtDriverInterface $driver)
    {
        $this->beAnInstanceOf(GetsJwtTokenTestClass::class);
        $this->setMockRequest($request);
        $this->setMockDriver($driver);

        putenv('JWT_SECRET=' . $this->validSecret);
        putenv('JWT_HEADER=');
        putenv('JWT_INPUT=');
        putenv('JWT_HEADER_ONLY=');
    }

    public function letGo()
    {
        putenv('JWT_SECRET=');
        putenv('JWT_HEADER=');
        putenv('JWT_INPUT=');
        putenv('JWT_HEADER_ONLY=');
    }

    public function it_gets_token_from_authorization_header(Request $request)
    {
        $request->header('Authorization')->willReturn('Bearer my_jwt_token');

        $this->getToken($request)->shouldReturn('my_jwt_token');
    }

    public function it_gets_token_from_custom_header(Request $request)
    {
        putenv('JWT_HEADER=X-Custom-Auth');
        $request->header('X-Custom-Auth')->willReturn('Bearer custom_token');

        $this->getToken($request)->shouldReturn('custom_token');
    }

    public function it_falls_back_to_input_when_header_missing(Request $request)
    {
        $request->header('Authorization')->willReturn(null);
        $request->input('token')->willReturn('input_token');

        $this->getToken($request)->shouldReturn('input_token');
    }

    public function it_uses_custom_input_name(Request $request)
    {
        putenv('JWT_INPUT=jwt');
        $request->header('Authorization')->willReturn(null);
        $request->input('jwt')->willReturn('custom_input_token');

        $this->getToken($request)->shouldReturn('custom_input_token');
    }

    public function it_returns_null_when_no_token_found(Request $request)
    {
        $request->header('Authorization')->willReturn(null);
        $request->input('token')->willReturn(null);

        $this->getToken($request)->shouldReturn(null);
    }

    public function it_does_not_fallback_to_input_in_header_only_mode(Request $request)
    {
        putenv('JWT_HEADER_ONLY=true');
        $request->header('Authorization')->willReturn(null);
        // input() should not be called in header-only mode

        $this->getToken($request)->shouldReturn(null);
    }

    public function it_creates_jwt_token_object_from_request(Request $request, JwtDriverInterface $driver)
    {
        $request->header('Authorization')->willReturn('Bearer valid_token');

        $result = $this->jwtToken($request);
        $result->shouldHaveType(JwtToken::class);
        $result->token()->shouldReturn('valid_token');
    }

    public function it_throws_no_token_exception_when_token_missing(Request $request)
    {
        $request->header('Authorization')->willReturn(null);
        $request->input('token')->willReturn(null);

        $this->shouldThrow(NoTokenException::class)->during('jwtToken', [$request]);
    }

    public function it_gets_payload_from_token(Request $request, JwtDriverInterface $driver)
    {
        $request->header('Authorization')->willReturn('Bearer payload_token');
        $driver->decodeToken('payload_token', $this->validSecret, 'HS256')
            ->willReturn(['sub' => '123', 'exp' => time() + 3600]);

        $this->jwtPayload(null, $request)->shouldReturn(['sub' => '123', 'exp' => time() + 3600]);
    }

    public function it_gets_specific_payload_path(Request $request, JwtDriverInterface $driver)
    {
        $request->header('Authorization')->willReturn('Bearer payload_token');
        $driver->decodeToken('payload_token', $this->validSecret, 'HS256')
            ->willReturn(['sub' => '123', 'context' => ['role' => 'admin']]);

        $this->jwtPayload('sub', $request)->shouldReturn('123');
        $this->jwtPayload('context.role', $request)->shouldReturn('admin');
    }

    public function it_handles_bearer_with_lowercase(Request $request)
    {
        // Note: sscanf is case-sensitive, so 'bearer' won't match 'Bearer %s'
        // This test documents current behavior
        $request->header('Authorization')->willReturn('bearer lowercase_token');
        $request->input('token')->willReturn('fallback_token');

        // Will fall back to input because 'bearer' doesn't match 'Bearer'
        $this->getToken($request)->shouldReturn('fallback_token');
    }

    public function it_handles_empty_authorization_header(Request $request)
    {
        $request->header('Authorization')->willReturn('');
        $request->input('token')->willReturn('fallback_token');

        $this->getToken($request)->shouldReturn('fallback_token');
    }

    public function it_handles_malformed_bearer_token(Request $request)
    {
        $request->header('Authorization')->willReturn('Bearer');
        $request->input('token')->willReturn('fallback_token');

        // sscanf returns null for the token part when there's nothing after 'Bearer '
        $this->getToken($request)->shouldReturn('fallback_token');
    }
}
