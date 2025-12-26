<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApartmentRequest extends FormRequest
{
    /**
     */
    public function authorize()
    {
        $user = $this->user();
        $apartment = \App\Models\Apartment::find($this->route('id'));

        if (!$apartment) {
            return false;
        }

        return $user->role === 'admin' || $apartment->owner_id === $user->id;
    }


    public function rules()
    {
        return [
            'title' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:500',
            'price_per_night' => 'sometimes|numeric|min:0',
            'price_per_month' => 'sometimes|numeric|min:0',
            'max_guests' => 'sometimes|integer|min:1',
            'bedrooms' => 'sometimes|integer|min:0',
            'bathrooms' => 'sometimes|integer|min:0',
            'city' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:pending,approved,rejected',

            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',

        ];
    }


}
