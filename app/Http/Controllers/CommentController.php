<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Qa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CommentController extends Controller
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
    public function create(CommentRequest $request)
    {
        if ($request->post_id && $request->qa_id) {
            return response()->json([
                'message' => 'Input data is invalid!'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } elseif ($request->post_id) {
            return $this->createCommentForPost($request);
        } else {
            return $this->createCommentForQA($request);
        }
    }

    public function createCommentForPost($request): JsonResponse
    {
        $post = Post::where('id', $request->post_id)->first();

        if (!$post) {
            return response()->json([
                'message' => 'The post does not exist!'
            ], Response::HTTP_BAD_REQUEST);
        } else {
            Comment::create([
                'comment' => $request->comment,
                'post_id' => $request->post_id,
                'user_id' => $this->userId
            ]);

            return response()->json([
                'message' => 'Create comment for post successfully'
            ], Response::HTTP_CREATED);
        }
    }

    public function createCommentForQA($request): JsonResponse
    {
        $qa = Qa::where('id', $request->qa_id)->first();

        if (!$qa) {
            return response()->json([
                'message' => 'The QA does not exist!'
            ], Response::HTTP_BAD_REQUEST);
        } else {
            Comment::create([
                'comment' => $request->comment,
                'qa_id' => $request->qa_id,
                'user_id' => $this->userId
            ]);

            return response()->json([
                'Create comment for QA successfully'
            ], Response::HTTP_CREATED);
        }
    }
}
