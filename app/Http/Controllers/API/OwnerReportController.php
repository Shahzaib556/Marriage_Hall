<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;

class OwnerReportController extends Controller
{
    /**
     * Generate reports for hall owners (only their halls)
     */
    public function reports()
    {
        $ownerId = Auth::id();

        // 1. Total bookings for my halls
        $totalBookings = Booking::whereHas('hall', function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        })
            ->count();

        // 2. Bookings by status
        $bookingsByStatus = Booking::whereHas('hall', function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        })
            ->selectRaw('bookings.status, COUNT(*) as total')
            ->groupBy('bookings.status')
            ->pluck('total', 'bookings.status');

        // 3. Monthly bookings
        $monthlyBookings = Booking::whereHas('hall', function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        })
            ->selectRaw('MONTH(bookings.booking_date) as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        // 4. Total revenue (only approved bookings of my halls)
        $totalRevenue = Booking::where('bookings.status', 'approved')
            ->whereHas('hall', function ($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            })
            ->join('halls', 'bookings.hall_id', '=', 'halls.id')
            ->sum('halls.pricing');

        // 5. Revenue by hall
        $revenueByHall = Booking::where('bookings.status', 'approved')
            ->whereHas('hall', function ($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            })
            ->join('halls', 'bookings.hall_id', '=', 'halls.id')
            ->selectRaw('halls.name, SUM(halls.pricing) as revenue')
            ->groupBy('halls.name')
            ->orderByDesc('revenue')
            ->get();

        // 6. Revenue by month
        $revenueByMonth = Booking::where('bookings.status', 'approved')
            ->whereHas('hall', function ($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            })
            ->join('halls', 'bookings.hall_id', '=', 'halls.id')
            ->selectRaw('MONTH(bookings.booking_date) as month, SUM(halls.pricing) as revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('revenue', 'month');

        return response()->json([
            'total_bookings' => $totalBookings,
            'bookings_by_status' => $bookingsByStatus,
            'monthly_bookings' => $monthlyBookings,
            'total_revenue' => $totalRevenue,
            'revenue_by_hall' => $revenueByHall,
            'revenue_by_month' => $revenueByMonth,
        ]);
    }
}
