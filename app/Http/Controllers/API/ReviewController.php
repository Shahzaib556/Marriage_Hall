<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    // 1. Add Review (only after approved booking)
    public function store(Request $request)
    {
        $request->validate([
            'hall_id' => 'required|exists:halls,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        // Check if user booked this hall
        $hasBooking = Booking::where('user_id', Auth::id())
            ->where('hall_id', $request->hall_id)
            ->where('status', 'approved')
            ->exists();

        if (!$hasBooking) {
            return response()->json(['message' => 'You can only review halls you booked'], 403);
        }

        $review = Review::create([
            'user_id' => Auth::id(),
            'hall_id' => $request->hall_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json(['message' => 'Review submitted successfully', 'review' => $review], 201);
    }

    // 2. Get all reviews for a hall
    public function hallReviews($hall_id)
    {
        $reviews = Review::with('user:id,name')
            ->where('hall_id', $hall_id)
            ->latest()
            ->get();

        return response()->json($reviews);
    }

    // 3. User update review
    public function update(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $review = Review::findOrFail($id);

        if ($review->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $review->update($request->only('rating', 'comment'));

        return response()->json(['message' => 'Review updated successfully', 'review' => $review]);
    }

    // 4. User delete review
    public function destroy($id)
    {
        $review = Review::findOrFail($id);

        if ($review->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }

    // 5. Admin view all reviews
    public function allReviews()
    {
        $reviews = Review::with(['user:id,name,email', 'hall:id,name,location'])
            ->latest()
            ->get();

        return response()->json($reviews);
    }

    // 6. Average rating for a hall
    public function averageRating($hall_id)
    {
        $avg = Review::where('hall_id', $hall_id)->avg('rating');
        $count = Review::where('hall_id', $hall_id)->count();

        return response()->json([
            'hall_id' => $hall_id,
            'average_rating' => round($avg, 2),
            'total_reviews' => $count
        ]);
    }
}
