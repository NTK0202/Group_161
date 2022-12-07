<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest\ForgotPasswordRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PasswordReset;
use App\Notifications\ResetPasswordNotice;
use App\Http\Requests\AuthRequest\ResetPasswordRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ResetPasswordController extends Controller
{
    /**
     * Create token password reset.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function sendMail(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->firstOrFail();
        $passwordReset = PasswordReset::updateOrCreate([
            'email' => $user->email,
        ], [
            'token' => Str::random(60),
        ]);
        if ($passwordReset) {
            $user->notify(new ResetPasswordNotice($passwordReset->token));
        }

        return response()->json([
            'message' => 'We have e-mailed your password reset link!'
        ], Response::HTTP_OK);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $passwordReset = PasswordReset::where('token', $request->token)->firstOrFail();
        if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
            $passwordReset->delete();

            return response()->json([
                'message' => 'This password reset token is invalid.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $user = User::where('email', $passwordReset->email)->firstOrFail();
        $updatePasswordUser = $user->update(['password' => bcrypt($request->password)]);
        $passwordReset->delete();

        return response()->json([
            'success' => $updatePasswordUser,
        ], Response::HTTP_OK);
    }
}
