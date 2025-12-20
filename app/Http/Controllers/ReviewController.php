<?php

namespace App\Http\Controllers;


use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // list appartment reviews (all)
    public function ratings($id)
    {
        return Review::where('appartment_id', $id)
            ->with('tenant:id,first_name,last_name')
            ->latest()
            ->get();
    }
    // * create appartment review (tenant)

    public function rate(Request $request, $id)
    {
        $user = $request->user();

        $data = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'appartment_id' => 'required|exists:appartments,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $booking = Booking::where('id', $data['booking_id'])
            ->where('tenant_id', $user->id)
            ->firstOrFail();

        if ($booking->status !== 'completed') {
            return response()->json([
                'message' => 'You can review only completed bookings'
            ], 403);
        }

        if (Review::where('booking_id', $booking->id)->exists()) {
            return response()->json([
                'message' => 'You have already reviewed this booking'
            ], 409);
        }

        $review = Review::create([
            'booking_id' => $booking->id,
            'appartment_id' => $booking->appartment_id,
            'tenant_id' => $user->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json($review, 201);
    }


    // update a review (tenant)
    public function updateReview(Request $request, $id)
    {
        $user = $request->user();

        // Find the review and ensure it belongs to the tenant
        $review = Review::where('id', $id)->where('tenant_id', $user->id)->firstOrFail();

        $review->update($request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'sometimes|string|max:1000',
        ]));

        return response()->json($review);
    }

    // Delete a review (tenant)
    public function deleteReview(Request $request, $id)
    {
        $user = $request->user();

        $review = Review::where('id', $id)->where('tenant_id', $user->id)->firstOrFail();
        $review->delete();

        return response()->json(['message' => 'Review deleted successfully.']);
    }

    // List my reviews (tenant)
    public function myReviews(Request $request)
    {
        $user = $request->user();

        $reviews = Review::where('tenant_id', $user->id)
            ->with('appartment:id,title')
            ->latest()
            ->get();

        return $reviews;
    }


    //  * List all reviews in the system
    public function adminListReviews()
    {
        return Review::with([
            'appartment:id,title',
            'tenant:id,first_name,last_name'
        ])
            ->latest()
            ->get();
    }

    //  * Create a review (Admin only)
    public function adminCreateReview(Request $request)
    {
        $data = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'appartment_id' => 'required|exists:appartments,id',
            'tenant_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        return Review::create($data);
    }

    //  * Update any review
    public function adminUpdateReview(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        $review->update($request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]));

        return $review;
    }

    //  * Delete any review
    public function adminDeleteReview($id)
    {
        Review::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Review deleted by admin'
        ]);
    }
}
