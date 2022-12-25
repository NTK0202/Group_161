<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Qa;
use App\Models\Tag;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    private Collection $tags;

    public function __construct()
    {
        $this->tags = Tag::select('*')->orderBy('total', 'desc')->get();
        foreach ($this->tags as $tag) {
            $tag['tag_for_post'] = Tag::find($tag->id)->posts()->count();
            $tag['tag_for_qa'] = Tag::find($tag->id)->qas()->count();
        }
    }

    public function all(): JsonResponse
    {
        return response()->json($this->tags);
    }

    public function tagForPost(TagRequest $request): JsonResponse
    {
        $page = $request->page ?? 1;
        $per_page = $request->per_page ?? 10;
        $orderBy = $request->order_by_created_at ?? 'asc';

        $tag = Tag::where('name', $request->tag)->first();
        $tagForPosts = Post::where('tag_id', $tag->id)
            ->with('user')
            ->with('tag')
            ->orderBy('created_at', $orderBy)
            ->paginate($per_page, ['*'], 'page', $page);

        foreach ($tagForPosts as $key => $post) {
            $tagForPosts[$key]['comment_quantity'] = Comment::where('post_id', $post->id)->count();
        }

        return response()->json($tagForPosts);
    }

    public function tagForQA(TagRequest $request): JsonResponse
    {
        $page = $request->page ?? 1;
        $per_page = $request->per_page ?? 10;
        $orderBy = $request->order_by_created_at ?? 'asc';

        $tag = Tag::where('name', $request->tag)->first();

        $tagForQA = Qa::where('tag_id', $tag->id)
            ->with('user')
            ->with('tag')
            ->orderBy('created_at', $orderBy)
            ->paginate($per_page, ['*'], 'page', $page);

        foreach ($tagForQA as $key => $qa) {
            $tagForQA[$key]['comment_quantity'] = Comment::where('qa_id', $qa->id)->count();
        }

        return response()->json($tagForQA);
    }
}
