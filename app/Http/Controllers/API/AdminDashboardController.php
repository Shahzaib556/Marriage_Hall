<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Hall;
use App\Models\Booking;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    // Dashboard Stats API
    public function overview()
    {
        // Summary counts
        $totalUsers = User::count();
        $totalHalls = Hall::count();
        $activeBookings = Booking::where('status', 'Approved')->count();

        // Revenue removed (since no payments yet)
        $revenue = 0;

        // Recent Users
       $recentUsers = User::where('role', '!=', 'admin') // exclude admins
            ->latest()
            ->take(5)
            ->get(['id', 'name', 'role']);


        // Recent Bookings
        $recentBookings = Booking::with(['user:id,name', 'hall:id,name'])
            ->latest()
            ->take(5)
            ->get(['id', 'user_id', 'hall_id', 'status']);

        return response()->json([
            'stats' => [
                'total_users' => $totalUsers,
                'total_halls' => $totalHalls,
                'active_bookings' => $activeBookings,
                'revenue' => $revenue,
            ],
            'recent_users' => $recentUsers,
            'recent_bookings' => $recentBookings,
        ]);
    }
}
