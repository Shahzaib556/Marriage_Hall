<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\HallController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::middleware(['role:owner,admin'])->group(function () {
        Route::get('/owner/dashboard', fn() => ['message' => 'Owner area']);
    });

    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
      
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });
});

// profile management routes


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'profile']);
    Route::put('/profile/update', [ProfileController::class, 'updateProfile']);
    Route::put('/profile/change-password', [ProfileController::class, 'changePassword']);
});

// hall management routes


Route::middleware(['auth:sanctum'])->group(function () {
    // Hall Owner
    Route::post('/halls', [HallController::class, 'store']);
    Route::get('/my-Halls', [HallController::class, 'myHalls']);
    Route::put('/halls/{id}', [HallController::class, 'update']);
    Route::delete('/halls/{id}', [HallController::class, 'destroy']);

    // Admin controls
    Route::post('/halls/{id}/approve', [HallController::class, 'approve']);
        // ->middleware('role:admin');
    Route::post('/halls/{id}/deactivate', [HallController::class, 'deactivate']);
        // ->middleware('role:admin');
    Route::get('/admin/halls', [HallController::class, 'adminHalls' ]);
});

// Public route
Route::get('/halls', [HallController::class, 'index']);

Route::get('/halls/approved', [HallController::class, 'approvedHalls']);
