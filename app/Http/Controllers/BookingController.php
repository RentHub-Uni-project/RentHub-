<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Http\Resources\BookingResource;
use App\Http\Resources\BookingUpdateRequestResource;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\BookingUpdateRequest;
use App\Models\User;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    // =======================
    //          TENANT
    // =======================
    public function createBooking(Request $request, Apartment $apartment)
    {
        $data = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            "number_of_guests" => "required|integer|min:1",
            "tenant_notes" => "nullable|string",
        ]);

        $user = $request->user();
        // check apartment
        if (!$apartment->isApproved()) {
            return response()->json([
                "message" => "this aprtment is not approved, you can't create a booking for it."
            ], 400);
        }
        if (!$apartment->is_available) {
            return response()->json(["message" => "this apartment is not available right now."], 400);
        }

        $total_price = Booking::calculateTotalPrice($data["start_date"], $data["end_date"], $apartment->price_per_night);

        $booking = Booking::create([
            ...$data,
            "apartment_id" => $apartment->id,
            "tenant_id" => $user->id,
            "status" => BookingStatus::PENDING,
            "total_price" => $total_price
        ]);

        return response()->json([
            "message" => "booking created successfully",
            "booking" => new BookingResource($booking)
        ], 201);
    }
    public function deleteBooking(Request $request, Booking $booking)
    {
        // check booking owner
        $user = $request->user();
        if ($user->id != $booking->tenant_id) {
            return response()->json([
                "message" => "you can't perform this action, you are not the owner of the given booking."
            ], 400);
        }
        // check if booking approved
        if ($booking->isApproved()) {
            return response()->json([
                "message" => "this booking is approved and can't be deleted."
            ], 400);
        }

        $booking->delete();
        return response()->json(["message" => "booking deleted successfully"]);
    }
    public function getBooking(Request $request, Booking $booking)
    {
        // check booking owner
        $user = $request->user();
        if ($user->id != $booking->tenant_id) {
            return response()->json([
                "message" => "you are not the owner of the given booking."
            ], 400);
        }
        return response()->json(["message" => "booking found successfully", "booking" => new BookingResource($booking)]);
    }
    public function listMyBookings(Request $request, User $tenant)
    {
        $user = $request->user();
        $bookings = Booking::where("tenant_id", $user->id);

        return response()->json(["message" => "success", "bookings" => BookingResource::collection($bookings)]);
    }

    public function createUpdateRequest(Request $request, Booking $booking)
    {
        $user = $request->user();
        // check tenant
        if ($booking->tenant_id != $user->id) {
            return response()->json(["message" => "you are not the owner of provided booking."]);
        }
        $apartment = $booking->apartment;

        $data = $request->validate([
            "request_tenant_notes" => "sometimes|nullable|string",
            "requested_start_date" => "required|date",
            "requested_end_date" => "required|date|after:start_date",
            "request_number_of_guests" => "sometimes|nullable|numeric"
        ]);
        // check start_date & end_date
        $start_date = $data["start_date"];
        $end_date = $data["end_date"];
        // if provided start_date == previous one, no validation needed
        if ($start_date != $booking->start_date) {
            if ($start_date < new DateTime()) {
                return response()->json(["message" => "provided start_date should be after today."], 400);
            }
        }

        // calculate new totalPrice
        $total_price = Booking::calculateTotalPrice($start_date, $end_date, $apartment->price_per_night);

        $updateRequest = BookingUpdateRequest::create([...$data, $total_price]);

        return response()->json(["message" => "update request created successfully.", "updateRequest" => $updateRequest], 201);
    }
    public function editUpdateRequest(Request $request, BookingUpdateRequest $updateRequest) {}
    public function deleteUpdateRequest(Request $request, BookingUpdateRequest $updateRequest) {}

    // =======================
    //          OWNER
    // =======================
    public function ownerGetBooking(Request $request, Booking $booking) {}
    public function ownerApproveBooking(Request $request, Booking $booking)
    {
        $user = $request->user();
        $apartment = $booking->apartment;
        // check apartment owner
        if ($apartment->owner_id != $user->id) {
            return response()->json(["message" => "you are not the owner of the booking apartment."], 400);
        }
        // check booking status
        if ($booking->isRejected()) {
            return response()->json(["message" => "this booking is rejected, you can't change its status."], 400);
        }
        // check apartment availablity
        if (!$apartment->is_available) {
            return response()->json(["message" => "the booking apartment is not available right now, you can't approve a new booking."]);
        }

        // TODO: handle user wallet

        $booking->update(["status" => BookingStatus::APPROVED]);

        // update apartment
        $apartment->update([
            "is_available" => false
        ]);

        return response()->json(["message" => "booking approved successfully", "booking" => new BookingResource($booking)]);
    }
    public function ownerRejectBooking(Request $request, Booking $booking)
    {
        $user = $request->user();
        $apartment = $booking->apartment;
        // check apartment owner
        if ($apartment->owner_id != $user->id) {
            return response()->json(["message" => "you are not the owner of the booking apartment."], 400);
        }
        // check booking status
        if ($booking->isApproved()) {
            return response()->json(["message" => "this booking is approved, you can't change its status."], 400);
        }

        $booking->update(["status" => BookingStatus::REJECTED]);

        return response()->json(["message" => "booking rejected successfully", "booking" => new BookingResource($booking)]);
    }
    public function ownerListBookings(Request $request, Apartment $apartment)
    {
        $user = $request->user();
        // check owner
        if ($apartment->owner_id != $user->id) {
            return response()->json(["message" => "you are not the owner of the booking apartment."], 400);
        }
        $bookings = Booking::where("apartment_id", $apartment->id);

        return response()->json(["message" => "success", "bookings" => BookingResource::collection($bookings)]);
    }

    public function ownerGetUpdateRequest(Request $request, BookingUpdateRequest $updateRequest)
    {
        $user = $request->user();
        // check apartment owner
        $apartment = $updateRequest->booking->apartment;
        if ($apartment->owner_id != $user->id) {
            return response()->json(["message" => "you are not the owner of booking's apartment."], 400);
        }

        return response()->json(["message" => "update request found successfully", "updateRequest" => new BookingUpdateRequestResource($updateRequest)]);
    }
    public function ownerApproveUpdateRequest(Request $request, BookingUpdateRequest $updateRequest)
    {
        $user = $request->user();
        // check owner
        $booking = $updateRequest->booking;
        $apartment = $booking->apartment;
        if ($apartment->owner_id != $user->id) {
            return response()->json(["message" => "you are not the owner of booking's apartment."], 400);
        }
        // check if booking approved

        // check start_date & end_date
        $start_date = $updateRequest->start_date;
        $end_date = $updateRequest->end_date;
        // validate them
        if ($end_date <= $start_date) {
            return response()->json(["message" => "end_date should be after start_date."], 400);
        }

        // calculate total price
        $total_price = Booking::calculateTotalPrice($start_date, $end_date, $apartment->price_per_nigth);
    }
    public function ownerRejectUpdateRequest(Request $request, BookingUpdateRequest $updateRequest) {}

    public function ownerListUpdateRequests(Request $request, Booking $booking)
    {
        $user = $request->user();
        $apartment = $booking->apartment;
        if ($apartment->owner_id != $user->id) {
            return response()->json(["message" => "you are not the owner of booking's apartment."]);
        }

        $updatedRequests = BookingUpdateRequest::where("booking_id", $booking->id);

        return response()->json(["message" => "success", "updateRequests" => BookingUpdateRequestResource::collection($updatedRequests)]);
    }

    // =======================
    //          ADMIN
    // =======================
    public function adminCreateBooking(Request $request, Apartment $apartment)
    {
        $validated_data = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            "number_of_guests" => "required|integer|min:1",
            "tenant_notes" => "nullable|string",
            "tenant_id" => 'required|string',
            "status" => Rule::enum(UserRole::class)
        ]);

        // check apartment
        if (!$apartment->isApproved()) {
            return response()->json([
                "message" => "this aprtment is not approved, you can't create a booking for it."
            ], 400);
        }
        if (!$apartment->is_available) {
            return response()->json(["message" => "this apartment is not available right now."], 400);
        }
        // check tenant
        $tenant = User::findOrFail($validated_data["tenant_id"]);
        if (!$tenant->role == UserRole::TENANT) {
            return response()->json(["message" => "provided user should be of role tenant"], 400);
        }
        // handle total_price
        $start_date = new DateTime($validated_data["start_date"]);
        $end_date = new DateTime($validated_data["end_date"]);
        $interval = $start_date->diff($end_date);
        $total_price = $interval->days * $apartment->price_per_night;

        $booking = Booking::create([
            ...$validated_data,
            "apartment_id" => $apartment->id,
            "total_price" => $total_price
        ]);

        return response()->json([
            "message" => "booking created successfully",
            "booking" => new BookingResource($booking)
        ], 201);
    }
    public function adminUpdateBooking(Request $request, Booking $booking)
    {
        $validated_data = $request->validate([
            'start_date' => 'date|after_or_equal:today',
            'end_date' => 'date|after:start_date',
            "number_of_guests" => "integer|min:1",
            "tenant_notes" => "nullable|string",
            "status" => Rule::enum(UserRole::class)
        ]);

        // validate booking
        if ($booking->isApproved()) {
            return response()->json(["message" => "you can't update approved bookings."], 400);
        }

        $apartment = $booking->apartment;

        // handle total_price
        $start_date = $booking->start_date;
        $end_date = $booking->end_date;
        if (array_key_exists("start_date", $validated_data)) {
            $start_date = $validated_data["start_date"];
        }
        if (array_key_exists("end_date", $validated_data)) {
            $end_date = $validated_data["end_date"];
        }
        $interval = $start_date->diff($end_date);
        $total_price = $interval->days * $apartment->price_per_night;

        $booking->update([...$validated_data, "total_price" => $total_price]);

        return response()->json(["message" => "booking updated successfully", "booking" => new BookingResource($booking)]);
    }
    public function adminDeleteBooking(Request $request, Booking $booking)
    {
        // validate booking
        if ($booking->isApproved()) {
            return response()->json(["message" => "you can't delete approved bookings."], 400);
        }

        $booking->delete();

        return response()->json(["message" => "booking deleted successfully"], 204);
    }
    public function adminListBookings(Request $request)
    {
        $query = Booking::query();

        $query->when($request->filled('tenant_id'), fn($q) => $q->where('tenant_id', $request->tenant_id))
            ->when($request->filled('apartment_id'), fn($q) => $q->where('apartment_id', $request->apartment_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('payment_status'), fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->filled('start_date'), fn($q) => $q->whereDate('start_date', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn($q) => $q->whereDate('end_date', '<=', $request->end_date))
            ->when($request->filled('total_price'), fn($q) => $q->where('total_price', '<=', $request->end_date));

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);

        // TODO: apply resource

        $result = $query->paginate($perPage);
        return response()->json(["message" => "success", "bookings" => $result], 200);
    }
    public function adminGetBooking(Request $request, Booking $booking)
    {
        return response()->json(["message" => "booking found successfully", "booking" => new BookingResource($booking)]);
    }


    // Update Requests
    public function adminGetUpdateRequest(Request $request, BookingUpdateRequest $updateRequest)
    {
        return response()->json(["message" => "update request found successfully", "updateRequest" => new BookingUpdateRequestResource($updateRequest)]);
    }
    public function adminListUpdateRequests(Request $request, Booking $booking)
    {
        $updatedRequests = BookingUpdateRequest::where("booking_id", $booking->id);
        return response()->json(["message" => "success", "updateRequests" => BookingUpdateRequestResource::collection($updatedRequests)]);
    }
    public function adminDeleteUpdateRequest(Request $request, BookingUpdateRequest $updateRequest)
    {
        $updateRequest->delete();

        return response()->json(["message" => "update request deleted successfully"], 204);
    }
}
