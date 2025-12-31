<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\BookingUpdateRequestStatus;
use App\Enums\UserRole;
use App\Http\Resources\BookingResource;
use App\Http\Resources\BookingUpdateRequestResource;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\BookingUpdateRequest;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\NotificationService;


class BookingController extends Controller
{
    // =======================
    //          TENANT
    // =======================


    public function createBooking(Request $request, Apartment $apartment)
    {
        $data = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            "number_of_guests" => "required|integer|min:1",
            "tenant_notes" => "nullable|string",
        ]);

        $user = $request->user();

        $total_price = Booking::calculateTotalPrice($data["start_date"], $data["end_date"], $apartment->price_per_night);

        $booking = Booking::create([
            ...$data,
            "apartment_id" => $apartment->id,
            "tenant_id" => $user->id,
            "status" => BookingStatus::PENDING,
            "total_price" => $total_price
        ]);
        $booking->load('apartment');
        NotificationService::createNotification(
            $apartment->owner_id,
            'booking_created',
            'New Booking Created',
            "You have a new booking from {$user->first_name} from {$booking->start_date} to {$booking->end_date}",
            $booking->id
        );
        return response()->json([
            "message" => "booking created successfully",
            "booking" => new BookingResource($booking)
        ], 201);
    }
    public function cancelBooking(Request $request, Booking $booking)
    {
        // check booking owner
        $user = $request->user();
        if ($user->id != $booking->tenant_id) {
            return response()->json([
                "message" => "you can't perform this action, you are not the owner of the given booking."
            ], 403);
        }
        $booking->update(["status" => BookingStatus::CANCELLED]);
        $booking->load('apartment');
        NotificationService::createNotification(
            $booking->apartment->owner_id,
            'booking_cancelled',
            'Booking Cancelled',
            "{$user->first_name} has cancelled the booking for {$booking->apartment->title} from {$booking->start_date} to {$booking->end_date}.",
            $booking->id
        );
        return response()->json(["message" => "booking cancelled successfully", "booking" => new BookingResource($booking)]);
    }
    public function getBooking(Request $request, Booking $booking)
    {
        // check booking owner
        $user = $request->user();
        $booking->load(["apartment"]);
        if ($user->id != $booking->tenant_id) {
            return response()->json([
                "message" => "you are not the owner of the given booking."
            ], 403);
        }
        $booking->load('apartment');
        return response()->json(["message" => "booking found successfully", "booking" => new BookingResource($booking)]);
    }
    public function listMyBookings(Request $request)
    {
        $user = $request->user();
        $bookings = Booking::where("tenant_id", $user->id)->with('apartment')->get();

        return response()->json(["message" => "success", "bookings" => BookingResource::collection($bookings)]);
    }

    public function getUpdateRequest(Request $request, BookingUpdateRequest $updateRequest)
    {
        $user = $request->user();
        $booking = $updateRequest->booking;
        if ($booking->tenant_id != $user->id) {
            return response()->json(["message" => "you are not the owner of this booking."], 403);
        }

        return response()->json(["message" => "update request found successfully", "updateRequest" => new BookingUpdateRequestResource($updateRequest)]);
    }

    public function listUpdateRequests(Request $request, Booking $booking)
    {
        $user = $request->user();
        if ($booking->tenant_id != $user->id) {
            return response()->json(["message" => "you are not the owner of this booking."], 403);
        }

        $updateRequests = BookingUpdateRequest::where("booking_id", $booking->id)->with('booking')->get();

        return response()->json(["message" => "success", "updaeRequests" => BookingUpdateRequestResource::collection($updateRequests)]);
    }

    public function createUpdateRequest(Request $request, Booking $booking)
    {
        $user = $request->user();
        // check tenant
        if ($booking->tenant_id != $user->id) {
            return response()->json(["message" => "you are not the owner of provided booking."], 403);
        }

        $data = $request->validate([
            "requested_tenant_notes" => "sometimes|nullable|string",
            "requested_start_date" => "required|date",
            "requested_end_date" => "required|date|after_or_equal:requested_start_date",
            "requested_number_of_guests" => "sometimes|nullable|numeric"
        ]);
        // check start_date
        $start_date = Carbon::parse($data["requested_start_date"]);
        // if provided start_date == previous one, no validation needed
        if ($start_date != $booking->start_date) {
            if ($start_date < new DateTime()) {
                return response()->json(["message" => "provided start_date should be after today."], 400);
            }
        }

        $updateRequest = BookingUpdateRequest::create([...$data, "booking_id" => $booking->id]);
        $updateRequest->load("booking");
        NotificationService::createNotification(
            $booking->apartment->owner_id,
            'update_request_created',
            'Booking Update Request',
            "{$user->first_name} has requested to update the booking for {$booking->apartment->title}.",
            $updateRequest->id
        );

        return response()->json(["message" => "update request created successfully, waiting approval from owner.", "updateRequest" => new BookingUpdateRequestResource($updateRequest)], 201);
    }
    public function editUpdateRequest(Request $request, BookingUpdateRequest $updateRequest)
    {
        $user = $request->user();
        $booking = $updateRequest->booking;
        // check tenant
        if ($booking->tenant_id != $user->id) {
            return response()->json(["message" => "you are not the owner of booking."], 403);
        }
        // check status
        if (!$updateRequest->isPending()) {
            return response()->json(["message" => "this update request is " . $updateRequest->status . " and you can't edit it."], 400);
        }

        $data = $request->validate([
            "requested_tenant_notes" => "sometimes|nullable|string",
            "requested_start_date" => "required|date",
            "requested_end_date" => "required|date|after_or_equal:requested_start_date",
            "requested_number_of_guests" => "sometimes|nullable|numeric"
        ]);
        // check start_date
        $start_date = Carbon::parse($data["requested_start_date"]);
        // if provided start_date == previous one, no validation needed
        if ($start_date != $booking->start_date) {
            if ($start_date < new DateTime()) {
                return response()->json(["message" => "provided start_date should be after today."], 400);
            }
        }
        $updateRequest->update($data);

        NotificationService::createNotification(
            $booking->apartment->owner_id,
            'update_request_updated',
            'Update request modified',
            'The tenant has modified the update request details.',
            $updateRequest->id
        );

        return response()->json(["message" => "update request updated successfully, waiting approval from owner.", "updateRequest" => new BookingUpdateRequestResource($updateRequest)]);
    }
    public function cancelUpdateRequest(Request $request, BookingUpdateRequest $updateRequest)
    {
        $user = $request->user();
        $booking = $updateRequest->booking;
        // check tenant
        if ($booking->tenant_id != $user->id) {
            return response()->json(["message" => "you are not the owner of booking."], 403);
        }
        // check status
        if (!$updateRequest->isPending()) {
            return response()->json(["message" => "this update request is " . $updateRequest->status . " and you can't cancel it."], 400);
        }
        $updateRequest->update(["status" => BookingUpdateRequestStatus::CANCELLED]);

        NotificationService::createNotification(
            $booking->apartment->owner_id,
            'update_request_cancelled',
            'Update request cancelled',
            'The tenant has cancelled the booking update request.',
            $updateRequest->id
        );

        return response()->json(["message" => "update request cancelled successfully.", "updateRequest" => new BookingUpdateRequestResource($updateRequest)]);
    }

    // =======================
    //          OWNER
    // =======================


    public function ownerGetBooking(Request $request, Booking $booking)
    {
        $user = $request->user();
        $apartment = $booking->apartment;
        if ($apartment->owner_id != $user->id) {
            return response()->json(["message" => "you are not the owner of this booking's apartment."], 403);
        }

        return response()->json(["message" => "booking found successfully.", "booking" => new BookingResource($booking)]);
    }
    public function ownerApproveBooking(Request $request, Booking $booking)
    {
        $user = $request->user();
        $apartment = $booking->apartment;
        // check apartment owner
        if ($apartment->owner_id != $user->id) {
            return response()->json(["message" => "you are not the owner of the booking apartment."], 400);
        }
        // check booking status
        if ($booking->isApproved()) {
            return response()->json(["message" => "this booking is approved already."], 400);
        }
        if ($booking->isRejected()) {
            return response()->json(["message" => "this booking is rejected, you can't change its status."], 400);
        }
        if ($booking->isCancelled()) {
            return response()->json(["message" => "this booking is cancelled, you can't change its status."], 400);
        }
        // check apartment availablity
        $availability = $apartment->checkAvailability($booking->start_date, $booking->end_date);
        if (!$availability["available"]) {
            return response()->json(["message" => $availability["message"]], 400);
        }

        // handle user wallet
        $wallet = $user->wallet;
        if ($wallet < $booking->total_price) {
            return response()->json(["message" => "you can't approve this booking, the user has no enough money."]);
        }
        $user->update(["wallet" => $wallet - $booking->total_price]);

        $booking->update(["status" => BookingStatus::APPROVED]);

        NotificationService::createNotification(
            $booking->tenant_id,
            'booking_approved',
            'Booking Approved',
            "Your booking for {$apartment->title} from {$booking->start_date} to {$booking->end_date} has been approved.",
            $booking->id
        );

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
        if ($booking->isRejected()) {
            return response()->json(["message" => "this booking is rejected already."], 400);
        }
        if ($booking->isApproved()) {
            return response()->json(["message" => "this booking is approved, you can't change its status."], 400);
        }
        if ($booking->isCancelled()) {
            return response()->json(["message" => "this booking is cancelled, you can't change its status."], 400);
        }

        $booking->update(["status" => BookingStatus::REJECTED]);

        NotificationService::createNotification(
            $booking->tenant_id,
            'booking_rejected',
            'Booking Rejected',
            "Your booking for {$apartment->title} from {$booking->start_date} to {$booking->end_date} has been rejected.",
            $booking->id
        );

        return response()->json(["message" => "booking rejected successfully", "booking" => new BookingResource($booking)]);
    }
    public function ownerListBookings(Request $request, Apartment $apartment)
    {
        $user = $request->user();
        // check owner
        if ($apartment->owner_id != $user->id) {
            return response()->json(["message" => "you are not the owner of the booking apartment."], 400);
        }
        $bookings = Booking::where("apartment_id", $apartment->id)->get();

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
        if ($booking->isRejected()) {
            return response()->json(["message" => "the related booking is rejected so you can't change the update request status."], 400);
        }
        // check request status
        if ($updateRequest->isApparoved()) {
            return response()->json(["message" => "this update request is approved already."], 400);
        }
        if ($updateRequest->isRejected()) {
            return response()->json(["message" => "this update request is rejected, you can't approve it."], 400);
        }
        if ($updateRequest->isCancelled()) {
            return response()->json(["message" => "this update request is cancelled, you can't approve it."], 400);
        }
        // calculate total price
        $total_price = Booking::calculateTotalPrice($updateRequest->start_date, $updateRequest->end_date, $apartment->price_per_nigth);

        // update user wallet
        $tenant = $booking->tenant;
        $wallet = $tenant->wallet;
        $amount_to_add = $booking->total_price - $total_price;
        // check if user has enough money
        if ($amount_to_add < 0) {
            if ($wallet < $amount_to_add) {
                return response()->json(["message" => "user has no enough money for the specified duration in the update request."]);
            }
        }
        $tenant->update(["wallet" => $wallet + $amount_to_add]);
        // update request
        $updateRequest->update(["status" => BookingUpdateRequestStatus::APPROVED]);
        NotificationService::createNotification(
            $booking->tenant_id,
            'update_request_approved',
            'Update Request Approved',
            "Your update request for {$apartment->title} booking has been approved.",
            $updateRequest->id
        );

        return response()->json(["message" => "update request approved successfully.", "updateRequest" => new BookingUpdateRequestResource($updateRequest)]);
    }
    public function ownerRejectUpdateRequest(Request $request, BookingUpdateRequest $updateRequest)
    {
        $user = $request->user();
        // check owner
        $booking = $updateRequest->booking;
        $apartment = $booking->apartment;
        if ($apartment->owner_id != $user->id) {
            return response()->json(["message" => "you are not the owner of booking's apartment."], 400);
        }
        // check if booking approved
        if ($booking->isRejected()) {
            return response()->json(["message" => "the related booking is rejected so you can't change the update request status."], 400);
        }
        // check request status
        if ($updateRequest->isApparoved()) {
            return response()->json(["message" => "this update request is approved, you can't change its status."], 400);
        }
        if ($updateRequest->isRejected()) {
            return response()->json(["message" => "this update request is rejected already."], 400);
        }
        if ($updateRequest->isCancelled()) {
            return response()->json(["message" => "this update request is cancelled, you can't reject it."], 400);
        }

        // update request
        $updateRequest->update(["status" => BookingUpdateRequestStatus::REJECTED]);
        NotificationService::createNotification(
            $booking->tenant_id,
            'update_request_rejected',
            'Update Request Rejected',
            "Your update request for {$apartment->title} booking has been rejected.",
            $updateRequest->id
        );

        return response()->json(["message" => "update request rejected successfully.", "updateRequest" => new BookingUpdateRequestResource($updateRequest)]);
    }

    public function ownerListUpdateRequests(Request $request, Booking $booking)
    {
        $user = $request->user();
        $apartment = $booking->apartment;
        if ($apartment->owner_id != $user->id) {
            return response()->json(["message" => "you are not the owner of booking's apartment."]);
        }

        $updateRequests = BookingUpdateRequest::where("booking_id", $booking->id)->get();

        return response()->json(["message" => "success", "updateRequests" => BookingUpdateRequestResource::collection($updateRequests)]);
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
            "status" => ['required', Rule::enum(BookingStatus::class)]
        ]);

        // check apartment availablity
        if ($validated_data["status"] == BookingStatus::APPROVED->value) {
            $availability = $apartment->checkAvailability($validated_data["start_date"], $validated_data["end_date"]);
            return response()->json(["message" => $availability], 400);
            if (!$availability["available"]) {
                return response()->json(["message" => $availability["message"]], 400);
            }
        }

        // check tenant
        $tenant = User::findOrFail($validated_data["tenant_id"]);
        if (!$tenant->role == UserRole::TENANT) {
            return response()->json(["message" => "provided user should be of role tenant"], 400);
        }
        // handle total_price
        $start_date = new DateTime($validated_data["start_date"]);
        $end_date = new DateTime($validated_data["end_date"]);
        $total_price = Booking::calculateTotalPrice($validated_data["start_date"], $validated_data["end_date"], $apartment->price_per_night);

        $booking = Booking::create([
            ...$validated_data,
            "apartment_id" => $apartment->id,
            "total_price" => $total_price
        ]);
        NotificationService::createNotification(
            $booking->tenant_id,
            'booking_created_by_admin',
            'Booking created',
            'An admin has created a booking for you.',
            $booking->id
        );

        NotificationService::createNotification(
            $apartment->owner_id,
            'booking_created_by_admin',
            'New booking created',
            'An admin has created a booking for your apartment.',
            $booking->id
        );
        return response()->json([
            "message" => "booking created successfully",
            "booking" => new BookingResource($booking)
        ], 201);
    }
    public function adminUpdateBooking(Request $request, Booking $booking)
    {
        $validated_data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
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
        $total_price = Booking::calculateTotalPrice($validated_data["start_date"], $validated_data["end_date"], $apartment->price_per_night);

        $booking->update([...$validated_data, "total_price" => $total_price]);
        NotificationService::createNotification(
            $booking->tenant_id,
            'booking_updated_by_admin',
            'Booking updated',
            'An admin has updated a booking for you.',
            $booking->id
        );

        NotificationService::createNotification(
            $apartment->owner_id,
            'booking_updated_by_admin',
            'booking updated',
            'An admin has updated a booking for your apartment.',
            $booking->id
        );
        return response()->json(["message" => "booking updated successfully", "booking" => new BookingResource($booking)]);
    }
    public function adminDeleteBooking(Request $request, Booking $booking)
    {
        // validate booking
        if ($booking->isApproved()) {
            return response()->json(["message" => "you can't delete approved bookings."], 400);
        }

        NotificationService::createNotification(
            $booking->tenant_id,
            'booking_deleted_by_admin',
            'Booking cancelled',
            'Your booking has been cancelled by an admin.',
            $booking->id
        );


        NotificationService::createNotification(
            $booking->apartment->owner_id,
            'booking_deleted_by_admin',
            'Booking cancelled',
            'A booking for your apartment has been cancelled by an admin.',
            $booking->id
        );
        $booking->delete();

        return response()->json(["message" => "booking deleted successfully"], 204);
    }
    public function adminListBookings(Request $request)
    {
        $query = Booking::query()->with('apartment');

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
        $updateRequests = BookingUpdateRequest::where("booking_id", $booking->id);
        return response()->json(["message" => "success", "updateRequests" => BookingUpdateRequestResource::collection($updateRequests)]);
    }
    public function adminDeleteUpdateRequest(Request $request, BookingUpdateRequest $updateRequest)
    {
        $updateRequest->delete();
        return response()->json(["message" => "update request deleted successfully"], 204);
    }
}
