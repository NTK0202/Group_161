<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagRequest;
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
        $tag = Tag::where('name', $request->tag)->first();
        $tagForPost = Post::where('tag_id', $tag->id)->with('user')->get();

        return response()->json($tagForPost);
    }

    public function tagForQA(TagRequest $request): JsonResponse
    {
        $tag = Tag::where('name', $request->tag)->first();
        $tagForQA = Qa::where('tag_id', $tag->id)->with('user')->get();

        return response()->json($tagForQA);
    }
}
