<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\HallController;
use App\Http\Controllers\API\AdminDashboardController;
use App\Http\Controllers\API\BookingController;

/* ----------------- AUTH ROUTES ----------------- */
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

/* ----------------- PROFILE ROUTES ----------------- */
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'profile']);
    Route::put('/profile/update', [ProfileController::class, 'updateProfile']);
    Route::put('/profile/change-password', [ProfileController::class, 'changePassword']);
});

/* ----------------- HALL ROUTES ----------------- */
Route::middleware(['auth:sanctum'])->group(function () {
    // Hall Owner
    Route::post('/halls', [HallController::class, 'store']);
    Route::get('/my-halls', [HallController::class, 'myHalls']);
    Route::put('/halls/{id}', [HallController::class, 'update']);
    Route::delete('/halls/{id}', [HallController::class, 'destroy']);

    // Admin controls
    Route::post('/halls/{id}/approve', [HallController::class, 'approve']);
    Route::post('/halls/{id}/deactivate', [HallController::class, 'deactivate']);
    Route::get('/admin/halls', [HallController::class, 'adminHalls']);
});

// Public routes
Route::get('/halls', [HallController::class, 'index']);
Route::get('/halls/approved', [HallController::class, 'approvedHalls']);

/* ----------------- ADMIN DASHBOARD ROUTE ----------------- */
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'overview']);
});

/* ----------------- BOOKING ROUTES ----------------- */
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/halls/search', [BookingController::class, 'search']); // 1. Search halls
    Route::get('/halls/{id}/availability', [BookingController::class, 'checkAvailability']); // 2. Check availability
    Route::post('/bookings', [BookingController::class, 'book']); // 3. Book a hall
    Route::get('/my-bookings', [BookingController::class, 'myBookings']); // 5. User bookings

    // New: Cancel booking
    Route::put('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
});

// Owner booking routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::put('/bookings/{id}/manage', [BookingController::class, 'manage']); // 4. Owner manage bookings
    Route::get('/owner/bookings', [BookingController::class, 'ownerBookings']);
});

// Admin booking routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/bookings', [BookingController::class, 'allBookings']); // 6. All bookings
    Route::put('/bookings/{id}/update', [BookingController::class, 'adminUpdate']); // 7. Update booking
    Route::get('/bookings/stats', [BookingController::class, 'bookingStats']); // 8. Stats
});
