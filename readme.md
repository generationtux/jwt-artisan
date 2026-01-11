# JWT Artisan

![Build Test Status](https://github.com/generationtux/jwt-artisan/actions/workflows/test.yml/badge.svg?event=push)

## Token auth for Laravel and Lumen web artisans

### Requirements

- PHP 8.2, 8.3, or 8.4
- Laravel 10.x, 11.x, or 12.x (or Lumen equivalent)

[JWT](http://jwt.io/) is a great solution for authenticating API requests between various services. This package
makes working with JWT super easy for both [Laravel](http://laravel.com/) and [Lumen](http://lumen.laravel.com/).

### Why JWT?

Because you have

![microservices](http://i.imgur.com/GJlYD83.jpg)

That need to authenticate with each other so you can turn away bad requests like

![how bout no](http://i.imgur.com/BMPy7AG.png)

Which is why JWT makes you feel like

![yea baby](http://i.imgur.com/lySEl7O.jpg)

### Contents

- [Setup](#setup)
- [Configure](#configure)
- [Working with Tokens](#working-with-tokens)
- [Security](#security)
- [Development](#development)

## Setup

Install the package using composer

    $ composer require generationtux/jwt-artisan

Add the appropriate service provider for Laravel/Lumen

```php
// Laravel
// config/app.php
'providers' => [
    ...
    GenTux\Jwt\Support\LaravelServiceProvider::class,
]

// Lumen
// bootstrap/app.php
$app->register(GenTux\Jwt\Support\LumenServiceProvider::class);
```

## Configure

All configuration for this package can be set using environment variables. The reason for using environment variables instead
of config files is so that it can be integrated with both Laravel & Lumen as easily as possible. See the table below
for the available config options and their defaults.

| Config       | Default         | Description                                                                                                                                                                             |
| ------------ | --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `JWT_SECRET` | _null_          | The secret key that will be used for sigining/validating tokens.                                                                                                                        |
| `JWT_ALGO`   | _HS256_         | The algorithm to use for sigining tokens.                                                                                                                                               |
| `JWT_LEEWAY` | _0_             | Seconds of leeway for validating timestamps to account for time differences between systems                                                                                             |
| `JWT_INPUT`  | _token_         | By default we will look for the token in the `Authorization` header. If it's not found there, then this value will be used to search the sent input from the request to find the token. |
| `JWT_HEADER` | _Authorization_ | By default the `Authorization` header key is used. This can be overridden with this value.                                                                                              |

If you're using the `JwtExceptionHandler` to handle exceptions, these environment variables can be set to customize the error messages.
_(see below for information on using the exception handler)_

| Config                 | Default                                                        | Description                                                                          |
| ---------------------- | -------------------------------------------------------------- | ------------------------------------------------------------------------------------ |
| `JWT_MESSAGE_ERROR`    | _There was an error while validating the authorization token._ | `500` A general error occured while working with the token.                          |
| `JWT_MESSAGE_INVALID`  | _Authorization token is not valid._                            | `401` The provided token is invalid in some way: expired, mismatched signature, etc. |
| `JWT_MESSAGE_NOTOKEN`  | _Authorization token is required._                             | `401` There was no token found with the request.                                     |
| `JWT_MESSAGE_NOSECRET` | _No JWT secret defined._                                       | `500` Unable to find the JWT secret for validating/signing tokens.                   |

### Security Configuration

These environment variables control security features. All are disabled by default for backward compatibility.

| Config | Default | Description |
| ------ | ------- | ----------- |
| `JWT_STRICT_MODE` | _false_ | Enable all strict security validations (enforces secret length and token expiration) |
| `JWT_HEADER_ONLY` | _false_ | Only accept tokens from the Authorization header (disables query string/body fallback) |
| `JWT_REQUIRE_EXP` | _false_ | Require `exp` claim in all tokens |
| `JWT_MIN_SECRET_LENGTH` | _32_ | Minimum secret length (warning in normal mode, error in strict mode) |

**Allowed Algorithms:** HS256, HS384, HS512, RS256, RS384, RS512, ES256, ES384, ES512, EdDSA (non-whitelisted algorithms log a warning; throw in strict mode)

## Working with Tokens

- [Creating Tokens](#creating-tokens)
- [Validating Tokens](#validating-tokens)
- [Payloads](#payloads)
- [Handling Errors](#handling-errors)

### Creating Tokens

Inject an instance of `GenTux\Jwt\JwtToken` into your controller or other service to create new tokens.

```php
<?php

use GenTux\Jwt\JwtToken;

class TokensController extends controller
{
        public function create(JwtToken $jwt)
        {
                $payload = ["exp" => time() + 7200]; // expire in 2 hours
                $token = $jwt->createToken($payload); // new instance of JwtToken

                return (string) $token;
        }
}
```

Implement `GenTux\Jwt\JwtPayloadInterface` to pass other objects to `createToken` for a more dynamic payload.

```php
<?php

use GenTux\Jwt\JwtPayloadInterface;

class User extends Model implements JwtPayloadInterface
{
        public function getPayload()
        {
                return [
                        "sub" => $this->id,
                        "exp" => time() + 7200,
                        "context" => [
                                "email" => $this->email,
                        ],
                ];
        }
}
```

Then simply pass that object when creating the token

```php
<?php

use GenTux\Jwt\JwtToken;

class TokensController extends controller
{
        public function create(JwtToken $jwt)
        {
                $user = User::find(1);
                $token = $jwt->createToken($user);

                return $token->payload(); // ['sub' => 1, exp => '...', 'context' => ...]
        }
}
```

You can set a specific `secret` and `algorithm` to use if necessary

```php
public function create(JwtToken $jwt)
{
    return $jwt
            ->setSecret('secret_123')
            ->setAlgorithm('custom')
            ->createToken('[...]');
}
```

### Validating Tokens

The easiest way to validate a request with a JWT token is to use the provided middleware.

```php
<?php

// Laravel
Route::group(["middleware" => "jwt"], function () {
        Route::post("/foo", "FooController");
});

// Lumen
$app->group(
        ["middleware" => "jwt", "namespace" => "App\Http\Controllers"],
        function ($app) {
                $app->post("/foo", "FooController");
        }
);
```

When a token is invalid, `GenTux\Jwt\Exceptions\InvalidTokenException` will be thrown. If no token is provided,
then `GenTux\Jwt\Exceptions\NoTokenException` will be thrown.

To manually validate the token, you can get tokens in any class using the trait `GenTux\Jwt\GetsJwtToken`.

For example, in a **Laravel** request object

```php
<?php

use GenTux\Jwt\GetsJwtToken;

class CreateUser extends FormRequest
{
        use GetsJwtToken;

        public function authorize()
        {
                return $this->jwtToken()->validate();
        }
}
```

Or in a controller for **Lumen**

```php
<?php

use GenTux\Jwt\GetsJwtController;

class FooController extends controller
{
    use GetsJwtToken;

    public function store()
    {
        if( ! $this->jwtToken()->validate()) {
            return redirect('/nope');
        }

        ...
    }
}
```

### Payloads

Once you have the token, working with the payload is easy.

```php
<?php

use GenTux\Jwt\GetsJwtToken;

class TokenService
{
        use GetsJwtToken;

        public function getExpires()
        {
                $payload = $this->jwtPayload(); // shortcut for $this->jwtToken()->payload()

                return $payload["exp"];
        }
}
```

The `payload` method for JwtToken accepts a `path` that can be used to get specific data from the payload.

```php
<?php

use GenTux\Jwt\GetsJwtToken;

class TokenService
{
        use GetsJwtToken;

        public function getData()
        {
                // ['exp' => '123', 'context' => ['foo' => 'bar']]

                $token = $this->jwtToken();
                $token->payload("exp"); // 123
                $token->payload("context.foo"); // bar
                $token->payload("context.baz"); // null
        }
}
```

### Handling Errors

This package can handle JWT exceptions out of the box if you would like. It will take all JWT exceptions
and return JSON error responses. If you would like to implements your own error handling, you can look
at `GenTux\Jwt\Exceptions\JwtExceptionHandler` for an example.

To implement, add the following inside of `app/Exceptions/Handler.php`

```php
<?php

use GenTux\Jwt\Exceptions\JwtException;
use GenTux\Jwt\Exceptions\JwtExceptionHandler;

class Handler extends ExceptionHandler
{
    use JwtExceptionHandler;

    public function render($request, Exception $e)
    {
        if($e instanceof JwtException) return $this->handleJwtException($e);

        ...
    }
}
```

## Security

### Best Practices

1. **Use Strong Secrets**: Your `JWT_SECRET` should be at least 32 characters and generated using a cryptographically secure random generator. Weak secrets are vulnerable to brute-force attacks.

   ```bash
   # Generate a secure secret
   php -r "echo bin2hex(random_bytes(32));"
   ```

2. **Always Set Token Expiration**: Include an `exp` claim in your tokens. Long-lived tokens increase the window of exposure if compromised.

   ```php
   $payload = [
       'sub' => $userId,
       'exp' => time() + 3600, // 1 hour
   ];
   ```

3. **Use Header-Only Mode in Production**: Enable `JWT_HEADER_ONLY=true` to prevent tokens from being accepted via query strings, which can leak through logs and referrer headers.

4. **Enable Strict Mode for New Projects**: Set `JWT_STRICT_MODE=true` to enforce all security validations. This mode:
   - Throws an exception for secrets shorter than 32 characters
   - Requires `exp` claim in all tokens
   - Validates algorithms against the whitelist

5. **Use HTTPS**: Always transmit tokens over HTTPS to prevent interception.

### Strict Mode

Enable strict mode for enhanced security:

```bash
JWT_STRICT_MODE=true
```

This automatically enables:
- Secret length validation (throws exception for weak secrets)
- Token expiration requirement
- Algorithm whitelist enforcement (throws exception for non-whitelisted algorithms)

## Development

### Running Tests Locally

This package uses Docker to ensure consistent test environments across PHP versions. Before pushing code, run the test suite to verify all tests pass.

**Run tests on default PHP version (8.4):**

```bash
docker-compose run --rm php composer install
docker-compose run --rm php ./vendor/bin/phpspec run -c phpspec.yml
```

**Run tests on a specific PHP version:**

```bash
# Build and test on PHP 8.2
PHP_VERSION=8.2 docker-compose build php
PHP_VERSION=8.2 docker-compose run --rm php composer install
PHP_VERSION=8.2 docker-compose run --rm php ./vendor/bin/phpspec run -c phpspec.yml

# Build and test on PHP 8.3
PHP_VERSION=8.3 docker-compose build php
PHP_VERSION=8.3 docker-compose run --rm php composer install
PHP_VERSION=8.3 docker-compose run --rm php ./vendor/bin/phpspec run -c phpspec.yml
```

### CI Matrix

The GitHub Actions workflow tests all supported PHP and Laravel version combinations:

| PHP | Laravel 10 | Laravel 11 | Laravel 12 |
|-----|------------|------------|------------|
| 8.2 | ✓ | ✓ | ✓ |
| 8.3 | ✓ | ✓ | ✓ |
| 8.4 | ✓ | ✓ | ✓ |

Before pushing, ensure tests pass on at least PHP 8.2 (the minimum supported version) to catch any compatibility issues.
