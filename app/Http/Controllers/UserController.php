<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        if ($user->isPending()) {
            return response()->json([
                "message" => "waiting for admin approval, you can't perform this action."
            ], 403);
        }
        if ($user->isRejected()) {
            return response()->json([
                "message" => "your account gets rejected from admin, you can't perform this action."
            ], 403);
        }
        try {
            $validatedData = $request->validate([
                "phone" => "string|unique:users,phone",
                "role" => Rule::enum(UserRole::class)->except([UserRole::ADMIN]),
                "first_name" => "string|max:50",
                "last_name" => "string|max:50",
                "password" => "min:8",
                "birth_date" => "date",
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

            $updatedFields = $validatedData;
            if (array_key_exists("password", $validatedData)) {
                $updatedFields["password"] = Hash::make($validatedData["password"]);
            }

            if ($profileImagePath) {
                $updatedFields["avatar"] = $profileImagePath;
            }
            if ($idImagePath) {
                $updatedFields["id_card"] = $idImagePath;
            }
            $user->update([...$updatedFields, "status" => "pending"]);

            return response()->json([
                "message" => "Your profile updated it successfully, waiting for admin approval",
                "user" => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'operation failed. Please try again.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    public function getProfile(Request $request)
    {
        try {

            return response()->json(["message" => "success", "user" => $request->user()], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'operation failed. Please try again.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    public function deleteProfile(Request $request)
    {
        try {
            request()->user()->delete();
            return response()->json(["message" => "Profile deleted successfully"], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'operation failed. Please try again.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function createUser(Request $request)
    {
        try {
            $validatedData = $request->validate([
                "phone" => "string|unique:users,phone",
                "role" => Rule::enum(UserRole::class),
                "status" => Rule::enum(UserStatus::class),
                "first_name" => "string|max:50",
                "last_name" => "string|max:50",
                "password" => "min:8",
                "birth_date" => "date",
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
                'message' => 'User created successfully.',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'operation failed. Please try again.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function updateUser(Request $request, User $user)
    {
        try {
            $validatedData = $request->validate([
                "phone" => "string|unique:users,phone",
                "role" => Rule::enum(UserRole::class),
                "status" => Rule::enum(UserStatus::class),
                "first_name" => "string|max:50",
                "last_name" => "string|max:50",
                "password" => "min:8",
                "birth_date" => "date",
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

            $updatedFields = $validatedData;
            if (array_key_exists("password", $validatedData)) {
                $updatedFields["password"] = Hash::make($validatedData["password"]);
            }

            if ($profileImagePath) {
                $updatedFields["avatar"] = $profileImagePath;
            }
            if ($idImagePath) {
                $updatedFields["id_card"] = $idImagePath;
            }

            $user->update($updatedFields);

            return response()->json([
                "message" => "User updated successfully.",
                "user" => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'operation failed. Please try again.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function getUser(Request $request, User $user)
    {
        try {
            return response()->json(["message" => "User found successfully", "user" => $user], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'operation failed. Please try again.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function listUsers(Request $request)
    {
        try {
            $query = User::query();

            // Filter using when() - cleaner syntax
            $query->when($request->filled('first_name'), function ($q) use ($request) {
                return $q->where('first_name', 'LIKE', '%' . $request->first_name . '%');
            })
                ->when($request->filled('last_name'), function ($q) use ($request) {
                    return $q->where('last_name', 'LIKE', '%' . $request->last_name . '%');
                })
                ->when($request->filled('phone'), function ($q) use ($request) {
                    return $q->where('phone', 'last_name', 'LIKE', '%' . $request->phone . '%');
                })
                ->when($request->filled('role'), function ($q) use ($request) {
                    return $q->where('role', $request->role);
                })
                ->when($request->filled('status'), function ($q) use ($request) {
                    return $q->where('status', $request->status);
                })
                ->when($request->filled('birth_date'), function ($q) use ($request) {
                    return $q->whereDate('birth_date', $request->birth_date);
                })
                ->when($request->filled('birth_date_from'), function ($q) use ($request) {
                    return $q->whereDate('birth_date', '>=', $request->birth_date_from);
                })
                ->when($request->filled('birth_date_to'), function ($q) use ($request) {
                    return $q->whereDate('birth_date', '<=', $request->birth_date_to);
                });

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);

            $result = $query->paginate($perPage);
            return response()->json(["message" => "success", "users" => $result], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'operation failed. Please try again.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function deleteUser(Request $request, User $user)
    {
        try {
            $user->delete();
            return response()->json(["message" => "User deleted successfully"], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'operation failed. Please try again.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
