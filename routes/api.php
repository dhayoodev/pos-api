<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\ShiftHistoryController;

// Public routes
Route::post('v1/login', [AuthController::class, 'login']);

// Protected routes
Route::group([
    'prefix' => 'v1',
    'middleware' => ['auth:sanctum']
], function () {
    Route::apiResource('product-categories', ProductCategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('transactions', TransactionController::class);
    Route::apiResource('users', UserController::class);
    
    Route::apiResource('shifts', ShiftController::class);
    Route::get('shifts/user/{user_id}', [ShiftController::class, 'getByUserId']);
    Route::post('shifts/{shift}/histories', [ShiftController::class, 'addHistory']);
    Route::apiResource('shift-histories', ShiftHistoryController::class)->only(['index', 'show']);
});