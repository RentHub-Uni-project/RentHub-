<?php

namespace App\Http\Requests;

use App\Constants\SyrianGovernorates;
use Illuminate\Foundation\Http\FormRequest;

class UpdateApartmentRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:500',
            'price_per_night' => 'sometimes|numeric|min:0',
            'price_per_month' => 'sometimes|numeric|min:0',
            'max_guests' => 'sometimes|numeric|min:1',
            'bedrooms' => 'sometimes|numeric|min:0',
            'bathrooms' => 'sometimes|numeric|min:0',
            'governorate' => 'in:' . implode(',', SyrianGovernorates::all()),
            'description' => 'nullable|string',

            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',

        ];
    }
}
