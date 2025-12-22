<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Review;
use App\Models\FavoriteApartment;
use Illuminate\Http\Request;
use App\Http\Requests\StoreApartmentRequest;
use App\Http\Requests\UpdateApartmentRequest;
use App\Http\Requests\AdminApartmentRequest;

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



        $query->when($request->filled('title'), fn($q) => $q->where('title', 'LIKE', '%' . $request->title . '%'));
        $query->when($request->filled('city'), fn($q) => $q->where('city', $request->city));

        // price range
        $query->when($request->filled('min_price'), fn($q) => $q->where('price_per_night', '>=', $request->min_price));
        $query->when($request->filled('max_price'), fn($q) => $q->where('price_per_night', '<=', $request->max_price));

        $query->when($request->has('is_available'), function ($q) use ($request) {
            return $q->where('is_available', filter_var($request->is_available, FILTER_VALIDATE_BOOLEAN));
        });

        // sorting: default to approved first, then by specified column
        if (!$request->has('sort_by')) {
            $query->orderByRaw("FIELD(status, 'approved', 'pending', 'rejected')")
                ->orderBy('created_at', 'desc');
        } else {
            $query->orderBy($request->get('sort_by'), $request->get('sort_order', 'desc'));
        }

        $result = $query->paginate($request->get('per_page', 15));

        return response()->json(["message" => "success", "data" => $result]);
    }

    // Show details of a specific apartment
    public function show($id)
    {
        $apartment = Apartment::where('status', 'approved')->findOrFail($id);
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
        return Apartment::where('owner_id', $user->id)->get();
    }

    public function store(StoreApartmentRequest $request)
    {
        $data = $request->validated();
        $data['owner_id'] = $request->user()->id;
        $data['status'] = 'pending';
        return Apartment::create($data);
    }

    public function update(UpdateApartmentRequest $request, $id)
    {
        $user = $request->user();
        if ($user->role === 'owner') {
            $apartment = Apartment::where('owner_id', $user->id)->findOrFail($id);
        } else {
            $apartment = Apartment::findOrFail($id);
        }

        $apartment->update($request->validated());
        return $apartment;
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $apartment = Apartment::where('owner_id', $user->id)->findOrFail($id);
        $apartment->delete();
        return response()->json(['message' => 'Deleted']);
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
        return Apartment::create($request->validated());
    }

    public function adminUpdate(AdminApartmentRequest $request, $id)
    {
        $apartment = Apartment::findOrFail($id);
        $apartment->update($request->validated());
        return $apartment;
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
