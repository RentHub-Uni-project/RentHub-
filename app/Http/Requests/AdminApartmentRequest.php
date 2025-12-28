<?php

namespace App\Http\Requests;

use App\Constants\SyrianGovernorates;
use Illuminate\Foundation\Http\FormRequest;

class AdminApartmentRequest extends FormRequest
{
    public function rules()
    {
        return [
            'owner_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'price_per_night' => 'required|numeric|min:0',
            'max_guests' => 'required|integer|min:1',
            'bedrooms' => 'required|integer|min:0',
            'bathrooms' => 'required|integer|min:0',
            'governorate' => 'required|in:' . implode(',', SyrianGovernorates::all()),
            'description' => 'nullable|string',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }
}
