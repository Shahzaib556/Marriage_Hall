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
        $query = Hall::query();

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
            $query->whereDoesntHave('bookings', function($q) use ($request) {
                $q->where('booking_date', $request->date)
                  ->whereIn('status', ['pending', 'approved']);
            });
        }

        return response()->json($query->get());
    }

    // 2. Check hall availability
    public function checkAvailability($hall_id, Request $request)
    {
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
            'time_slot' => 'required|string'
        ]);

        // check availability
        $exists = Booking::where('hall_id', $request->hall_id)
            ->where('booking_date', $request->booking_date)
            ->where('time_slot', $request->time_slot)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Hall not available at this time'], 400);
        }

        $booking = Booking::create([
            'user_id' => Auth::id(),
            'hall_id' => $request->hall_id,
            'booking_date' => $request->booking_date,
            'time_slot' => $request->time_slot,
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'Booking request created', 'booking' => $booking], 201);
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

    // 5. View booking status
    public function myBookings()
    {
        $bookings = Booking::with('hall')->where('user_id', Auth::id())->get();
        return response()->json($bookings);
    }
}

