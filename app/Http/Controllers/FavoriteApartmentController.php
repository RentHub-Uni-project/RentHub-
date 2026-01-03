<?php

namespace App\Http\Controllers;

use App\Models\FavoriteApartment;
use Illuminate\Http\Request;
use App\Models\Apartment;


class FavoriteApartmentController extends Controller
{
    public function toggleFavorite(Request $request, Apartment $apartment)
    {
        $apartment->load('images');
        $favorite = FavoriteApartment::where('tenant_id', $request->user()->id)
            ->where('apartment_id', $apartment->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json(['message' => 'Removed from favorites', 'apartment' => $apartment]);
        }

        $favorite = FavoriteApartment::create([
            'tenant_id' => $request->user()->id,
            'apartment_id' => $apartment->id,
        ]);


        return response()->json(['message' => 'apartment Added to favorites', 'apartment' => $apartment]);
    }



    public function myFavorites(Request $request)
    {
        $favoriteApartments = Apartment::whereHas('favorites', function ($q) use ($request) {
            $q->where('tenant_id', $request->user()->id);
        })->with('images')->get();
        return response()->json(["message" => "success", "favoriteAppartments" => $favoriteApartments]);
    }
}
