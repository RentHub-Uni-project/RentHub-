<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminApartmentRequest extends FormRequest
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
            'owner_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'price_per_night' => 'required|numeric|min:0',
            'price_per_month' => 'required|numeric|min:0',
            'max_guests' => 'required|integer|min:1',
            'bedrooms' => 'required|integer|min:0',
            'bathrooms' => 'required|integer|min:0',
            'governorate_id' => 'required|exists:governorates,id',
            'city' => 'required|string|max:100',
            'status' => 'required|in:pending,approved,rejected',
            'description' => 'nullable|string',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',

        ];
    }

    /**
     */
}
