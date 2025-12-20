<?php

namespace App\Http\Controllers;

use App\Models\Appartment;
use App\Models\Review;
use App\Models\FavoriteAppartment;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAppartmentRequest;
use App\Http\Requests\UpdateAppartmentRequest;
use App\Http\Requests\AdminAppartmentRequest;

class AppartmentController extends Controller
{
    // ======================
    //  ALL USERS - Public APIs
    // ======================

    // List all approved appartments
    public function index()
    {
        return Appartment::where('status', 'approved')->get();
    }

    // Show details of a specific appartment
    public function show($id)
    {
        return Appartment::where('status', 'approved')->findOrFail($id);
    }

    // Search appartments by address keyword
    public function search(Request $request)
    {
        $keyword = $request->input('keyword', '');
        return Appartment::where('status', 'approved')
            ->where(['address', 'title' ], 'like', "%{$keyword}%")
            ->get();
    }

    // Filter appartments by various fields
    public function filter(Request $request)
    {
        $query = Appartment::query()->where('status', 'approved');

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
        return Appartment::where('owner_id', $user->id)->get();
    }

    public function store(StoreAppartmentRequest $request)
    {
        $data = $request->validated();
        $data['owner_id'] = $request->user()->id;
        $data['status'] = 'pending';
        return Appartment::create($data);
    }

    public function update(UpdateAppartmentRequest $request, $id)
    {
        $user = $request->user();
        if ($user->role === 'owner') {
            $appartment = Appartment::where('owner_id', $user->id)->findOrFail($id);
        } else {
            $appartment = Appartment::findOrFail($id);
        }

        $appartment->update($request->validated());
        return $appartment;
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $appartment = Appartment::where('owner_id', $user->id)->findOrFail($id);
        $appartment->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // ======================
    //  ADMIN - Admin APIs
    // ======================

    public function adminIndex()
    {
        return Appartment::all();
    }

    public function adminStore(AdminAppartmentRequest $request)
    {
        return Appartment::create($request->validated());
    }

    public function adminUpdate(AdminAppartmentRequest $request, $id)
    {
        $appartment = Appartment::findOrFail($id);
        $appartment->update($request->validated());
        return $appartment;
    }

    public function adminDelete($id)
    {
        Appartment::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted by admin']);
    }

    // ======================
    //  ADMIN Approval
    // ======================

    public function approve($id)
    {
        $appartment = Appartment::where('status', 'pending')->findOrFail($id);
        $appartment->update(['status' => 'approved']);
        return response()->json(['message' => 'Appartment approved']);
    }

    public function reject($id)
    {
        $appartment = Appartment::where('status', 'pending')->findOrFail($id);
        $appartment->update(['status' => 'rejected']);
        return response()->json(['message' => 'Appartment rejected']);
    }


}
