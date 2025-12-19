<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AppartmentController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::patch("/users/update-my-profile", [UserController::class, "updateProfile"]);
    Route::get('/users/get-my-profile', [UserController::class, "getProfile"]);
    Route::delete('/users/delete-my-profile', [UserController::class, "deleteProfile"]);
    Route::get('/users/list', [UserController::class, "listUsers"]);
    Route::get('/users/{user}', [UserController::class, "getUser"]);
    Route::post('/users/create', [UserController::class, "createUser"]);
    Route::patch('/users/update/{user}', [UserController::class, "updateUser"]);
    Route::delete('/users/delete/{user}', [UserController::class, "deleteUser"]);


    Route::post('/auth/logout', [AuthController::class, 'logout']);
});



Route::prefix('apartments')->group(function () {
    Route::get('/', [AppartmentController::class, 'index']);
    Route::get('/{id}', [AppartmentController::class, 'show']);
});

Route::middleware('auth:sanctum')->prefix('owner')->group(function () {
    Route::get('/apartments', [AppartmentController::class, 'myApartments']);
    Route::post('/apartments', [AppartmentController::class, 'store']);
    Route::put('/apartments/{id}', [AppartmentController::class, 'update']);
    Route::delete('/apartments/{id}', [AppartmentController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/apartments', [AppartmentController::class, 'adminIndex']);
    Route::post('/apartments', [AppartmentController::class, 'adminStore']);
    Route::put('/apartments/{id}', [AppartmentController::class, 'adminUpdate']);
    Route::delete('/apartments/{id}', [AppartmentController::class, 'adminDelete']);
});
