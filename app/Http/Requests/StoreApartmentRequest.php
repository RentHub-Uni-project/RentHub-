<?php

namespace App\Http\Requests;

use App\Constants\SyrianGovernorates;
use Illuminate\Foundation\Http\FormRequest;

class StoreApartmentRequest extends FormRequest
{
    /**
     */
    public function authorize()
    {
        return $this->user()->role === 'owner';
    }

    /**
     */
    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'price_per_night' => 'required|numeric|min:0',
            'max_guests' => 'required|integer|min:1',
            'bedrooms' => 'required|integer|min:0',
            'bathrooms' => 'required|integer|min:0',
            'governorate' => 'required|in:' . implode(',', SyrianGovernorates::all()),
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180'
        ];
    }

    /**
     */
}
