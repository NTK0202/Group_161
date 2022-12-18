<?php

namespace App\Http\Controllers;

use App\Http\Requests\QaRequest;
use App\Models\Qa;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class QaController extends Controller
{
    public function create(QaRequest $request): JsonResponse
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
        Qa::create([
            'title' => $request->title,
            'content' => $request->content_qa,
            'tag_id' => $tagId,
            'user_id' => $userId
        ]);

        $tagTotal = $tag->total;
        Tag::where('id', $tagId)->update(['total', $tagTotal+1]);

        return response()->json([
            'message' => 'Create QA successfully',
        ], Response::HTTP_CREATED);
    }

    public function all(): JsonResponse
    {
        $qas = Qa::with('user')->with('tag')->get();

        return response()->json($qas);
    }
}