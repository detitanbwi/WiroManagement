<?php

use App\Http\Controllers\Api\TrackerApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('tracker')->group(function () {
    // Accounts
    Route::get('/accounts', [TrackerApiController::class, 'getAccounts']);
    Route::post('/accounts', [TrackerApiController::class, 'storeAccount']);
    Route::put('/accounts/{id}', [TrackerApiController::class, 'updateAccount']);
    Route::delete('/accounts/{id}', [TrackerApiController::class, 'deleteAccount']);

    // Categories
    Route::get('/categories', [TrackerApiController::class, 'getCategories']);
    Route::post('/categories', [TrackerApiController::class, 'storeCategory']);
    Route::put('/categories/{id}', [TrackerApiController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [TrackerApiController::class, 'deleteCategory']);

    // Expenses
    Route::get('/expenses', [TrackerApiController::class, 'getExpenses']);
    Route::post('/expenses/sync', [TrackerApiController::class, 'syncExpenses']);
    Route::delete('/expenses/{id}', [TrackerApiController::class, 'deleteExpense']);
});

// Wiromitra Client Portal API (v1)
Route::prefix('v1/mitra')->group(function () {
    // Public / Onboarding
    Route::post('/auth/verify-token', [\App\Http\Controllers\Api\Mitra\MitraAuthController::class, 'verifyToken']);
    Route::post('/auth/set-password', [\App\Http\Controllers\Api\Mitra\MitraAuthController::class, 'setPassword']);
    Route::post('/auth/login', [\App\Http\Controllers\Api\Mitra\MitraAuthController::class, 'login']);

    // Protected (Sanctum Token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [\App\Http\Controllers\Api\Mitra\MitraAuthController::class, 'logout']);
        Route::get('/me', [\App\Http\Controllers\Api\Mitra\MitraAuthController::class, 'me']);
        Route::get('/dashboard', [\App\Http\Controllers\Api\Mitra\MitraDashboardController::class, 'index']);
        Route::get('/projects', [\App\Http\Controllers\Api\Mitra\MitraProjectController::class, 'index']);
        Route::get('/projects/{id}', [\App\Http\Controllers\Api\Mitra\MitraProjectController::class, 'show']);
    });
});

