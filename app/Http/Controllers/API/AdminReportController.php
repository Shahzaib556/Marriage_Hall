<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    /**
     * Generate system-wide reports for admin
     */
    public function reports()
    {
        // 1. Total Bookings
        $totalBookings = Booking::count();

        // 2. Bookings by status
        $bookingsByStatus = Booking::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // 3. Bookings by month
        $monthlyBookings = Booking::selectRaw('MONTH(booking_date) as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        // 4. Total Revenue (only approved bookings)
        $totalRevenue = Booking::where('status', 'approved')
            ->join('halls', 'bookings.hall_id', '=', 'halls.id')
            ->sum('halls.pricing');

        // 5. Revenue by hall
        $revenueByHall = Booking::where('status', 'approved')
            ->join('halls', 'bookings.hall_id', '=', 'halls.id')
            ->selectRaw('halls.name, SUM(halls.pricing) as revenue')
            ->groupBy('halls.name')
            ->orderByDesc('revenue')
            ->get();

        // 6. Revenue by month
        $revenueByMonth = Booking::where('status', 'approved')
            ->join('halls', 'bookings.hall_id', '=', 'halls.id')
            ->selectRaw('MONTH(booking_date) as month, SUM(halls.pricing) as revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('revenue', 'month');

        // 7. Popular halls by bookings
        $popularHallsByBookings = Booking::selectRaw('hall_id, COUNT(*) as total_bookings')
            ->groupBy('hall_id')
            ->orderByDesc('total_bookings')
            ->with('hall:id,name')
            ->take(5)
            ->get();

        // 8. Popular halls by revenue
        $popularHallsByRevenue = Booking::where('status', 'approved')
            ->join('halls', 'bookings.hall_id', '=', 'halls.id')
            ->selectRaw('halls.id, halls.name, SUM(halls.pricing) as revenue')
            ->groupBy('halls.id', 'halls.name')
            ->orderByDesc('revenue')
            ->take(5)
            ->get();

        return response()->json([
            'total_bookings'         => $totalBookings,
            'bookings_by_status'     => $bookingsByStatus,
            'monthly_bookings'       => $monthlyBookings,
            'total_revenue'          => $totalRevenue,
            'revenue_by_hall'        => $revenueByHall,
            'revenue_by_month'       => $revenueByMonth,
            'popular_halls_bookings' => $popularHallsByBookings,
            'popular_halls_revenue'  => $popularHallsByRevenue,
        ]);
    }
}
