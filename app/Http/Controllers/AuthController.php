<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {

        try {
            $validatedData = $request->validate([
                "phone" => "required|string|unique:users,phone",
                "role" => Rule::enum(UserRole::class)->except([UserRole::ADMIN]),
                "first_name" => "required|string|max:50",
                "last_name" => "required|string|max:50",
                "password" => "required|min:8",
                "birth_date" => "required|date",
                "avatar" => "nullable|image|max:5120|mimes:jpg,jpeg,png",
                "id_card" => "nullable|image|max:5120|mimes:jpg,jpeg,png"
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 400);
        }

        try {
            // handle file uploads
            $profileImagePath = null;
            $idImagePath = null;

            if ($request->hasFile('avatar')) {
                $profileImagePath = $request->file('avatar')
                    ->store('profiles', 'public');
            }

            if ($request->hasFile('id_card')) {
                $idImagePath = $request->file('id_card')
                    ->store('ids', 'public');
            }

            $user = User::create([
                ...$validatedData,
                'avatar' => "storage/" . $profileImagePath,
                'id_card' => "storage/" . $idImagePath,
                'password' => Hash::make($validatedData["password"]),
            ]);

            return response()->json([
                'message' => 'Registration successful. Waiting for admin approval.',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'operation failed. Please try again.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'phone' => 'required',
                'password' => 'required'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 400);
        }
        try {
            $user = User::where('phone', $validatedData["phone"])->first();

            if (!$user || !Hash::check($validatedData["password"], $user->password)) {
                return response()->json([
                    'message' => 'User not found'
                ], 401);
            }

            if ($user->isRejected()) {
                return response()->json([
                    'message' => 'Account rejected from admin.'
                ], 403);
            }
            if ($user->isPending()) {
                return response()->json([
                    'message' => 'Account pending admin approval. Please wait for approval.'
                ], 403);
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'operation failed. Please try again.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logged out successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'operation failed. Please try again.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
