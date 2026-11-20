<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\BudgetController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CurrentUserController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware(['auth:sanctum', 'verified', 'throttle:api'])->group(function () {
    Route::get('me', [CurrentUserController::class, 'show'])->name('me');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notification}', [NotificationController::class, 'update'])->name('notifications.update');
    Route::delete('notifications', [NotificationController::class, 'destroy'])->name('notifications.read-all');

    Route::apiResource('workspaces', WorkspaceController::class);

    Route::prefix('workspaces/{workspace}')->scopeBindings()->group(function () {
        Route::get('dashboard', [DashboardController::class, 'show'])->name('workspaces.dashboard');
        Route::apiResource('accounts', AccountController::class)->except('show')
            ->parameters(['accounts' => 'account']);
        Route::apiResource('categories', CategoryController::class)->except('show');
        Route::apiResource('transactions', TransactionController::class);
        Route::apiResource('budgets', BudgetController::class)->except('show');
    });
});
