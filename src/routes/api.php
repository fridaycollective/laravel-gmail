<?php

use FridayCollective\LaravelGmail\Http\Controllers\OAuthController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::get('/oauth/gmail/callback', [OAuthController::class, 'gmailCallback']);
});

Route::middleware(['api', Config::get('gmail.middleware')])->group(function () {
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