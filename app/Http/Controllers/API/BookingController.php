<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\ActivityController;
use App\Models\Booking;
use App\Models\Hall;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    // 1. Search halls (User)
    public function search(Request $request)
    {
        $query = Hall::where('status', 'approved');

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

        // ✅ Log user activity
        ActivityController::log(
            'user',
            'Hall Search',
            Auth::user()->name,
            "User #" . Auth::id() . " searched halls with filters: " . json_encode($request->all())
        );

        return response()->json($query->get());
    }

    // 2. Check hall availability (User)
    public function checkAvailability($hall_id, Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'time_slot' => 'required|string|in:afternoon,evening'
        ]);

        $exists = Booking::where('hall_id', $hall_id)
            ->where('booking_date', $request->date)
            ->where('time_slot', $request->time_slot)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        // ✅ Log user activity
        ActivityController::log(
            'user',
            'Check Availability',
            Auth::user()->name,
            "User #" . Auth::id() . " checked availability for hall #{$hall_id} on {$request->date} ({$request->time_slot})"
        );

        return response()->json(['available' => !$exists]);
    }

    // 3. Book a hall (User)
    public function book(Request $request)
    {
        $request->validate([
            'hall_id' => 'required|exists:halls,id',
            'booking_date' => 'required|date',
            'time_slot' => 'required|string|in:afternoon,evening',
            'guests' => 'required|integer|min:1'
        ]);

        $duplicate = Booking::where('hall_id', $request->hall_id)
            ->where('user_id', Auth::id())
            ->where('booking_date', $request->booking_date)
            ->where('time_slot', $request->time_slot)
            ->first();

        if ($duplicate) {
            return response()->json(['message' => 'You already booked this hall at that time'], 409);
        }

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

        // ✅ Log user activity
        ActivityController::log(
            'user',
            'Booking Created',
            Auth::user()->name,
            "User #" . Auth::id() . " booked hall #{$request->hall_id} on {$request->booking_date} ({$request->time_slot})"
        );

        // ✅ Include owner bank details in response
        $booking->load('hall.owner');

        return response()->json([
            'message' => 'Booking request sent',
            'booking' => $booking,
            'owner_bank_details' => [
                'bank_name' => $booking->hall->owner->bank_name ?? null,
                'account_number' => $booking->hall->owner->account_number ?? null,
            ]
        ], 201);
    }

    // 4. Manage booking request (Owner)
    public function manage(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $booking = Booking::findOrFail($id);

        if (Auth::id() !== $booking->hall->owner_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->status = $request->status;
        $booking->save();

        // ✅ Log owner activity
        ActivityController::log(
            'owner',
            "Booking {$request->status}",
            Auth::user()->name,
            "Owner #" . Auth::id() . " {$request->status} booking #{$booking->id} for hall #{$booking->hall_id}"
        );

        return response()->json(['message' => 'Booking updated', 'booking' => $booking]);
    }

    // 5. View my bookings (User)
    public function myBookings()
    {
        $bookings = Booking::with(['hall.owner'])->where('user_id', Auth::id())->latest()->get();

        // ✅ Log user activity
        ActivityController::log(
            'user',
            'View My Bookings',
            Auth::user()->name,
            "User #" . Auth::id() . " viewed their bookings"
        );

        return response()->json($bookings);
    }

    // 6. Owner: View bookings
    public function ownerBookings()
    {
        $ownerId = Auth::id();

        $bookings = Booking::with(['hall', 'user'])
            ->whereHas('hall', function ($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            })
            ->orderBy('booking_date', 'asc')
            ->get();

        // ✅ Log owner activity
        ActivityController::log(
            'owner',
            'View My Halls Bookings',
            Auth::user()->name,
            "Owner #{$ownerId} viewed bookings for their halls"
        );

        return response()->json($bookings);
    }

    // 7. Admin: View all bookings
    public function allBookings(Request $request)
    {
        $days = $request->query('days', 7); // default to 7 if not provided

        $bookings = Booking::with(['user:id,name,email', 'hall.owner:id,name,bank_name,account_number'])
            ->where('created_at', '>=', now()->subDays($days))
            ->latest()
            ->get();

        return response()->json($bookings);
    }

    // 8. Admin update status
    public function adminUpdate(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected,cancelled'
        ]);

        $booking = Booking::findOrFail($id);

        // Only allow admin to change if booking is still pending
        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Admin can only update pending bookings'
            ], 403);
        }

        $booking->status = $request->status;
        $booking->save();

        return response()->json([
            'message' => 'Booking status updated by admin',
            'booking' => $booking
        ]);
    }

    // 9. Booking statistics (Admin)
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

    // 10. User cancel booking
    public function cancel($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (in_array($booking->status, ['cancelled', 'rejected'])) {
            return response()->json(['message' => 'This booking is already ' . $booking->status], 400);
        }

        $booking->status = 'cancelled';
        $booking->save();

        // ✅ Log user activity
        ActivityController::log(
            'user',
            'Booking Cancelled',
            Auth::user()->name,
            "User #" . Auth::id() . " cancelled booking #{$booking->id} for hall #{$booking->hall_id}"
        );

        return response()->json([
            'message' => 'Booking cancelled successfully',
            'booking' => $booking
        ]);
    }
}
