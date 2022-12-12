<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest\ChangePassRequest;
use App\Http\Requests\AuthRequest\LoginRequest;
use App\Http\Requests\AuthRequest\RegisterRequest;
use Exception;
use Laravel\Passport\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\RefreshTokenRepository;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{
    private $client;
    private array $data;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
        $this->client = Client::where('password_client', 1)->first();
        $this->data = [
            'grant_type' => 'password',
            'client_id' => $this->client->id,
            'client_secret' => $this->client->secret,
            'username' => '',
            'password' => '',
            'scope' => '*',
        ];
    }

    /**
     * @param  RegisterRequest  $request
     * @return mixed
     * @throws Exception
     */
    public function register(RegisterRequest $request): mixed
    {
        $this->data['username'] = $request->email;
        $this->data['password'] = $request->password;
        $user = User::create(array_merge(
            $request->validated(),
            ['password' => Hash::make($request->password)]
        ));
        event(new Registered($user));
        $token = Request::create('oauth/token', 'POST', $this->data);

        /**
         * @var \Illuminate\Http\Response $response
         */
        $response = app()->handle($token);
        $content = json_decode($response->content());
        $content->message = 'User successfully registered';
        $content->user = $user;

        return $content;
    }

    /**
     * Get a JWT via given credentials.
     *
     * @param  LoginRequest  $request
     * @return mixed
     * @throws Exception
     */
    public function login(LoginRequest $request): mixed
    {
        $this->data['username'] = $request->email;
        $this->data['password'] = $request->password;
        $token = Request::create('oauth/token', 'POST', $this->data);

        /**
         * @var \Illuminate\Http\Response $response
         */
        $response = app()->handle($token);
        $content = json_decode($response->content());
        if ($response->status() == 200) {
            $content->user = User::where('email', $request->email)->first();
        }

        return $content;
    }

    /**
     * Log the member out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        /**
         * @var User $user
         */
        $user = auth('api')->user();
        $tokenId = $user->token()->getAttributes()['id'];
        $tokenRepository = app(TokenRepository::class);
        $refreshTokenRepository = app(RefreshTokenRepository::class);
        // Revoke an access token...
        $tokenRepository->revokeAccessToken($tokenId);
        // Revoke all the token's refresh tokens...
        $refreshTokenRepository->revokeRefreshTokensByAccessTokenId($tokenId);

        return response()->json(['message' => 'Member successfully signed out']);
    }

    public function changePassword(ChangePassRequest $request): JsonResponse
    {
        /**
         * @var User $user
         */
        $user = auth('api')->user();
        $userId = auth()->user()->id;
        $user = User::where('id', $userId)->first();
        if (Hash::check($request->old_password, $user->password)) {
            if (!Hash::check($request->new_password, $user->password)) {
                User::where('id', $userId)->update(
                    ['password' => bcrypt($request->new_password)]
                );

                return response()->json([
                    'message' => 'Member successfully changed password',
                ], Response::HTTP_CREATED);
            } else {
                return response()->json([
                    'message' => 'New password can not be the old password !',
                ], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return response()->json([
                'message' => 'Old password is incorrect !',
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function userProfile(): JsonResponse
    {
        /**
         * @var User $user
         */
        $user = auth('api')->user();
        $user = $user->getAttributes();
        unset($user['password']);
        unset($user['remember_token']);
        return response()->json($user);
    }
}
