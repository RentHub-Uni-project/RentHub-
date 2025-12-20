<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AppartmentController;
use App\Http\Controllers\ReviewController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// just for now, allow creating users without restrictions
Route::post('/users/create', [UserController::class, "createUser"]);


// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::patch("/users/update-my-profile", [UserController::class, "updateProfile"]);
    Route::get('/users/get-my-profile', [UserController::class, "getProfile"]);
    Route::delete('/users/delete-my-profile', [UserController::class, "deleteProfile"]);
    Route::get('/users/list', [UserController::class, "listUsers"]);
    Route::get('/users/{user}', [UserController::class, "getUser"]);
    Route::patch('/users/update/{user}', [UserController::class, "updateUser"]);
    Route::delete('/users/delete/{user}', [UserController::class, "deleteUser"]);


    Route::post('/auth/logout', [AuthController::class, 'logout']);
});



// Public APIs for apartment
Route::prefix('apartments')->group(function () {
    Route::get('/', [AppartmentController::class, 'index']);
    Route::get('/{id}', [AppartmentController::class, 'show']);
    Route::get('/search', [AppartmentController::class, 'search']); // Search by address
    Route::get('/filter', [AppartmentController::class, 'filter']);      // Filter apartments
    Route::get('/{id}/ratings', [ReviewController::class, 'ratings']); // Show ratings
});

 // Owner APIs
Route::middleware(['auth:sanctum', 'role:owner'])->prefix('owner')->group(function () {
    //  for Apartment
    Route::get('/apartments', [AppartmentController::class, 'myApartments']);
    Route::post('/apartments', [AppartmentController::class, 'store']);
    Route::put('/apartments/{id}', [AppartmentController::class, 'update']);
    Route::delete('/apartments/{id}', [AppartmentController::class, 'destroy']);
});




// Admin APIs
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    //  for apartment
    Route::get('/apartments', [AppartmentController::class, 'adminIndex']);
    Route::post('/apartments', [AppartmentController::class, 'adminStore']);
    Route::put('/apartments/{id}', [AppartmentController::class, 'adminUpdate']);
    Route::delete('/apartments/{id}', [AppartmentController::class, 'adminDelete']);

    // Admin Approval
    Route::post('/apartments/{id}/approve', [AppartmentController::class, 'approve']);
    Route::post('/apartments/{id}/reject', [AppartmentController::class, 'reject']);

    // for Reviewing
    Route::get('/reviews', [ReviewController::class, 'adminListReviews']);
    Route::post('/reviews', [ReviewController::class, 'adminCreateReview']);
    Route::put('/reviews/{id}', [ReviewController::class, 'adminUpdateReview']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'adminDeleteReview']);

});

// Tenant APIs
Route::middleware(['auth:sanctum', 'role:tenant'])->prefix('tenant')->group(function () {
    //  for apartment
    Route::post('/apartments/{id}/favorite', [AppartmentController::class, 'toggleFavorite']);
    Route::get('/favorites', [AppartmentController::class, 'myFavorites']);

    // for Reviewing
    Route::post('/apartments/{id}/rate', [ReviewController::class, 'rate']);
    Route::get('/reviews', [AppartmentController::class, 'myReviews']);
    Route::put('/reviews/{id}', [AppartmentController::class, 'updateReview']);
    Route::delete('/reviews/{id}', [AppartmentController::class, 'deleteReview']);

});


