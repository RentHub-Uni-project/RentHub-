<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\FavoriteApartmentController;
use App\Http\Controllers\ReviewController;
use App\Models\Booking;
use App\Models\BookingUpdateRequest;
use Illuminate\Support\Facades\Route;

// Auth
Route::prefix("auth")->group(function () {
    // unprotected routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});


// Users
Route::prefix("users")->middleware(['auth:sanctum'])->group(function () {
    // General APIs
    Route::get('/get/{user}', [UserController::class, "getUser"]);
    Route::patch("/update-my-profile", [UserController::class, "updateProfile"]);
    Route::get('/get-my-profile', [UserController::class, "getProfile"]);
    Route::delete('/delete-my-profile', [UserController::class, "deleteProfile"]);

    // Admin APIs
    Route::prefix("admin")->middleware("role:admin")->group(function () {
        Route::post('/create', [UserController::class, "adminCreateUser"]);
        Route::get('/list', [UserController::class, "adminListUsers"]);
        Route::patch('/update/{user}', [UserController::class, "adminUpdateUser"]);
        Route::delete('/delete/{user}', [UserController::class, "adminDeleteUser"]);
    });
});


// Apartments
Route::prefix("apartments")->middleware(['auth:sanctum'])->group(function () {
    // General APIs
    Route::get('/list', [ApartmentController::class, 'index']);
    Route::get('/get/{apartment}', [ApartmentController::class, 'show']);
    Route::get('/search', [ApartmentController::class, 'search']); // Search by address
    Route::get('/filter', [ApartmentController::class, 'filter']);      // Filter apartments
    Route::get('/list-reviews/{apartment}', [ReviewController::class, 'listApartmentReviews']); // Show ratings

    // Admin APIs
    Route::prefix("admin")->middleware("role:admin")->group(function () {
        Route::get('/list', [ApartmentController::class, 'adminIndex']);
        Route::post('/create', [ApartmentController::class, 'adminStore']);
        Route::put('/update/{apartment}', [ApartmentController::class, 'adminUpdate']);
        Route::delete('/delete/{apartment}', [ApartmentController::class, 'adminDelete']);

        Route::post('/approve/{apartment}', [ApartmentController::class, 'approve']);
        Route::post('/reject/{apartment}', [ApartmentController::class, 'reject']);
    });

    // Owner APIs
    Route::prefix("owner")->middleware('role:owner')->group(function () {
        Route::get('/list-my-apartments', [ApartmentController::class, 'myApartments']);
        Route::post('/create', [ApartmentController::class, 'store']);
        Route::patch('/update/{apartment}', [ApartmentController::class, 'update']);
        Route::delete('/delete/{apartment}', [ApartmentController::class, 'destroy']);
        Route::get("/list-bookings/{apartment}", [BookingController::class, 'ownerListBookings']);
    });

    // Tenant APIs
    Route::prefix("tenant")->middleware("role:tenant")->group(function () {
        Route::post('/mark-as-favorite/{apartment}', [FavoriteApartmentController::class, 'toggleFavorite']);
        Route::get('/list-my-favorites', [FavoriteApartmentController::class, 'myFavorites']);
    });
});

// Reviews
Route::prefix("reviews")->middleware(['auth:sanctum'])->group(function () {
    // General APIs
    Route::get('/get/{review}', [ReviewController::class, 'getReview']);
    // Admin APIs
    Route::prefix("admin")->middleware("role:admin")->group(function () {
        Route::get('/get/{review}', [ReviewController::class, 'adminGetReview']);
        Route::get('/list', [ReviewController::class, 'adminListReviews']);
        Route::post('/create', [ReviewController::class, 'adminCreateReview']);
        Route::patch('/update/{review}', [ReviewController::class, 'adminUpdateReview']);
        Route::delete('/delete/{review}', [ReviewController::class, 'adminDeleteReview']);
    });

    // Tenant APIs
    Route::prefix("tenant")->middleware("role:tenant")->group(function () {
        Route::post('/create/{apartment}', [ReviewController::class, 'createReview']);
        Route::get('/list', [ReviewController::class, 'myReviews']);
        Route::patch('/update/{review}', [ReviewController::class, 'updateReview']);
        Route::delete('/delete/{review}', [ReviewController::class, 'deleteReview']);
    });
});

// Bookings
Route::prefix("bookings")->middleware(["auth:sanctum"])->group(function () {
    Route::prefix("tenant")->middleware("role:tenant")->group(function () {
        Route::get("/get/{booking}", [BookingController::class, "getBooking"]);
        Route::get("/list-my-bookings", [BookingController::class, "listMyBookings"]);
        Route::post("/create/{apartment}", [BookingController::class, "createBooking"]);
        Route::post("/cancel/{booking}", [BookingController::class, "cancelBooking"]);

        Route::get('/get-update-request/{updateRequest}', [BookingController::class, "getUpdateRequest"]);
        Route::get('/list-update-requests/{booking}', [BookingController::class, "listUpdateRequests"]);
        Route::post('/creat-update-request/{booking}', [BookingController::class, "createUpdateRequest"]);
        Route::put('/edit-update-request/{updateRequest}', [BookingController::class, "editUpdateRequest"]);
        Route::post('/cancel-update-request/{updateRequest}', [BookingController::class, "cancelUpdateRequest"]);
    });

    Route::prefix("owner")->middleware("role:owner")->group(function () {
        Route::put("/approve-booking/{booking}", [BookingController::class, "ownerApproveBooking"]);
        Route::put("/reject-booking/{booking}", [BookingController::class, "ownerRejectBooking"]);
        Route::get("/get-booking/{booking}", [BookingController::class, "ownerGetBooking"]);
        Route::get("/get-update-request/{updateRequest}", [BookingController::class, "ownerGetUpdateRequest"]);
        Route::get("/list-update-requests/{booking}", [BookingController::class, "ownerListUpdateRequests"]);
        Route::put("/approve-update-request/{updateRequest}", [BookingController::class, "ownerApproveUpdateRequest"]);
        Route::put("/reject-update-request/{updateRequest}", [BookingController::class, "ownerRejectUpdateRequest"]);
    });

    Route::prefix("admin")->middleware("role:admin")->group(function () {
        Route::get("/get/{booking}", [BookingController::class, "adminGetBooking"]);
        Route::get("/list", [BookingController::class, "adminListBookings"]);
        Route::post("/create/{apartment}", [BookingController::class, "adminCreateBooking"]);
        Route::patch("/update/{booking}", [BookingController::class, "adminUpdateBooking"]);
        Route::delete("/delete/{booking}", [BookingController::class, "adminDeleteBooking"]);

        Route::get("/list-update-requests/{booking}", [BookingController::class, "adminListUpdateRequests"]);
        Route::get("/get-update-request/{updateRequest}", [BookingController::class, "adminGetUpdateRequest"]);
        Route::get("/delete-update-requests/{updateRequest}", [BookingController::class, "adminDeleteUpdateRequest"]);
    });
});
