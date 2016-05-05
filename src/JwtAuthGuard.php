<?php
/**
 * Created by hopewise
 * User: samir
 * Date: 4/27/2016
 * Time: 4:29 PM
 */
namespace App\Http\Guard;
use BadMethodCallException;
use Closure;
use GenTux\Jwt\GetsJwtToken;
use GenTux\Jwt\JwtToken;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Laravel\Lumen\Http\ResponseFactory;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
class JwtAuthGuard implements Guard
{
    use GetsJwtToken;
    use GuardHelpers;
    /**
     * The user we last attempted to retrieve.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $lastAttempted;
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;
    /**+
     * JwtAuthGuard constructor.
     * @param UserProvider $provider
     * @param Request $request
     * @internal param ResponseFactory $factory
     */
    public function __construct(UserProvider $provider, Request $request)
    {
        $this->provider = $provider;
        $this->request = $request;
    }
    /**
     * @param $request
     * @param Closure $next
     * @return string
     */
    public function handle($request, Closure $next)
    {
        $factory = new ResponseFactory();
        if (!$this->jwtToken()->validate()) {
            return $factory->make('Unauthorized.', 401);
        } else {
            $response = $next($request);
            return $response;
        }
    }
    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }
        $token = $this->jwtToken();
        if ($this->jwtToken()->validate() == false)
            return null;
        $user_id = $token->payload('user_id');
        return $this->user = $this->provider->retrieveById($user_id);
    }
    /**
     * @param array $credentials
     * @param bool $remember
     * @param bool $login
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false, $login = true)
    {
        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);
        // If an implementation of UserInterface was returned, we'll ask the provider
        // to validate the user against the given credentials, and if they are in
        // fact valid we'll log the users into the application and return true.
        if ($this->hasValidCredentials($user, $credentials)) {
            if ($login) {
                return $this->login($user, $credentials);
            }
            return true;
        }
        return false;
    }
    protected function hasValidCredentials($user, $credentials)
    {
        return !is_null($user) && $this->provider->validateCredentials($user, $credentials);
    }
    public function login(AuthenticatableContract $user, $credentials)
    {
        $this->setUser($user);
        /*
         * Build a valid token and return it to the user
         */
        $payload = [
            'exp' => time() + (60 * 10), // expire in 30 days! #2 hours
            'user_id' => $user->id];
        $payload = array_merge($payload);
        $driver = $this->makeDriver();
        $token = new JwtToken($driver);
        $token = $token->createToken($payload)->token();
        return $token;
    }
    /**
     * Validate a user's credentials.
     *
     * @param  array $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        return true;
    }
}
