<?php
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


use FridayCollective\LaravelGmail\Http\Controllers\OAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::prefix('oauth')->group(function () {
        Route::prefix('gmail')->group(function () {
            Route::get('/callback', [OAuthController::class, 'gmailCallback']);
        });
    });
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('api')->group(function () {
        Route::get('/mail-config', [OAuthController::class, 'fetchMailConfig']);
        Route::prefix('oauth')->group(function () {
            Route::prefix('gmail')->group(function () {
                Route::get('/', [OAuthController::class, 'gmailRedirect']);
                Route::post('/logout', [OAuthController::class, 'gmailLogout']);
            });
        });
    });
});