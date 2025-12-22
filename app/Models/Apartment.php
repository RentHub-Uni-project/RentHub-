<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
    use HasFactory;

    protected $table = 'apartments';

    protected $fillable = [
        'owner_id',
        'title',
        'description',
        'address',
        'price_per_night',
        'price_per_month',
        'max_guests',
        'bedrooms',
        'bathrooms',
        'governorate_id',
        'city',
        'latitude',
        'longitude',
        'is_available',
        'status',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'price_per_night' => 'decimal:2',
        'price_per_month' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function images()
    {
        return $this->hasMany(ApartmentImage::class, 'apartment_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'apartment_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'apartment_id');
    }
    public function favorites()
    {
        return $this->hasMany(FavoriteApartment::class, 'apartment_id');
    }


    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();

        $model = $this->where($field, $value)->first();

        if (!$model) {
            abort(response()->json([
                'message' => 'apartment not found',
            ], 404));
        }

        return $model;
    }

    public function isPending()
    {
        return $this->status == "pending";
    }
    public function isRejected()
    {
        return $this->status == "rejected";
    }
    public function isApproved()
    {
        return $this->status == "approved";
    }
}
