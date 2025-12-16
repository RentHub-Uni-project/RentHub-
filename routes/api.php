<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
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
