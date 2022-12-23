<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use \Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PostController extends Controller
{
    private ?\Illuminate\Contracts\Auth\Authenticatable $user;
    private mixed $userId;

    public function __construct()
    {
        /**
         * @var User $user
         */
        $this->user = auth('api')->user();
        $this->userId = $this->user->getAttributes()['id'];
    }
    public function create(PostRequest $request): JsonResponse
    {
        $tag = Tag::where('name', $request->tag)->first();
        if (!$tag) {
            $tagId = Tag::create(['name' => $request->tag])->id;
        } else {
            $tagId = $tag->id;
        }
        Post::create([
            'title' => $request->title,
            'content' => $request->content_post,
            'tag_id' => $tagId,
            'user_id' => $this->userId
        ]);

        $tagTotal = $tag->total ?? 0;
        Tag::where('id', $tagId)->update(['total' => $tagTotal+1]);

        return response()->json([
            'message' => 'Create post successfully',
        ], Response::HTTP_CREATED);
    }

    public function show(): JsonResponse
    {
        $posts = Post::where('user_id', $this->userId)->with('tag')->get();
        return response()->json($posts);
    }

    public function all(): JsonResponse
    {
        $posts = Post::with('user')->with('tag')->get();
        return response()->json($posts);

    }
}
