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
