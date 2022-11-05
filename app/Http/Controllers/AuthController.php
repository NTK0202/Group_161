<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest\ChangePassRequest;
use App\Http\Requests\AuthRequest\LoginRequest;
use App\Http\Requests\AuthRequest\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;


class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @param  LoginRequest  $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (!$token = auth()->attempt($request->validated())) {
            return response()->json([
                'error' => 'Email or Password is incorrect, please try again !'
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->createNewToken($token);
    }

    /**
     * Log the member out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json(['message' => 'Member successfully signed out']);
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return JsonResponse
     */
    protected function createNewToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => getenv('JWT_TTL'),
            'member' => auth()->user(),
            'role' => auth()->user()->memberId->role_id ?? 3,
        ], Response::HTTP_OK);
    }

    public function changePassword(ChangePassRequest $request): JsonResponse
    {
        $userId = auth()->user()->id;
        $user = User::where('id', $userId)->first();
        if (Hash::check($request->old_password, $user->password)) {
            if (!Hash::check($request->new_password, $user->password)) {
                $user = User::where('id', $userId)->update(
                    ['password' => bcrypt($request->new_password)]
                );

                return response()->json([
                    'message' => 'Member successfully changed password',
                    'member_id' => $userId,
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

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create(array_merge(
            $request->validated(),
            ['password' => bcrypt($request->password)]
        ));

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], Response::HTTP_CREATED);
    }

    public function refresh(): JsonResponse
    {
        return $this->createNewToken(auth()->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function userProfile(): JsonResponse
    {
        return response()->json(auth()->user());
    }
}
