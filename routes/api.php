<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\StockProductController;
use App\Http\Controllers\Api\AdjustmentProductController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\ShiftHistoryController;
use App\Http\Controllers\Api\DiscountController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\FileController;

// Public routes
Route::get('v1/files/{path}', [FileController::class, 'show'])->where('path', '.*');

// Public routes
Route::post('v1/login', [AuthController::class, 'login']);

// Protected routes
Route::group([
    'prefix' => 'v1',
    'middleware' => ['auth:sanctum']
], function () {
    Route::apiResource('discounts', DiscountController::class);

    Route::apiResource('product-categories', ProductCategoryController::class);
    Route::apiResource('products', ProductController::class)->except(['update']);
    Route::post('products/{product}', [ProductController::class, 'update']);

    Route::apiResource('transactions', TransactionController::class);

    Route::apiResource('users', UserController::class);
    Route::get('users/role/{role}', [UserController::class, 'getByRole']);

    Route::apiResource('stock-products', StockProductController::class);
    Route::apiResource('adjustment-products', AdjustmentProductController::class)->except(['update', 'destroy']);
    Route::post('adjustment-products/upload-image', [AdjustmentProductController::class, 'uploadImage']);

    Route::apiResource('shifts', ShiftController::class);
    Route::get('shifts/user/{user_id}', [ShiftController::class, 'getByUserId']);
    Route::post('shifts/{shift}/histories', [ShiftController::class, 'addHistory']);
    Route::apiResource('shift-histories', ShiftHistoryController::class)->only(['index', 'show']);
});