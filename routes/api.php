<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Item\ItemDestroyController;
use App\Http\Controllers\Api\Item\ItemIndexController;
use App\Http\Controllers\Api\Item\ItemRestoreController;
use App\Http\Controllers\Api\Item\ItemShowController;
use App\Http\Controllers\Api\Item\ItemStoreController;
use App\Http\Controllers\Api\Item\ItemUpdateController;
use App\Http\Controllers\Api\User\UserIndexController;
use App\Http\Controllers\Api\User\UserRoleShowController;
use App\Http\Controllers\Api\User\UserRoleUpdateController;
use App\Http\Controllers\Api\User\UserShowController;
use App\Http\Controllers\Api\Workspace\WorkspaceGeneralDestroyController;
use App\Http\Controllers\Api\Workspace\WorkspaceGeneralShowController;
use App\Http\Controllers\Api\Workspace\WorkspaceGeneralStoreController;
use App\Http\Controllers\Api\Workspace\WorkspaceGeneralUpdateController;
use App\Http\Controllers\Api\Workspace\WorkspaceIndexController;
use App\Http\Controllers\Api\Workspace\WorkspaceItemsIndexController;
use App\Http\Controllers\Api\Workspace\WorkspaceShowController;
use App\Http\Controllers\Api\Workspace\WorkspaceStoreController;
use App\Http\Controllers\Api\Workspace\WorkspaceUpdateController;
use Illuminate\Support\Facades\Route;

Route::as('api.v1.')
    ->middleware('throttle:api')
    ->group(function (): void {
        Route::post('/login', LoginController::class)->middleware('throttle:login')->name('auth.login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/user', UserShowController::class)->name('user.show');

            // Admin-only endpoints
            Route::middleware('role:admin')->group(function (): void {
                Route::get('/users', UserIndexController::class)->name('user.index');
                Route::get('/users/{user}/role', UserRoleShowController::class)->name('user.role.show');
                Route::patch('/users/{user}/role', UserRoleUpdateController::class)->name('user.role.update')->middleware('throttle:5,1');
                Route::get('/workspaces', WorkspaceIndexController::class)->name('workspace.index');
                Route::post('/workspaces', WorkspaceGeneralStoreController::class)->name('workspace.general.store')->middleware('throttle:5,1');
            });

            // Workspace — own (authenticated users)
            Route::get('/workspace', WorkspaceShowController::class)->name('workspace.show');
            Route::post('/workspace', WorkspaceStoreController::class)->name('workspace.store')->middleware('throttle:5,1');
            Route::patch('/workspace', WorkspaceUpdateController::class)->name('workspace.update')->middleware('throttle:15,1');

            // Workspace — admin or owner
            Route::get('/workspaces/{workspace}', WorkspaceGeneralShowController::class)->name('workspace.general.show');
            Route::patch('/workspaces/{workspace}', WorkspaceGeneralUpdateController::class)->name('workspace.general.update')->middleware('throttle:5,1');
            Route::delete('/workspaces/{workspace}', WorkspaceGeneralDestroyController::class)->name('workspace.general.destroy')->middleware('throttle:5,1');
            Route::get('/workspaces/{workspace}/items', WorkspaceItemsIndexController::class)->name('workspace.items.index');

            // Items — flat
            Route::get('/items', ItemIndexController::class)->name('item.index');
            Route::post('/items', ItemStoreController::class)->name('item.store')->middleware('throttle:15,1');
            Route::get('/items/{item}', ItemShowController::class)->name('item.show');
            Route::patch('/items/{item}', ItemUpdateController::class)->name('item.update')->middleware('throttle:15,1');
            Route::delete('/items/{item}', ItemDestroyController::class)->name('item.destroy')->middleware('throttle:15,1');
            Route::post('/items/{id}/restore', ItemRestoreController::class)->name('item.restore')->middleware('throttle:15,1');
        });
    });
