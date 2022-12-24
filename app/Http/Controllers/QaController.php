<?php

namespace App\Http\Controllers;

use App\Http\Requests\DetailRequest;
use App\Http\Requests\QaRequest;
use App\Http\Requests\SearchRequest;
use App\Models\Comment;
use App\Models\Qa;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class QaController extends Controller
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
    public function create(QaRequest $request): JsonResponse
    {
        $tag = Tag::where('name', $request->tag)->first();
        if (!$tag) {
            $tag = Tag::create(['name' => $request->tag])->id;
        }
        $tagId = $tag->id;
        Qa::create([
            'title' => $request->title,
            'content' => $request->content_qa,
            'tag_id' => $tagId,
            'user_id' => $this->userId
        ]);

        $tagTotal = $tag->total;
        Tag::where('id', $tagId)->update(['total' => $tagTotal+1]);

        return response()->json([
            'message' => 'Create QA successfully',
        ], Response::HTTP_CREATED);
    }

    public function show(QaRequest $request): JsonResponse
    {
        $orderBy = $request->order_by_created_at ?? 'asc';

        $qas = Qa::where('user_id', $this->userId)
            ->with('tag')
            ->orderBy('created_at', $orderBy)
            ->get();

        return response()->json($qas);
    }

    public function all(QaRequest $request): JsonResponse
    {
        $orderBy = $request->order_by_created_at ?? 'asc';

        $qas = Qa::with('user')
            ->with('tag')
            ->orderBy('created_at', $orderBy)
            ->get();

        return response()->json($qas);
    }

    public function search(SearchRequest $request): JsonResponse
    {
        $likeSearch = "%" . $request->title . "%";
        $orderBy = $request->order_by_created_at ?? 'asc';
        $qas = Qa::where('title', 'like', $likeSearch)
                ->with('user')
                ->with('tag')
                ->orderBy('created_at', $orderBy)
                ->get();

        return response()->json($qas);
    }

    public function detail(DetailRequest $request): JsonResponse
    {
        $qa = json_decode(Qa::where('id', $request->id)->with('tag')->first());
        $comment = Comment::where('qa_id', $request->id)->get();
        $commentQuantity = Comment::where('qa_id', $request->id)->count();
        $qa->comment_quantity = $commentQuantity;
        $qa->comment = $comment;

        return response()->json($qa);
    }
}
