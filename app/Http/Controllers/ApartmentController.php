<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\ApartmentImage;
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

    public function index(Request $request)     // GET /apartments?keyword=studio&city=Berlin&min_price=50&max_price=200&per_page=10
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

        return response()->json([
            'message' => 'success',
            'data' => $apartments
        ]);
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
        return Apartment::create($data);
    }

    public function update(UpdateApartmentRequest $request, Apartment $apartment)
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

    public function destroy(Request $request, Apartment $apartment)
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
        return response()->json(["message" => "success", "apartments" => Apartment::all()]);
    }

    public function adminStore(AdminApartmentRequest $request)
    {
        return Apartment::create($request->validated());
    }

    public function adminUpdate(AdminUpdateApartmentRequest $request, Apartment $apartment)
    {
        $apartment = Apartment::findOrFail($id);
        $apartment->update($request->validated());
        return $apartment;
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
