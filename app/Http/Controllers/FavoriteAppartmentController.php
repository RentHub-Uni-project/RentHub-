<?php

namespace App\Http\Controllers;

use App\Models\FavoriteAppartment;
use Illuminate\Http\Request;
use App\Models\Appartment;


class FavoriteAppartmentController extends Controller
{
public function toggleFavorite(Request $request, $id)
    {
        $favorite = FavoriteAppartment::where('tenant_id', $request->user()->id)
            ->where('appartment_id', $id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json(['message' => 'Removed from favorites']);
        }

        FavoriteAppartment::create([
            'tenant_id' => $request->user()->id,
            'appartment_id' => $id,
        ]);

        return response()->json(['message' => 'Added to favorites']);
    }



    public function myFavorites(Request $request)
    {
        return Appartment::whereHas('favorites', function ($q) use ($request) {
            $q->where('tenant_id', $request->user()->id);
        })->get();
    }
}
