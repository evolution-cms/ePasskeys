<?php

use EvolutionCMS\ePasskeys\Http\Controllers\AuthOptionsController;
use EvolutionCMS\ePasskeys\Http\Controllers\AuthenticateController;
use EvolutionCMS\ePasskeys\Http\Controllers\DeletePasskeyController;
use EvolutionCMS\ePasskeys\Http\Controllers\PasskeysController;
use EvolutionCMS\ePasskeys\Http\Controllers\RegisterController;
use EvolutionCMS\ePasskeys\Http\Controllers\RegisterOptionsController;
use EvolutionCMS\ePasskeys\Support\Config;
use EvolutionCMS\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

$prefix = Config::getContextRoutePrefix('mgr');
$globalMiddleware = config('app.middleware.global', []);

$csrfMiddleware = array_merge($globalMiddleware, [VerifyCsrfToken::class]);

Route::prefix($prefix)->group(function () use ($globalMiddleware, $csrfMiddleware) {
    Route::middleware($globalMiddleware)->group(function () {
        Route::get('auth/options', AuthOptionsController::class);
    });

    Route::middleware($csrfMiddleware)->group(function () {
        Route::post('auth', AuthenticateController::class);
    });

    Route::middleware('mgr')->group(function () use ($globalMiddleware, $csrfMiddleware) {
        Route::middleware($globalMiddleware)->group(function () {
            Route::get('register/options', RegisterOptionsController::class);
            Route::get('credentials', PasskeysController::class);
        });

        Route::middleware($csrfMiddleware)->group(function () {
            Route::post('register', RegisterController::class);
            Route::post('credentials/{id}/delete', DeletePasskeyController::class);
        });
    });
});
