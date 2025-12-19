<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppartmentRequest extends FormRequest
{
    /**
     */
    public function authorize()
    {
        $user = $this->user();
        $appartment = \App\Models\Appartment::find($this->route('id'));

        if (!$appartment) {
            return false;
        }

        return $user->role === 'admin' || $appartment->owner_id === $user->id;
    }

    /**
     */
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
            'governorate_id' => 'sometimes|exists:governorates,id',
            'city' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string',
            'status' => 'sometimes|in:pending,approved,rejected'
        ];
    }

    /**
     */
    
}
