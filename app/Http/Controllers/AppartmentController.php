<?php

namespace App\Http\Controllers;

use App\Models\Appartment;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAppartmentRequest;
use App\Http\Requests\UpdateAppartmentRequest;
use App\Http\Requests\AdminAppartmentRequest;

class AppartmentController extends Controller
{
    //  ALL USERS - Public APIs
    public function index()
    {
        return Appartment::where('status', 'approved')->get();
    }

    public function show($id)
    {
        return Appartment::where('status', 'approved')->findOrFail($id);
    }

    //  OWNER - Owner APIs
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
        return response()->json(['message' => ' deleted ']);
    }

    // ADMIN - Admin APIs
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
        return response()->json(['message' => 'deleted by admin']);
    }
}
