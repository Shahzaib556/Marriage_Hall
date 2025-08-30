<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\HallController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

/*
|--------------------------------------------------------------------------
| Profile Management Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('profile')->group(function () {
    Route::get('/', [ProfileController::class, 'profile']);
    Route::put('/update', [ProfileController::class, 'updateProfile']);
    Route::put('/change-password', [ProfileController::class, 'changePassword']);
});

/*
|--------------------------------------------------------------------------
| Hall Management Routes
|--------------------------------------------------------------------------
*/

// ✅ Public halls (only approved)
Route::get('/halls', [HallController::class, 'index']);

// ✅ Hall Owner Routes
Route::middleware(['auth:sanctum', 'role:owner'])->prefix('owner')->group(function () {
    Route::post('/halls', [HallController::class, 'store']);
    Route::get('/halls', [HallController::class, 'myHalls']);
    Route::put('/halls/{id}', [HallController::class, 'update']);
    Route::delete('/halls/{id}', [HallController::class, 'destroy']);
});

// ✅ Admin Routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // User management
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Hall management (approval/deactivation + full list)
    Route::get('/halls', [HallController::class, 'adminIndex']); 
    Route::post('/halls/{id}/approve', [HallController::class, 'approve']);
    Route::post('/halls/{id}/deactivate', [HallController::class, 'deactivate']);
});
