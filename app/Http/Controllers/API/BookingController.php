<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hall;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    // 1. Search halls by location, date, capacity, price
    public function search(Request $request)
    {
        $query = Hall::where('status', 'approved'); // Only approved halls

        if ($request->location) {
            $query->where('location', 'LIKE', "%{$request->location}%");
        }
        if ($request->capacity) {
            $query->where('capacity', '>=', $request->capacity);
        }
        if ($request->price) {
            $query->where('pricing', '<=', $request->price);
        }
        if ($request->date) {
            $query->whereDoesntHave('bookings', function ($q) use ($request) {
                $q->where('booking_date', $request->date)
                  ->whereIn('status', ['pending', 'approved']);
            });
        }

        return response()->json($query->get());
    }

    // 2. Check hall availability
    public function checkAvailability($hall_id, Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'time_slot' => 'required|string|in:afternoon,evening' // ✅ Only these slots
        ]);

        $exists = Booking::where('hall_id', $hall_id)
            ->where('booking_date', $request->date)
            ->where('time_slot', $request->time_slot)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        return response()->json(['available' => !$exists]);
    }

    // 3. Book a hall
    public function book(Request $request)
    {
        $request->validate([
            'hall_id' => 'required|exists:halls,id',
            'booking_date' => 'required|date',
            'time_slot' => 'required|string|in:afternoon,evening', // ✅ Only valid slots
            'guests' => 'required|integer|min:1'
        ]);

        // prevent duplicate booking by same user on same date & slot
        $duplicate = Booking::where('hall_id', $request->hall_id)
            ->where('user_id', Auth::id())
            ->where('booking_date', $request->booking_date)
            ->where('time_slot', $request->time_slot)
            ->first();

        if ($duplicate) {
            return response()->json(['message' => 'You already booked/requested this hall at that time'], 409);
        }

        // check availability
        $exists = Booking::where('hall_id', $request->hall_id)
            ->where('booking_date', $request->booking_date)
            ->where('time_slot', $request->time_slot)
            ->whereIn('status', ['pending','approved'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Hall not available at this time'], 400);
        }

        $booking = Booking::create([
            'user_id' => Auth::id(),
            'hall_id' => $request->hall_id,
            'booking_date' => $request->booking_date,
            'time_slot' => $request->time_slot,
            'guests' => $request->guests,
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'Booking request sent to hall owner for approval', 'booking' => $booking], 201);
    }

    // 4. Manage booking request (Hall Owner Accept/Reject)
    public function manage(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $booking = Booking::findOrFail($id);

        // only hall owner can manage
        if (Auth::id() !== $booking->hall->owner_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->status = $request->status;
        $booking->save();

        return response()->json(['message' => 'Booking updated', 'booking' => $booking]);
    }

    // 5. View booking status (User Dashboard)
    public function myBookings()
    {
        $bookings = Booking::with('hall')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();
        return response()->json($bookings);
    }

    // Owner: View bookings for halls they own
    public function ownerBookings()
    {
        $ownerId = Auth::id();

        $bookings = Booking::with(['hall', 'user'])
            ->whereHas('hall', function ($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            })
            ->orderBy('booking_date', 'asc')
            ->get();

        return response()->json($bookings);
    }

    /* ---------------------- ADMIN APIS ---------------------- */

    // 6. View all bookings (Admin only)
    public function allBookings()
    {
        $bookings = Booking::with(['user:id,name,email', 'hall:id,name,location'])
            ->latest()
            ->get();

        return response()->json($bookings);
    }

    // 7. Admin update booking status
    public function adminUpdate(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected,cancelled'
        ]);

        $booking = Booking::findOrFail($id);
        $booking->status = $request->status;
        $booking->save();

        return response()->json(['message' => 'Booking status updated by admin', 'booking' => $booking]);
    }

    // 8. Booking statistics (Admin Dashboard)
    public function bookingStats()
    {
        return response()->json([
            'total_bookings'   => Booking::count(),
            'approved'         => Booking::where('status', 'approved')->count(),
            'pending'          => Booking::where('status', 'pending')->count(),
            'rejected'         => Booking::where('status', 'rejected')->count(),
            'cancelled'        => Booking::where('status', 'cancelled')->count(),
        ]);
    }
    
    // User: Cancel booking
    public function cancel($id)
    {
        $booking = Booking::findOrFail($id);
    
        // Ensure booking belongs to logged-in user
        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        // Ensure booking is not already cancelled/rejected
        if (in_array($booking->status, ['cancelled', 'rejected'])) {
            return response()->json(['message' => 'This booking is already ' . $booking->status], 400);
        }
    
        $booking->status = 'cancelled';
        $booking->save();
    
        return response()->json([
            'message' => 'Booking cancelled successfully',
            'booking' => $booking
        ]);
    }
}
