<?php

namespace App\Http\Controllers;

use App\Models\FavoriteApartment;
use Illuminate\Http\Request;
use App\Models\Apartment;


class FavoriteApartmentController extends Controller
{
    public function toggleFavorite(Request $request, $id)
    {
        $favorite = FavoriteApartment::where('tenant_id', $request->user()->id)
            ->where('apartment_id', $id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json(['message' => 'Removed from favorites']);
        }

        FavoriteApartment::create([
            'tenant_id' => $request->user()->id,
            'apartment_id' => $id,
        ]);

        return response()->json(['message' => 'Added to favorites']);
    }



    public function myFavorites(Request $request)
    {
        return Apartment::whereHas('favorites', function ($q) use ($request) {
            $q->where('tenant_id', $request->user()->id);
        })->get();
    }
}
