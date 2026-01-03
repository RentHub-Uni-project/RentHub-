<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Http\Resources\ReviewResource;
use App\Models\Apartment;
use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Services\NotificationService;


class ReviewController extends Controller
{
    // list apartment reviews (all)
    public function listApartmentReviews(Request $request, Apartment $apartment)
    {
        $reviews = $apartment->reviews;
        return response()->json(["message" => "success", "reviews" => ReviewResource::collection($reviews)]);
    }
    // get one review (all)
    public function getReview(Request $request, Review $review)
    {
        return response()->json(["message" => "review found successfully.", "review" => new ReviewResource($review)]);
    }
    // * create apartment review (tenant)

    public function createReview(Request $request, Apartment $apartment)
    {
        $user = $request->user();

        $data = $request->validate([
            'booking_id' => 'required|numeric',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);
        $booking = Booking::where('id', $data["booking_id"])->first();
        if (!$booking) {
            return response()->json(["message" => "booking not found."], 404);
        }
        // check booking
        if ($booking->tenant_id != $user->id) {
            return response()->json(["message" => "you are not the owner of the provided booking."], 403);
        }

        if ($booking->status != BookingStatus::APPROVED->value) {
            return response()->json([
                'message' => 'You can review only by approved bookings.'
            ], 400);
        }

        if (Review::where('booking_id', $booking->id)->where('tenant_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'You have already reviewed this booking.'
            ], 400);
        }

        $review = Review::create([
            ...$data,
            'apartment_id' => $apartment->id,
            'tenant_id' => $user->id
        ]);
        NotificationService::createNotification(
            $apartment->owner_id,
            'review_created',
            'New Review Received',
            "{$user->first_name} has submitted a review for your apartment {$apartment->title}.",
            $review->id
        );
        return response()->json(["message" => "review created successfully.", "review" => new ReviewResource($review)], 201);
    }


    // update a review (tenant)
    public function updateReview(Request $request, Review $review)
    {
        $user = $request->user();

        // check owner
        if ($review->tenant_id != $user->id) {
            return response()->json(["message" => "you are not the owner of this review."], 403);
        }

        $review->update($request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'sometimes|nullable|string|max:1000',
        ]));

        return response()->json(["message" => "review updated successfully.", "review" => new ReviewResource($review)]);
    }

    // Delete a review (tenant)
    public function deleteReview(Request $request, Review $review)
    {
        $user = $request->user();

        // check owner
        if ($review->tenant_id != $user->id) {
            return response()->json(["message" => "you are not the owner of this review."], 403);
        }
        $review->delete();

        return response()->json(['message' => 'Review deleted successfully.'], 204);
    }

    // List my reviews (tenant)
    public function myReviews(Request $request)
    {
        $user = $request->user();

        $reviews = Review::where('tenant_id', $user->id)
            ->with('apartment:id,title')
            ->latest()
            ->get();

        return response()->json(["message" => "success", "reviews" => ReviewResource::collection($reviews)]);
    }
}
