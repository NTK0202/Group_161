<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\QaController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\VerifyEmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::prefix('auth')
    ->middleware(['api'])
    ->controller(AuthController::class)
    ->group(function () {
        Route::post('/register', 'register');
        Route::post('/login', 'login');
        Route::delete('/logout', 'logout');
        Route::post('/change-password', 'changePassword');
        Route::post('/refresh-token', 'refreshToken');
        Route::get('/user-profile', 'userProfile');
        Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
    });

Route::prefix('auth')
    ->middleware(['api'])
    ->controller(ResetPasswordController::class)
    ->group(function () {
        Route::post('forgot-password', 'sendMail');
        Route::post('forgot-password/reset', 'reset');
    });

Route::prefix('post')
    ->middleware(['api', 'auth:api'])
    ->controller(PostController::class)
    ->group(function () {
        Route::post('create', 'create');
        Route::get('all', 'all');
        Route::get('show', 'show');
    });

Route::prefix('qa')
    ->middleware(['api', 'auth:api'])
    ->controller(QaController::class)
    ->group(function () {
        Route::post('create', 'create');
        Route::get('all', 'all');
        Route::get('show', 'show');
    });

Route::prefix('tag')
    ->middleware(['api', 'auth:api'])
    ->controller(TagController::class)
    ->group(function () {
        Route::get('all', 'all');
        Route::get('tag-for-post', 'tagForPost');
    });
