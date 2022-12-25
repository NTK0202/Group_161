<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest\ChangePassRequest;
use App\Http\Requests\AuthRequest\LoginRequest;
use App\Http\Requests\AuthRequest\RegisterRequest;
use App\Http\Requests\DownloadAvatarRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Http\Requests\SearchRequest;
use App\Http\Requests\SearchUserRequest;
use App\Http\Requests\UserInfoRequest;
use App\Models\Avatar;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Qa;
use Exception;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\RefreshTokenRepository;
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
        $this->middleware('auth:api', ['except' => ['login', 'register', 'refreshToken']]);
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
        $content->user = User::where('email', $request->email)->first();

        $content->user->comment_quantity = Comment::where('user_id', $content->user->id)->count();
        $content->user->post_quantity = Post::where('user_id', $content->user->id)->count();
        $content->user->qa_quantity = Qa::where('user_id', $content->user->id)->count();

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
        $user = User::where('email', $request->email)->first();
        if (Hash::check($request->password, $user->password)) {
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

            $content->user->comment_quantity = Comment::where('user_id', $content->user->id)->count();
            $content->user->post_quantity = Post::where('user_id', $content->user->id)->count();
            $content->user->qa_quantity = Qa::where('user_id', $content->user->id)->count();

            return $content;
        } else {
            return response()->json([
                'message' => 'Email or Password is incorrect, please try again !',
            ], Response::HTTP_FORBIDDEN);
        }
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
        $userId = $user->getAttributes()['id'];
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

    public function userProfile()
    {
        /**
         * @var User $user
         */
        $user = auth('api')->user();
        $userId = $user->getAttributes()['id'];
        $userInfo = User::where('id', $userId)->with('avatar')->first();
        $userInfo = json_decode($userInfo);
        unset($user['password']);
        unset($user['remember_token']);
        $user = json_decode($user);
        $user->avatar = $userInfo->avatar->image ?? null;

        $user->comment_quantity = Comment::where('user_id', $user->id)->count();
        $user->post_quantity = Post::where('user_id', $user->id)->count();
        $user->qa_quantity = Qa::where('user_id', $user->id)->count();

        return response()->json($user);
    }

    public function downloadAvatar(DownloadAvatarRequest $request)
    {
        return response()->download(public_path() . '/images/avatars/' . $request->avatar);
    }

    /**
     * @throws Exception
     */
    public function refreshToken(RefreshTokenRequest $request)
    {
        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $request->refresh_token,
            'client_id' => $this->client->id,
            'client_secret' => $this->client->secret,
            'scope' => '*',
        ];
        $token = Request::create('oauth/token', 'POST', $data);

        /**
         * @var \Illuminate\Http\Response $response
         */
        $response = app()->handle($token);

        $data = json_decode($response->content());

        return response()->json($data);
    }

    public function updateUserInfo(UserInfoRequest $request): JsonResponse
    {
        /**
         * @var User $user
         */
        $user = auth('api')->user();
        $userId = $user->getAttributes()['id'];

        $data = $request->all();

        if (isset($data['avatar'])) {
            $user = User::where('id', $userId)->first();
            $userInfo = json_decode(User::where('id', $userId)->with('avatar')->first());

            $avatar = new Avatar;
            $getAvatar = $request->file('avatar');
            $avatarName = $getAvatar->getClientOriginalName();
            $imagePath = public_path() . '/images/avatars';

            $avatar->path = $imagePath;
            $avatar->image = $avatarName;
            $data['avatar'] = $avatarName;

            $getAvatar->move($imagePath, $avatarName);

            if ($userInfo->avatar) {
                $user->avatar()->update(['image' => $avatar->image]);
            } else {
                $user->avatar()->save($avatar);
            }
        }

        if ($data['gender']) {
            $data['gender'] = ($data['gender'] == 'male') ? 1 : 0;
        }

        User::where('id', $userId)->update($data);
        $userInfo = json_decode(User::where('id', $userId)->with('avatar')->first());
        $userInfo->avatar = $userInfo->avatar->image ?? null;
        if ($userInfo->gender == 0 | $userInfo->gender == 1) {
            $userInfo->gender = ($userInfo->gender == 1) ? 'male' : 'female';
        }

        return response()->json($userInfo);
    }

    public function all(SearchUserRequest $request)
    {
        $page = $request->page ?? 1;
        $per_page = $request->per_page ?? 10;
        $orderBy = $request->order_by_created_at ?? 'asc';

        if ($request->name) {
            $likeSearch = "%" . $request->name . "%";
            $users = User::where('name', 'like', $likeSearch)
                ->with('avatar')
                ->orderBy('created_at', $orderBy)
                ->paginate($per_page, ['*'], 'page', $page);
        } else {
            $users = User::all();
        }

        return response()->json($users);
    }
}
