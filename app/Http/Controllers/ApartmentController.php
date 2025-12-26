<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\ApartmentImage;
use Illuminate\Http\Request;
use App\Http\Requests\StoreApartmentRequest;
use App\Http\Requests\UpdateApartmentRequest;
use App\Http\Requests\AdminApartmentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;



class ApartmentController extends Controller
{
    // ======================
    //  ALL USERS - Public APIs
    // ======================

    // List all approved apartments

    public function index(Request $request)     // GET /apartments?keyword=studio&city=Berlin&min_price=50&max_price=200&per_page=10
    {
        $query = Apartment::query()
            ->where('status', 'approved')
            ->with('images'); // load apartment images

        /*  Search by keyword (title + address) */
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;

            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('address', 'like', "%{$keyword}%");
            });
        }

        /*  Price filter */
        if ($request->filled('min_price')) {
            $query->where('price_per_night', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price_per_night', '<=', $request->max_price);
        }

        /*  Guests filter */
        if ($request->filled('guests')) {
            $query->where('max_guests', '>=', $request->guests);
        }

        /*  Bedrooms */
        if ($request->filled('bedrooms')) {
            $query->where('bedrooms', $request->bedrooms);
        }

        /*  Bathrooms */
        if ($request->filled('bathrooms')) {
            $query->where('bathrooms', $request->bathrooms);
        }

        /*  City */
        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        /*  Default sorting (latest approved apartments first) */
        $query->orderBy('average_rating', 'desc');

        /*  Pagination */
        $apartments = $query->paginate(
            $request->get('per_page', 15)
        );

        return response()->json([
            'message' => 'success',
            'data' => $apartments
        ]);
    }


    // Show details of a specific apartment
    public function show($id)
    {
        $apartment = Apartment::where('status', 'approved')->findOrFail($id);
        return response()->json(["message" => "apartment found successfully.", "apartment" => $apartment]);
    }

    // ======================
    //  OWNER - Owner APIs
    // ======================

    public function myApartments(Request $request)
    {
        $user = $request->user();
        return Apartment::where('owner_id', $user->id)->get();
    }


    public function store(StoreApartmentRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['owner_id'] = $request->user()->id;
            $data['status'] = 'pending';

            $apartment = Apartment::create($data);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store("apartments/{$apartment->id}", 'public');

                    ApartmentImage::create([
                        'apartment_id' => $apartment->id,
                        'image_url' => $path,
                        'display_order' => $index + 1,
                        'is_main' => $index === 0,
                    ]);
                }
            }

            if ($request->filled('is_main')) {
                $mainImageId = $request->is_main;

                ApartmentImage::where('apartment_id', $apartment->id)
                    ->update(['is_main' => false]);

                $mainImage = ApartmentImage::find($mainImageId);
                if ($mainImage && $mainImage->apartment_id == $apartment->id) {
                    $mainImage->update(['is_main' => true]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Apartment created successfully',
                'apartment' => $apartment,
                'images' => $apartment->images()->get()
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create apartment',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    public function update(UpdateApartmentRequest $request, $id)
    {
        $user = $request->user();

        if ($user->role === 'owner') {
            $apartment = Apartment::where('owner_id', $user->id)->findOrFail($id);
        } else {
            $apartment = Apartment::findOrFail($id);
        }

        DB::beginTransaction();

        try {
            $apartment->update($request->validated());

            if ($request->filled('delete_images')) {
                foreach ($request->delete_images as $imgId) {
                    $img = ApartmentImage::find($imgId);
                    if ($img && $img->apartment_id == $apartment->id) {
                        if (Storage::disk('public')->exists($img->image_url)) {
                            Storage::disk('public')->delete($img->image_url);
                        }
                        $img->delete();
                    }
                }
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store("apartments/{$apartment->id}", 'public');

                    ApartmentImage::create([
                        'apartment_id' => $apartment->id,
                        'image_url' => $path,
                        'display_order' => ApartmentImage::where('apartment_id', $apartment->id)->max('display_order') + 1,
                        'is_main' => false,
                    ]);
                }
            }

            if ($request->filled('is_main')) {
                $mainImageId = $request->is_main;

                ApartmentImage::where('apartment_id', $apartment->id)
                    ->update(['is_main' => false]);

                $mainImage = ApartmentImage::find($mainImageId);
                if ($mainImage && $mainImage->apartment_id == $apartment->id) {
                    $mainImage->update(['is_main' => true]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Apartment updated successfully',
                'apartment' => $apartment,
                'images' => $apartment->images()->get()
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update apartment',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role === 'owner') {
            $apartment = Apartment::where('owner_id', $user->id)->findOrFail($id);
        } else {
            $apartment = Apartment::findOrFail($id);
        }

        DB::beginTransaction();

        try {
            foreach ($apartment->images as $image) {
                if (Storage::disk('public')->exists($image->image_url)) {
                    Storage::disk('public')->delete($image->image_url);
                }
            }

            $apartment->images()->delete();

            $apartment->delete();

            DB::commit();

            return response()->json([
                'message' => 'Apartment and its images deleted successfully'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete apartment',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // ======================
    //  ADMIN - Admin APIs
    // ======================

    public function adminIndex()
    {
        return Apartment::all();
    }

    public function adminStore(AdminApartmentRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            $data['owner_id'] = $request->user()->id;

            $apartment = Apartment::create($data);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store("apartments/{$apartment->id}", 'public');

                    ApartmentImage::create([
                        'apartment_id' => $apartment->id,
                        'image_url' => $path,
                        'display_order' => $index + 1,
                        'is_main' => $index === 0,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Apartment created successfully',
                'apartment' => $apartment
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create apartment'
            ], 500);
        }
    }

    public function adminUpdate(AdminApartmentRequest $request, $id)
    {
        $apartment = Apartment::findOrFail($id);

        DB::beginTransaction();
        try {
            $apartment->update($request->validated());

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store("apartments/{$apartment->id}", 'public');

                    ApartmentImage::create([
                        'apartment_id' => $apartment->id,
                        'image_url' => $path,
                        'display_order' => $apartment->images()->count() + 1,
                        'is_main' => false,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Apartment updated successfully',
                'apartment' => $apartment,
                'images' => $apartment->images()->get()
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update apartment', 'error' => $e->getMessage()], 500);
        }
    }

    public function adminDelete($id)
    {
        Apartment::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted by admin']);
    }

    // ======================
    //  ADMIN Approval
    // ======================

    public function approve($id)
    {
        $apartment = Apartment::where('status', 'pending')->findOrFail($id);
        $apartment->update(['status' => 'approved']);
        return response()->json(['message' => 'Apartment approved']);
    }

    public function reject($id)
    {
        $apartment = Apartment::where('status', 'pending')->findOrFail($id);
        $apartment->update(['status' => 'rejected']);
        return response()->json(['message' => 'Apartment rejected']);
    }
}
