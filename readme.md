# JWT Artisan

## Token auth for Laravel and Lumen web artisans

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

| Config       | Default | Description                                                      |
| ------------ | ------- | ---------------------------------------------------------------- |
| `JWT_SECRET` | *null*  | The secret key that will be used for sigining/validating tokens. |
| `JWT_ALGO`   | *HS256* | The algorithm to use for sigining tokens.                        |
| `JWT_LEEWAY` | *0*     | Seconds of leeway for validating timestamps to account for time differences between systems |
| `JWT_INPUT`  | *token* | By default we will look for the token in the `Authorization` header. If it's not found there, then this value will be used to search the sent input from the request to find the token. |
| `JWT_HEADER`  | *Authorization* | By default the `Authorization` header key is used. This can be overridden with this value. |

If you're using the `JwtExceptionHandler` to handle exceptions, these environment variables can be set to customize the error messages.
*(see below for information on using the exception handler)*

| Config                   | Default                                                         | Description                                                        |
| ------------------------ | --------------------------------------------------------------- | ------------------------------------------------------------------ |
| `JWT_MESSAGE_ERROR`      | *There was an error while validating the authorization token.*  | `500` A general error occured while working with the token.        |
| `JWT_MESSAGE_INVALID`    | *Authorization token is not valid.*                             | `401` The provided token is invalid in some way: expired, mismatched signature, etc. |
| `JWT_MESSAGE_NOTOKEN`    | *Authorization token is required.*                              | `401` There was no token found with the request.                   |
| `JWT_MESSAGE_NOSECRET`   | *No JWT secret defined.*                                        | `500` Unable to find the JWT secret for validating/signing tokens. |


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
        $payload = ['exp' => time() + 7200]; // expire in 2 hours
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
            'sub' => $this->id,
            'exp' => time() + 7200,
            'context' => [
                'email' => $this->email
            ]
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
Route::group(['middleware' => 'jwt'], function() {
    Route::post('/foo', 'FooController');
});

// Lumen
$app->group(['middleware' => 'jwt', 'namespace' => 'App\Http\Controllers'], function($app)  {
    $app->post('/foo', 'FooController');
});
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

        return $payload['exp'];
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
        $token->payload('exp'); // 123
        $token->payload('context.foo'); // bar
        $token->payload('context.baz'); // null
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
