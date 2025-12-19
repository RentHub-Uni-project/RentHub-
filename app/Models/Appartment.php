<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appartment extends Model
{
    use HasFactory;

    protected $table = 'appartments';

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
        'is_available'      => 'boolean',
        'price_per_night'   => 'decimal:2',
        'price_per_month'  => 'decimal:2',
        'latitude'          => 'decimal:8',
        'longitude'         => 'decimal:8',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function images()
    {
        return $this->hasMany(AppartmentImage::class, 'apartment_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'apartment_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'apartment_id');
    }
}
