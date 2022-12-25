<?php

namespace App\Http\Controllers;

use App\Http\Requests\DetailRequest;
use App\Http\Requests\PostRequest;
use App\Http\Requests\SearchRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Qa;
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
        $page = $request->page ?? 1;
        $per_page = $request->per_page ?? 10;
        $orderBy = $request->order_by_created_at ?? 'asc';

        if ($request->title) {
            $likeSearch = "%" . $request->title . "%";
            $posts = Post::where('user_id', $this->userId)
                ->where('title', 'like', $likeSearch)
                ->with('tag')
                ->with('user')
                ->orderBy('created_at', $orderBy)
                ->paginate($per_page, ['*'], 'page', $page);
        } else {
            $posts = Post::where('user_id', $this->userId)
                ->with('tag')
                ->with('user')
                ->orderBy('created_at', $orderBy)
                ->paginate($per_page, ['*'], 'page', $page);
        }

        foreach ($posts as $key => $post) {
            $posts[$key]['user']['comment_quantity'] = Comment::where('user_id', $this->userId)->count();
            $posts[$key]['user']['post_quantity'] = Post::where('user_id', $this->userId)->count();
            $posts[$key]['user']['qa_quantity'] = Qa::where('user_id', $this->userId)->count();
        }

        return response()->json($posts);
    }

    public function all(PostRequest $request): JsonResponse
    {
        $page = $request->page ?? 1;
        $per_page = $request->per_page ?? 10;
        $orderBy = $request->order_by_created_at ?? 'asc';

        $posts = Post::with('user')
            ->with('tag')
            ->orderBy('created_at', $orderBy)
            ->paginate($per_page, ['*'], 'page', $page);

        foreach ($posts as $key => $post) {
            $posts[$key]['user']['comment_quantity'] = Comment::where('user_id', $this->userId)->count();
            $posts[$key]['user']['post_quantity'] = Post::where('user_id', $this->userId)->count();
            $posts[$key]['user']['qa_quantity'] = Qa::where('user_id', $this->userId)->count();
        }

        return response()->json($posts);
    }


    public function search(SearchRequest $request): JsonResponse
    {
        $page = $request->page ?? 1;
        $per_page = $request->per_page ?? 10;
        $likeSearch = "%" . $request->title . "%";
        $orderBy = $request->order_by_created_at ?? 'asc';

        if ($request->title) {
            $posts = Post::where('title', 'like', $likeSearch)
                ->with('user')
                ->with('tag')
                ->orderBy('created_at', $orderBy)
                ->paginate($per_page, ['*'], 'page', $page);
        } else {
            $posts = Post::with('user')
                ->with('tag')
                ->orderBy('created_at', $orderBy)
                ->paginate($per_page, ['*'], 'page', $page);
        }

        return response()->json($posts);
    }


    public function detail(DetailRequest $request): JsonResponse
    {
        $post = json_decode(Post::where('id', $request->id)->with('user')->with('tag')->first());
        $comment = Comment::where('post_id', $request->id)->with('user')->get();
        $commentQuantity = Comment::where('post_id', $request->id)->count();
        $post->comment_quantity = $commentQuantity;
        $post->comment = $comment;

        return response()->json($post);
    }
}
