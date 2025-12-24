<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // list apartment reviews (all)
    public function reviews(Request $request, Apartment $apartment)
    {
        $user = $request->user();
        if (!$user->isAdmin() && !$apartment->isApproved()) {
            return response()->json(["message" => "this appartment is not approved, you can't see its details."], 403);
        }
        return response()->json(["message" => "success", "reviews" => $apartment->reviews()]);
    }
    // * create apartment review (tenant)

    public function rate(Request $request, $id)
    {
        $user = $request->user();

        $data = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'apartment_id' => 'required|exists:apartments,id',
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
            'apartment_id' => $booking->apartment_id,
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
            ->with('apartment:id,title')
            ->latest()
            ->get();

        return $reviews;
    }

    // ===============
    //      ADMIN
    // ===============

    public function adminGetReview(Request $request, Review $review)
    {
        return response()->json([
            'message' => 'Review found successfully.',
            'reviews' => $review
        ]);
    }

    //  * List all reviews in the system
    public function adminListReviews()
    {
        $reviews = Review::with([
            'apartment:id,title',
            'tenant:id,first_name,last_name'
        ])
            ->latest()
            ->get();
        return response()->json([
            'message' => 'success.',
            'reviews' => $reviews
        ]);
    }

    //  * Create a review (Admin only)
    public function adminCreateReview(Request $request)
    {
        $data = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'apartment_id' => 'required|exists:apartments,id',
            'tenant_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);
        $review = Review::create($data);

        return response()->json([
            'message' => 'Review created successfully.',
            'review' => $review
        ], 201);
    }

    //  * Update any review
    public function adminUpdateReview(Request $request, Review $review)
    {
        $review->update($request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]));

        return response()->json([
            'message' => 'Review updated successfully.',
            'review' => $review
        ]);
    }

    //  * Delete any review
    public function adminDeleteReview(Request $request, Review $review)
    {
        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully.'
        ], 204);
    }
}
