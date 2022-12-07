<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\VerifyEmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
        Route::put('/change-password', 'changePassword');
        Route::put('/refresh-token', 'refresh');
    });

Route::prefix('auth')
    ->middleware(['api'])
    ->controller(ResetPasswordController::class)
    ->group(function () {
        Route::post('forgot-password', 'sendMail');
        Route::put('forgot-password/{token}', 'reset');
    });


