<?php

namespace App\Http\Requests;

use App\Constants\SyrianGovernorates;
use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateApartmentRequest extends FormRequest
{
    /**
     */
    public function authorize()
    {
        return $this->user()->role === 'admin';
    }

    /**
     */
    public function rules()
    {
        return [
            'owner_id' => 'sometimes|exists:users,id',
            'title' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:500',
            'price_per_night' => 'sometimes|numeric|min:0',
            'max_guests' => 'sometimes|integer|min:1',
            'bedrooms' => 'sometimes|integer|min:0',
            'bathrooms' => 'sometimes|integer|min:0',
            'governorate' => 'sometimes|in:' . implode(',', SyrianGovernorates::all()),
            'status' => 'sometimes|in:pending,approved,rejected',
            'description' => 'sometimes|nullable|string',
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180'
        ];
    }
}
