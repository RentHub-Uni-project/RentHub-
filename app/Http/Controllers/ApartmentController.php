<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Review;
use App\Models\FavoriteApartment;
use Illuminate\Http\Request;
use App\Http\Requests\StoreApartmentRequest;
use App\Http\Requests\UpdateApartmentRequest;
use App\Http\Requests\AdminApartmentRequest;
use App\Http\Requests\AdminUpdateApartmentRequest;
use App\Http\Resources\ApartmentResource;

class ApartmentController extends Controller
{
    // ======================
    //  ALL USERS - Public APIs
    // ======================

    // List all approved apartments
    public function index(Request $request)
    {
        $query = Apartment::query();

        // show approved apartments first
        $query->where('status', 'approved');



        $query->when($request->filled('address'), fn($q) => $q->where('address', 'LIKE', '%' . $request->address . '%'));
        $query->when($request->filled('governorate'), fn($q) => $q->where('governorate', 'LIKE', '%' . $request->governorate . '%'));
        $query->when($request->filled('title'), fn($q) => $q->where('title', 'LIKE', '%' . $request->title . '%'));
        $query->when($request->filled('bedrooms'), fn($q) => $q->where('bedrooms', $request->bedrooms));
        $query->when($request->filled('bathrooms'), fn($q) => $q->where('bathrooms', $request->bathrooms));

        // price range
        $query->when($request->filled('min_price'), fn($q) => $q->where('price_per_night', '>=', $request->min_price));
        $query->when($request->filled('max_price'), fn($q) => $q->where('price_per_night', '<=', $request->max_price));


        // sorting: default to approved first, then by specified column

        $query->orderBy("created_at", 'asc');

        $result = $query->paginate($request->get('per_page', 15));

        return response()->json(["message" => "success", "data" => $result]);
    }

    // Show details of a specific apartment
    public function show(Request $request, Apartment $apartment)
    {
        $user = $request->user();
        if (!$user->isAdmin() && !$apartment->isApproved()) {
            return response()->json(["message" => "this appartment is not approved, you can't see its details."], 403);
        }
        return response()->json(["message" => "apartment found successfully.", "apartment" => $apartment]);
    }

    // Search apartments by address keyword
    public function search(Request $request)
    {
        $keyword = $request->input('keyword', '');
        return Apartment::where('status', 'approved')
            ->where(['address', 'title'], 'like', "%{$keyword}%")
            ->get();
    }

    // Filter apartments by various fields
    public function filter(Request $request)
    {
        $query = Apartment::query()->where('status', 'approved');

        if ($request->has('min_price')) $query->where('price_per_night', '>=', $request->min_price);
        if ($request->has('max_price')) $query->where('price_per_night', '<=', $request->max_price);
        if ($request->has('guests')) $query->where('max_guests', '>=', $request->guests);
        if ($request->has('bedrooms')) $query->where('bedrooms', $request->bedrooms);
        if ($request->has('bathrooms')) $query->where('bathrooms', $request->bathrooms);
        if ($request->has('city')) $query->where('city', $request->city);

        return $query->get();
    }

    // ======================
    //  OWNER - Owner APIs
    // ======================

    public function myApartments(Request $request)
    {
        $user = $request->user();
        $apartments = Apartment::where('owner_id', $user->id)->get();
        return response()->json(["message" => "success", "apartments" => $apartments]);
    }

    public function store(StoreApartmentRequest $request)
    {
        $data = $request->validated();
        $data['owner_id'] = $request->user()->id;
        $data['status'] = 'pending';

        $apartments = Apartment::create($data);
        return response()->json(["message" => "apartment created successfully.", "apartment" => new ApartmentResource($apartments)]);
    }

    public function update(UpdateApartmentRequest $request, Apartment $apartment)
    {
        $user = $request->user();
        if ($apartment->owner_id != $user->id) {
            return response()->json(["message" => "you are no the owner of the provided apartment."], 403);
        }

        $apartment->update($request->validated());
        return response()->json(["message" => "apartment updated successfully.", "apartment" => new ApartmentResource($apartment)]);
    }

    public function destroy(Request $request, Apartment $apartment)
    {
        $user = $request->user();
        if ($apartment->owner_id != $user->id) {
            return response()->json(["message" => "you are no the owner of the provided apartment."], 403);
        }
        $apartment->delete();
        return response()->json(['message' => 'apartment deleted successfully.'], 204);
    }

    // ======================
    //  ADMIN - Admin APIs
    // ======================

    public function adminIndex()
    {
        return response()->json(["message" => "success", "apartments" => Apartment::all()]);
    }

    public function adminStore(AdminApartmentRequest $request)
    {
        $apartment = Apartment::create($request->validated());
        return response()->json(["message" => "apartment created successfully.", "apartment" => new ApartmentResource($apartment)], 201);
    }

    public function adminUpdate(AdminUpdateApartmentRequest $request, Apartment $apartment)
    {
        $apartment->update($request->validated());
        return response()->json(["message" => "apartment updated successfully.", "apartment" => new ApartmentResource($apartment)]);
    }

    public function adminDelete(Request $request, Apartment $apartment)
    {
        $apartment->delete();
        return response()->json(['message' => 'apartment deleted successfully.'], 204);
    }

    // ======================
    //  ADMIN Approval
    // ======================

    public function approve(Apartment $apartment)
    {
        $apartment->update(['status' => 'approved']);
        return response()->json(['message' => 'Apartment approved', "apartment" => new ApartmentResource($apartment)]);
    }

    public function reject(Apartment $apartment)
    {
        $apartment->update(['status' => 'rejected']);
        return response()->json(['message' => 'Apartment rejected',  "apartment" => new ApartmentResource($apartment)]);
    }
}
