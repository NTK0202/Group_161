<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PostController extends Controller
{
    public function create(PostRequest $request)
    {
        /**
         * @var User $user
         */
        $user = auth('api')->user();
        $userId = $user->getAttributes()['id'];
        $tag = Tag::where('name', $request->tag)->first();
        if (!$tag) {
            $tag = Tag::create(['name' => $request->tag]);
        }
        $tagId = $tag->id;
        Post::create([
            'title' => $request->title,
            'content' => $request->content_post,
            'tag_id' => $tagId,
            'user_id' => $userId
        ]);

        $tagTotal = $tag->total ?? 0;
        Tag::where('id', $tagId)->update(['total' => $tagTotal+1]);

        return response()->json([
            'message' => 'Create post successfully',
        ], Response::HTTP_CREATED);
    }

    public function all()
    {
        $posts = Post::with('user')->with('tag')->get();
        return response()->json($posts);

    }
}
