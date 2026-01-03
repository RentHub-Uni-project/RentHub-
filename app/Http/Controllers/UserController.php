<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function getUser(Request $request, User $user)
    {

        if (!$request->user()->isAdmin() && !$user->isApproved()) {
            return response()->json(["message" => "user not found."], 404);
        }
        return response()->json(["message" => "user found successfully.", "user" => new UserResource($user)]);
    }
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
        $validatedData = $request->validate([
            "phone" => "sometimes|string|unique:users,phone",
            "role" => ["sometimes", Rule::enum(UserRole::class)->except([UserRole::ADMIN])],
            "first_name" => "sometimes|string|max:50",
            "last_name" => "sometimes|string|max:50",
            "password" => "sometimes|min:8",
            "birth_date" => "sometimes|date",
            "avatar" => "sometimes|nullable|image|max:5120|mimes:jpg,jpeg,png",
            "id_card" => "sometimes|nullable|image|max:5120|mimes:jpg,jpeg,png",

            "delete_id_card" => "sometimes|in:true,false",
            "delete_avatar" => "sometimes|in:true,false"
        ]);

        return response(["message" => $validatedData, "request" => $request, "request-facade" => request()]);

        // handle file uploads
        $profileImagePath = null;
        $idImagePath = null;

        DB::beginTransaction();

        try {
            $updatedFields = $validatedData;
            // delete images if they provided as null
            if ($request->has("delete_avatar")) {
                if ($validatedData["delete_avatar"] == "true") {
                    if ($user->avatar) {
                        if (Storage::disk('public')->exists($user->avatar)) {
                            Storage::disk('public')->delete($user->avatar);
                        }
                    }

                    unset($updatedFields["delete_avatar"]);
                    $updatedFields["avatar"] = null;
                }
            }
            if ($request->has("delete_id_card")) {
                if ($validatedData["delete_id_card"] == "true") {
                    if ($user->id_card) {
                        if (Storage::disk('public')->exists($user->id_card)) {
                            Storage::disk('public')->delete($user->id_card);
                        }
                    }
                    unset($updatedFields["delete_id_card"]);
                    $updatedFields["id_card"] = null;
                }
            }
            // store new images if they exist
            if ($request->hasFile('avatar')) {
                $profileImagePath = $request->file('avatar')
                    ->store('profiles/' . $user->id, 'public');
                if ($user->avatar) {
                    if (Storage::disk('public')->exists($user->avatar)) {
                        Storage::disk('public')->delete($user->avatar);
                    }
                }
                $updatedFields["avatar"] = $profileImagePath;
            }

            if ($request->hasFile('id_card')) {
                $idImagePath = $request->file('id_card')
                    ->store('ids/' . $user->id, 'public');
                if ($user->id_card) {
                    if (Storage::disk('public')->exists($user->id_card)) {
                        Storage::disk('public')->delete($user->id_card);
                    }
                }
                $updatedFields["id_card"] = $idImagePath;
            }


            if ($request->has('password')) {
                $updatedFields["password"] = Hash::make($validatedData["password"]);
            }

            $user->update($updatedFields);

            DB::commit();

            return response()->json([
                "message" => "Your profile updated it successfully, waiting for admin approval",
                'user' => new UserResource($user)
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getProfile(Request $request)
    {

        return response()->json(["message" => "success", "user" => new UserResource($request->user())], 200);
    }
    public function deleteProfile(Request $request)
    {
        $user = $request->user();

        DB::beginTransaction();

        try {
            $user->delete();
            // delete images
            Storage::disk("public")->deleteDirectory("ids/" . $user->id);
            Storage::disk("public")->deleteDirectory("profiles/" . $user->id);

            DB::commit();

            return response()->json(["message" => "Profile deleted successfully"], 204);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Admin
    public function adminCreateUser(Request $request)
    {
        $validatedData = $request->validate([
            "phone" => "required|string|unique:users,phone",
            "role" => ['required', Rule::enum(UserRole::class)],
            "status" => ['required', Rule::enum(UserStatus::class)],
            "first_name" => "required|string|max:50",
            "last_name" => "required|string|max:50",
            "password" => "required|min:8",
            "birth_date" => "required|date",
            "wallet" => "sometimes|numeric",
            "avatar" => "nullable|image|max:5120|mimes:jpg,jpeg,png",
            "id_card" => "nullable|image|max:5120|mimes:jpg,jpeg,png"
        ]);

        // handle file uploads
        $profileImagePath = null;
        $idImagePath = null;

        DB::beginTransaction();

        try {
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

            DB::commit();

            return response()->json([
                'message' => 'User created successfully.',
                'user' => new UserResource($user)
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function adminUpdateUser(Request $request, User $user)
    {
        $validatedData = $request->validate([
            "phone" => "sometimes|string|unique:users,phone",
            "role" => ["sometimes", Rule::enum(UserRole::class)],
            "status" => ["sometimes", Rule::enum(UserStatus::class)],
            "first_name" => "sometimes|string|max:50",
            "last_name" => "sometimes|string|max:50",
            "password" => "sometimes|min:8",
            "birth_date" => "sometimes|date",
            "wallet" => "sometimes|numeric",
            "avatar" => "sometimes|nullable|image|max:5120|mimes:jpg,jpeg,png",
            "id_card" => "sometimes|nullable|image|max:5120|mimes:jpg,jpeg,png",

            "delete_id_card" => "sometimes|in:true,false",
            "delete_avatar" => "sometimes|in:true,false"
        ]);

        // handle file uploads
        $profileImagePath = null;
        $idImagePath = null;

        DB::beginTransaction();
        try {
            $updatedFields = $validatedData;
            // delete images if they provided as null
            if ($request->has("delete_avatar")) {
                if ($validatedData["delete_avatar"] == "true") {
                    if ($user->avatar) {
                        if (Storage::disk('public')->exists($user->avatar)) {
                            Storage::disk('public')->delete($user->avatar);
                        }
                    }

                    unset($updatedFields["delete_avatar"]);
                    $updatedFields["avatar"] = null;
                }
            }
            if ($request->has("delete_id_card")) {
                if ($validatedData["delete_id_card"] == "true") {

                    if ($user->id_card != null) {
                        if (Storage::disk('public')->exists($user->id_card)) {
                            Storage::disk('public')->delete($user->id_card);
                        }
                    }

                    unset($updatedFields["delete_id_card"]);
                    Arr::set($updatedFields, "id_card", null);
                }
            }
            // store new images if they exist

            if ($request->hasFile('avatar')) {
                $profileImagePath = $request->file('avatar')
                    ->store('profiles/' . $user->id, 'public');
                if ($user->avatar) {
                    if (Storage::disk('public')->exists($user->avatar)) {
                        Storage::disk('public')->delete($user->avatar);
                    }
                }
                $updatedFields["avatar"] = $profileImagePath;
            }

            if ($request->hasFile('id_card')) {
                $idImagePath = $request->file('id_card')
                    ->store('ids/' . $user->id, 'public');
                if ($user->id_card) {
                    if (Storage::disk('public')->exists($user->id_card)) {
                        Storage::disk('public')->delete($user->id_card);
                    }
                }
                $updatedFields["id_card"] = $idImagePath;
            }


            if ($request->has('password')) {
                $updatedFields["password"] = Hash::make($validatedData["password"]);
            }

            $user->update($updatedFields);

            DB::commit();

            return response()->json([
                "message" => "User updated successfully.",
                'user' => new UserResource($user)
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function adminListUsers(Request $request)
    {
        $query = User::query();

        $query->when($request->filled('first_name'), function ($q) use ($request) {
            return $q->where('first_name', 'LIKE', '%' . $request->first_name . '%');
        })
            ->when($request->filled('last_name'), function ($q) use ($request) {
                return $q->where('last_name', 'LIKE', '%' . $request->last_name . '%');
            })
            ->when($request->filled('phone'), function ($q) use ($request) {
                return $q->where('phone', 'LIKE', '%' . $request->phone . '%');
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
        return response()->json(["message" => "success", "data" => $result], 200);
    }

    public function adminDeleteUser(Request $request, User $user)
    {
        DB::beginTransaction();
        try {
            $user->delete();
            // delete images
            Storage::disk("public")->deleteDirectory("ids/" . $user->id);
            Storage::disk("public")->deleteDirectory("profiles/" . $user->id);

            DB::commit();

            return response()->json(["message" => "User deleted successfully"], 204);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
