<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    // 1. User: Add Review
    public function store(Request $request)
    {
        $request->validate([
            'hall_id' => 'required|exists:halls,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $booking = Booking::where('user_id', Auth::id())
            ->where('hall_id', $request->hall_id)
            ->where('status', 'approved')
            ->whereDate('booking_date', '<', now()) // booking must be completed
            ->orderBy('booking_date', 'desc')
            ->first();

        if (! $booking) {
            return response()->json(['message' => 'You can only review completed bookings'], 403);
        }

        if (Review::where('booking_id', $booking->id)->exists()) {
            return response()->json(['message' => 'You already reviewed this booking'], 400);
        }

        $review = Review::create([
            'user_id' => Auth::id(),
            'hall_id' => $request->hall_id,
            'booking_id' => $booking->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Review submitted successfully',
            'review' => $review,
        ], 201);
    }

    // 2. Public: Get all reviews for a hall
    public function hallReviews($hall_id)
    {
        $reviews = Review::with('user:id,name')
            ->where('hall_id', $hall_id)
            ->latest()
            ->get();

        return response()->json($reviews);
    }

    // 3. Public: Get average rating for a hall
    public function averageRating($hall_id)
    {
        $avg = Review::where('hall_id', $hall_id)->avg('rating');
        $count = Review::where('hall_id', $hall_id)->count();

        return response()->json([
            'hall_id' => $hall_id,
            'average_rating' => round($avg, 2),
            'total_reviews' => $count,
        ]);
    }

    // 4. Admin: View all reviews
   public function allReviews()
    {
    $user = Auth::user();

    if ($user->role === 'admin') {
        // Admin can see all reviews
        $reviews = Review::with(['user:id,name,email', 'hall:id,name,location'])
            ->latest()
            ->get();
    } elseif ($user->role === 'owner') {
        // Owner can only see reviews of their halls
        $hallIds = $user->halls()->pluck('id');

        $reviews = Review::with(['user:id,name,email', 'hall:id,name,location'])
            ->whereIn('hall_id', $hallIds)
            ->latest()
            ->get();
    } else {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    return response()->json($reviews);
    }


    // 5. Admin: Delete a review
    public function destroy($id)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $review = Review::findOrFail($id);
        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }
    public function recentBooking($user_id)
   {
    $booking = Booking::where('user_id', $user_id)
        ->where('status', 'approved')
        ->whereDate('booking_date', '<', now())
        ->whereDoesntHave('review')
        ->with('hall:id,name')
        ->orderBy('booking_date', 'desc')
        ->first();

    return response()->json([
        'booking' => $booking
    ]);
    }

    public function reports()
   {
    $owner = Auth::user();

    // Example: halls owned by this owner
    $halls = $owner->halls()->with('bookings')->get();

    $revenue_by_hall = $halls->map(function ($hall) {
        return [
            'id' => $hall->id,
            'name' => $hall->name,
            'revenue' => $hall->bookings->sum('total_price'),
            'average_rating' => round(Review::where('hall_id', $hall->id)->avg('rating'), 2),
            'total_reviews' => Review::where('hall_id', $hall->id)->count(),
        ];
    });

    return response()->json([
        'total_bookings' => $halls->flatMap->bookings->count(),
        'monthly_bookings' => $this->getMonthlyBookings($halls), // your existing logic
        'revenue_by_hall' => $revenue_by_hall,
        'occupancy_rate' => $this->calculateOccupancy($halls), // if you have it
    ]);
    }

}
