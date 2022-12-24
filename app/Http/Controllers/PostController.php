<?php

namespace App\Http\Controllers;

use App\Http\Requests\DetailRequest;
use App\Http\Requests\PostRequest;
use App\Http\Requests\SearchRequest;
use App\Models\Comment;
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

    public function show(PostRequest $request): JsonResponse
    {
        $orderBy = $request->order_by_created_at ?? 'asc';

        $posts = Post::where('user_id', $this->userId)
            ->with('tag')
            ->orderBy('created_at', $orderBy)
            ->get();

        return response()->json($posts);
    }

    public function all(PostRequest $request): JsonResponse
    {
        $orderBy = $request->order_by_created_at ?? 'asc';
        $posts = Post::with('user')
                ->with('tag')
                ->orderBy('created_at', $orderBy)
                ->get();

        return response()->json($posts);
    }


    public function search(SearchRequest $request): JsonResponse
    {
        $likeSearch = "%" . $request->title . "%";
        $orderBy = $request->order_by_created_at ?? 'asc';
        $posts = Post::where('title', 'like', $likeSearch)
            ->with('user')
            ->with('tag')
            ->orderBy('created_at', $orderBy)
            ->get();

        return response()->json($posts);
    }


    public function detail(DetailRequest $request): JsonResponse
    {
        $post = json_decode(Post::where('id', $request->id)->with('tag')->first());
        $comment = Comment::where('post_id', $request->id)->with('user')->get();
        $commentQuantity = Comment::where('post_id', $request->id)->count();
        $post->comment_quantity = $commentQuantity;
        $post->comment = $comment;

        return response()->json($post);
    }
}
